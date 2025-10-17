<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesItem;
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

        $customers = Customer::orderBy('name')->get();

        return Inertia::render('sales/Create', [
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

            // Create the sale record
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'customer_id' => $request->validated('customer_id'),
                'total_amount' => $request->validated('total_amount'),
                'invoice_number' => $invoiceNumber,
                'payment_type' => 'cash',
                'transaction_date' => now(),
            ]);

            // Create sales items
            $items = [];
            foreach ($request->validated('items') as $itemData) {
                $salesItem = SalesItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                ]);

                // Load product details for the receipt
                $product = Product::find($salesItem->product_id);

                $items[] = [
                    'product_id' => $salesItem->product_id,
                    'product_name' => $product->product_name,
                    'quantity' => $salesItem->quantity,
                    'unit_price' => $salesItem->unit_price,
                    'total_amount' => $salesItem->quantity * $salesItem->unit_price,
                ];
            }

            DB::commit();

            // Prepare response data
            $saleData = [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'transaction_date' => $sale->transaction_date->toISOString(),
                'customer_id' => $sale->customer_id,
                'total_amount' => $sale->total_amount,
                'items' => $items,
                'cashier' => auth()->user()->name ?? 'System User',
            ];

            return Inertia::render('sales/Create', [
                'products' => Product::orderBy('product_name')->get(),
                'customers' => Customer::orderBy('name')->get(),
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
}
