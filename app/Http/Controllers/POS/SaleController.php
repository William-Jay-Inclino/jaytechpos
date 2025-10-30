<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesItem;
use App\Models\UtangTracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function create(): Response
    {
        $products = Product::orderBy('product_name')
            ->get();

        $customers = CustomerResource::collection(
            Customer::where('user_id', auth()->id())
                ->orderBy('name')
                ->get()
        )->resolve();

        return Inertia::render('sales/Index', [
            'products' => $products,
            'customers' => $customers,
        ]);
    }

    public function store(StoreSaleRequest $request): Response
    {
        try {
            DB::beginTransaction();

            // Generate unique invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // Get current balance before creating the sale
            $previousBalance = 0;
            $newBalance = 0;
            
            if ($request->validated('customer_id')) {
                $customer = Customer::find($request->validated('customer_id'));
                if ($customer) {
                    $previousBalance = $customer->running_utang_balance;
                    
                    // Calculate new balance based on payment type
                    if ($request->validated('payment_type') === 'utang') {
                        // For utang sales, balance increases by unpaid amount
                        $unpaidAmount = $request->validated('total_amount') - $request->validated('paid_amount');
                        $newBalance = $previousBalance + $unpaidAmount;
                    } else {
                        // For cash sales with balance deduction, balance decreases
                        $balanceDeduction = $request->validated('deduct_from_balance', 0);
                        $newBalance = $previousBalance - $balanceDeduction;
                    }
                }
            }

            // Create the sale record with all required fields including balance tracking
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'customer_id' => $request->validated('customer_id'),
                'total_amount' => $request->validated('total_amount'),
                'paid_amount' => $request->validated('paid_amount'),
                'previous_balance' => $previousBalance,
                'new_balance' => $newBalance,
                'invoice_number' => $invoiceNumber,
                'payment_type' => $request->validated('payment_type'),
                'transaction_date' => now(),
                'notes' => $request->validated('notes'),
            ]);

            // Create sales items
            foreach ($request->validated('items') as $itemData) {
                SalesItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                ]);
            }

            // Handle utang tracking if payment type is utang
            if ($request->validated('payment_type') === 'utang') {
                $this->updateUtangTracking($sale);
            }

            // Handle balance deduction for cash payments
            if ($request->validated('payment_type') === 'cash' && $request->validated('deduct_from_balance') > 0) {
                $this->deductFromRunningBalance($sale->customer_id, $request->validated('deduct_from_balance'));
            }

            DB::commit();

            // Load relationships for the resource
            $sale->load(['user', 'customer', 'salesItems.product']);

            // Add payment calculation details to the sale object for the resource
            $sale->amount_tendered = $request->validated('payment_type') === 'cash' 
                ? $request->validated('amount_tendered', $sale->paid_amount)
                : null;
            
            $sale->change_amount = $request->validated('payment_type') === 'cash'
                ? max(0, $request->validated('amount_tendered', 0) - $sale->total_amount - $request->validated('deduct_from_balance', 0))
                : null;
            
            $sale->balance_payment = $request->validated('deduct_from_balance', 0);
            
            $sale->original_customer_balance = $sale->previous_balance;
            
            $sale->new_customer_balance = $sale->new_balance;

            // Prepare response data using the resource
            $saleData = (new SaleResource($sale))->resolve();

            return Inertia::render('sales/Index', [
                'products' => Product::orderBy('product_name')->get(),
                'customers' => CustomerResource::collection(
                    Customer::where('user_id', auth()->id())
                        ->orderBy('name')
                        ->get()
                )->resolve(),
                'sale' => $saleData,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Sale creation failed: '.$e->getMessage());

            return Inertia::render('sales/Index', [
                'products' => Product::orderBy('product_name')->get(),
                'customers' => CustomerResource::collection(
                    Customer::where('user_id', auth()->id())
                        ->orderBy('name')
                        ->get()
                )->resolve(),
                'error' => 'Failed to process the sale. Please try again.',
            ]);
        }
    }

    /**
     * Generate a unique invoice number.
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.date('Y').'-';
        
        // Get all sales with the current year prefix and extract numbers
        $existingNumbers = Sale::where('invoice_number', 'like', $prefix.'%')
            ->pluck('invoice_number')
            ->map(function ($invoiceNumber) use ($prefix) {
                $numberPart = substr($invoiceNumber, strlen($prefix));
                return (int) $numberPart;
            })
            ->max();

        $nextNumber = $existingNumbers ? $existingNumbers + 1 : 1;

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update utang tracking when a sale with utang payment is created.
     */
    private function updateUtangTracking(Sale $sale): void
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $utangAmount = $sale->total_amount - $sale->paid_amount;
        $customer = Customer::find($sale->customer_id);

        // Find existing utang tracking for current month
        $currentTracking = UtangTracking::where('customer_id', $sale->customer_id)
            ->whereMonth('computation_date', $currentMonth)
            ->whereYear('computation_date', $currentYear)
            ->first();

        if ($currentTracking) {
            // Update existing record
            $currentTracking->increment('beginning_balance', $utangAmount);
        } else {
            // Create new record for current month
            $previousBalance = 0;
            $interestRate = $customer->getEffectiveInterestRate();

            // Get the most recent tracking from previous months to calculate starting balance with interest
            $previousTracking = UtangTracking::where('customer_id', $sale->customer_id)
                ->where('computation_date', '<', now()->startOfMonth())
                ->orderBy('computation_date', 'desc')
                ->first();

            if ($previousTracking) {
                // Use customer's current effective interest rate for new calculations
                $interestRate = $customer->getEffectiveInterestRate();
                // Calculate balance with interest from previous month
                $previousBalance = $previousTracking->beginning_balance * (1 + ($interestRate / 100));
            }

            UtangTracking::create([
                'user_id' => $sale->user_id,
                'customer_id' => $sale->customer_id,
                'beginning_balance' => $previousBalance + $utangAmount,
                'computation_date' => now()->startOfMonth(),
                'interest_rate' => $interestRate,
            ]);
        }

        // Update customer's has_utang status
        Customer::where('id', $sale->customer_id)->update(['has_utang' => true]);
    }

    /**
     * Deduct amount from customer's running balance.
     */
    private function deductFromRunningBalance(int $customerId, float $deductionAmount): void
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Find existing utang tracking for current month
        $currentTracking = UtangTracking::where('customer_id', $customerId)
            ->whereMonth('computation_date', $currentMonth)
            ->whereYear('computation_date', $currentYear)
            ->first();

        if ($currentTracking) {
            // Deduct from existing record
            $currentTracking->decrement('beginning_balance', $deductionAmount);
            
            // If balance is now zero or negative, update customer's has_utang status
            if ($currentTracking->fresh()->beginning_balance <= 0) {
                Customer::where('id', $customerId)->update(['has_utang' => false]);
            }
        } else {
            // Get the most recent tracking from previous months
            $previousTracking = UtangTracking::where('customer_id', $customerId)
                ->where('computation_date', '<', now()->startOfMonth())
                ->orderBy('computation_date', 'desc')
                ->first();

            if ($previousTracking) {
                $customer = Customer::find($customerId);
                $interestRate = $customer->getEffectiveInterestRate();
                
                // Calculate balance with interest from previous month and subtract deduction
                $balanceWithInterest = $previousTracking->beginning_balance * (1 + ($interestRate / 100));
                $newBalance = $balanceWithInterest - $deductionAmount;

                UtangTracking::create([
                    'user_id' => auth()->id(),
                    'customer_id' => $customerId,
                    'beginning_balance' => max(0, $newBalance), // Ensure non-negative
                    'computation_date' => now()->startOfMonth(),
                    'interest_rate' => $interestRate,
                ]);

                // Update customer's has_utang status
                Customer::where('id', $customerId)->update(['has_utang' => $newBalance > 0]);
            }
        }
    }
}
