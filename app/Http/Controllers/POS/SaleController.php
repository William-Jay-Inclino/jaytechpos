<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Sale;
use App\Models\Product;

class SaleController extends Controller
{
    
    public function create(): Response
    {
        $products = Product::orderBy('product_name')
            ->with(['unit', 'vatRate'])
            ->get();
        
            return Inertia::render('sales/Create', [
            "products" => $products
        ]);
    }

}
