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
use App\Services\SaleService;
use App\Services\UtangTrackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected SaleService $saleService,
        protected UtangTrackingService $utangTrackingService
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Sale::class);

        $products = Product::availableForSale()
            ->orderBy('product_name')
            ->get();

        $customers = CustomerResource::collection(
            Customer::ownedBy()
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
        $this->authorize('createForCustomer', [Sale::class, $request->validated('customer_id')]);

        try {
            DB::beginTransaction();

            // Generate unique invoice number
            $invoiceNumber = $this->saleService->generateInvoiceNumber();

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
                $this->utangTrackingService->updateUtangTracking($sale);
            }

            // Handle balance deduction for cash payments
            if ($request->validated('payment_type') === 'cash' && $request->validated('deduct_from_balance') > 0) {
                $this->utangTrackingService->deductFromRunningBalance($sale->customer_id, $request->validated('deduct_from_balance'));
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
                'products' => Product::availableForSale()->orderBy('product_name')->get(),
                'customers' => CustomerResource::collection(
                    Customer::ownedBy()
                        ->orderBy('name')
                        ->get()
                )->resolve(),
                'sale' => $saleData,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Sale creation failed: '.$e->getMessage());

            return Inertia::render('sales/Index', [
                'products' => Product::availableForSale()->orderBy('product_name')->get(),
                'customers' => CustomerResource::collection(
                    Customer::ownedBy()
                        ->orderBy('name')
                        ->get()
                )->resolve(),
                'error' => 'Failed to process the sale. Please try again.',
            ]);
        }
    }
}
