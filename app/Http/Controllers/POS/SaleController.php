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

            // Create the sale record with all required fields
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'customer_id' => $request->validated('customer_id'),
                'total_amount' => $request->validated('total_amount'),
                'paid_amount' => $request->validated('paid_amount'),
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

            DB::commit();

            // Load relationships for the resource
            $sale->load(['user', 'customer', 'salesItems.product']);

            // Prepare response data using the resource
            $saleData = new SaleResource($sale);

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

            return back()->withErrors([
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
        $lastSale = Sale::where('invoice_number', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->invoice_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

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
}
