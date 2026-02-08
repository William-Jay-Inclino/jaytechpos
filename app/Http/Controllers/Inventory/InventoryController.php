<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLowStockThresholdRequest;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::ownedBy()
            ->with(['inventory'])
            ->orderBy('product_name');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->search);
            $query->whereRaw('LOWER(product_name) like ?', ["%{$search}%"]);
        }

        $products = $query->paginate(15)->withQueryString()->through(function ($product) {
            return [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'quantity' => $product->inventory?->quantity ?? 0,
                'low_stock_threshold' => $product->inventory?->low_stock_threshold ?? 0,
            ];
        });

        $unstockedProducts = Product::ownedBy()
            ->whereDoesntHave('inventory')
            ->orderBy('product_name')
            ->get(['id', 'product_name']);

        return Inertia::render('inventory/Index', [
            'products' => $products,
            'unstockedProducts' => $unstockedProducts,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function updateLowStockThreshold(UpdateLowStockThresholdRequest $request): JsonResponse
    {
        try {
            $inventory = Inventory::firstOrCreate(
                ['product_id' => $request->product_id],
                ['quantity' => 0, 'low_stock_threshold' => 0]
            );

            $inventory->update([
                'low_stock_threshold' => $request->low_stock_threshold,
            ]);

            $inventory->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Low stock threshold updated successfully.',
                'product' => [
                    'id' => $inventory->product_id,
                    'product_name' => $inventory->product->product_name,
                    'quantity' => number_format((float) $inventory->quantity, 2, '.', ''),
                    'low_stock_threshold' => number_format((float) $inventory->low_stock_threshold, 2, '.', ''),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update low stock threshold.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
