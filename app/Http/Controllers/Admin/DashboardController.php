<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_sales_today' => Sale::whereDate('created_at', today())->sum('total_amount'),
            'total_products' => Product::count(),
            'total_customers' => Customer::count(),
            'recent_sales' => Sale::with('user')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'total_amount' => $sale->total_amount,
                        'user_name' => $sale->user->name,
                        'created_at' => $sale->created_at->format('M d, Y H:i'),
                    ];
                }),
        ];

        return Inertia::render('admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
