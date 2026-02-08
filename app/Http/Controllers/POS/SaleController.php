<?php

namespace App\Http\Controllers\POS;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesItem;
use App\Models\StockMovement;
use App\Services\CustomerService;
use App\Services\SaleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected SaleService $saleService,
        protected CustomerService $customerService
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Sale::class);

        $hasProducts = Product::availableForSale()->exists();

        $customers = Customer::ownedBy()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('sales/Index', [
            'hasProducts' => $hasProducts,
            'customers' => $customers,
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Sale::class);

        $query = Product::availableForSale()
            ->with(['unit', 'inventory:product_id,quantity'])
            ->orderBy('product_name');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(product_name) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('unit', fn ($q) => $q->whereRaw('LOWER(abbreviation) LIKE ?', ["%{$search}%"]));
            });
        }

        $products = $query->limit(20)->get();

        return response()->json(['products' => $products]);
    }

    public function getSale(Sale $sale)
    {
        $this->authorize('view', $sale);

        // Ensure relationships required by the resource are loaded to avoid runtime errors
        $sale->load(['user', 'customer', 'salesItems.product']);

        $data = (new SaleResource($sale))->resolve();

        return response()->json([
            'sale' => $data,
        ]);
    }

    public function getCustomerBalance(Customer $customer)
    {
        $this->authorize('view', $customer);

        return response()->json([
            'balance' => $customer->running_utang_balance,
        ]);
    }

    public function store(StoreSaleRequest $request)
    {
        $this->authorize('createForCustomer', [Sale::class, $request->validated('customer_id')]);

        try {
            DB::beginTransaction();

            // Check inventory availability for all products
            foreach ($request->validated('items') as $itemData) {
                $inventory = Inventory::where('product_id', $itemData['product_id'])->first();

                if ($inventory) {
                    if ($inventory->quantity < $itemData['quantity']) {
                        $product = Product::find($itemData['product_id']);
                        DB::rollBack();

                        return response()->json([
                            'message' => "Insufficient stock for {$product->product_name}. Available: {$inventory->quantity}",
                        ], 422);
                    }
                }
            }

            // Generate unique invoice number for this user
            $invoiceNumber = $this->saleService->generateInvoiceNumber(Auth::id());

            // Get customer and current balance for customer transaction tracking
            $customer = null;
            $previousBalance = 0;
            $newBalance = 0;

            if ($request->validated('customer_id')) {
                $customer = Customer::find($request->validated('customer_id'));
                if ($customer) {
                    $previousBalance = $customer->running_utang_balance;

                    // Calculate new balance based on payment type for customer transaction record
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

            // Create the sale record
            $sale = Sale::create([
                'user_id' => Auth::id(),
                'customer_id' => $request->validated('customer_id'),
                'total_amount' => $request->validated('total_amount'),
                'paid_amount' => $request->validated('paid_amount'),
                'amount_tendered' => $request->validated('payment_type') === 'cash'
                    ? $request->validated('amount_tendered')
                    : null,
                'deduct_from_balance' => $request->validated('deduct_from_balance', 0),
                'invoice_number' => $invoiceNumber,
                'payment_type' => $request->validated('payment_type'),
                'transaction_date' => $request->validated('transaction_date'),
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

            // Update inventory and create stock movements
            foreach ($request->validated('items') as $itemData) {
                $inventory = Inventory::where('product_id', $itemData['product_id'])->first();

                if ($inventory) {
                    // Reduce inventory quantity
                    $inventory->stockOut($itemData['quantity']);

                    // Create stock movement record
                    StockMovement::create([
                        'product_id' => $itemData['product_id'],
                        'type' => StockMovementType::OUT,
                        'quantity' => $itemData['quantity'],
                        'reference' => $sale->id,
                        'remarks' => "Sale - Invoice #{$invoiceNumber}",
                    ]);
                }
            }

            // Customer transaction tracking is handled by the system

            // Create customer transaction record (only if customer is selected)
            if ($sale->customer_id) {
                CustomerTransaction::create([
                    'user_id' => Auth::id(),
                    'customer_id' => $sale->customer_id,
                    'transaction_type' => 'sale',
                    'reference_id' => $sale->id,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                    'transaction_desc' => $invoiceNumber,
                    'transaction_date' => $request->validated('transaction_date'),
                    'transaction_amount' => $sale->total_amount,
                ]);
            }

            DB::commit();

            // Load relationships for the resource
            $sale->load(['user', 'customer', 'salesItems.product']);

            // Add calculated payment details to the sale object for the resource
            $sale->change_amount = $request->validated('payment_type') === 'cash'
                ? max(0, $request->validated('amount_tendered', 0) - $sale->total_amount - $request->validated('deduct_from_balance', 0))
                : null;

            $sale->original_customer_balance = $previousBalance;
            $sale->new_customer_balance = $newBalance;

            // Prepare response data using the resource
            $saleData = (new SaleResource($sale))->resolve();

            return response()->json([
                'sale' => $saleData,
                'message' => 'Sale completed successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Sale creation failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to process the sale. Please try again.',
            ], 500);
        }
    }
}
