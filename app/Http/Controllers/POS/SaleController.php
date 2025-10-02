<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;


class SaleController extends Controller
{
    
    public function create(): Response
    {
        $products = Product::orderBy('product_name')
            ->get();

        $customers = Customer::orderBy('name')->get();

        return Inertia::render('sales/Create', [
            "products" => $products,
            "customers" => $customers
        ]);
    }

    public function store(Request $request): Response
    {
        // Mock successful sale creation
        $mockSaleData = [
            'id' => rand(1000, 9999),
            'invoice_number' => 'INV-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
            'transaction_date' => now()->toISOString(),
            'customer_id' => $request->input('customer_id'),
            'subtotal' => $request->input('subtotal'),
            'total_amount' => $request->input('total_amount'),
            'discount_amount' => $request->input('discount_amount'),
            'vat_amount' => $request->input('vat_amount'),
            'net_amount' => $request->input('net_amount'),
            'amount_tendered' => $request->input('amount_tendered'),
            'change_amount' => $request->input('change_amount'),
            'items' => $request->input('items'),
            'receipt_number' => 'RCP-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'cashier' => auth()->user()->name ?? 'System User'
        ];

        // Return Inertia response with sale data
        return Inertia::render('sales/Create', [
            'products' => Product::orderBy('product_name')->get(),
            'customers' => Customer::orderBy('name')->get(),
            'sale' => $mockSaleData
        ]);
    }

}
