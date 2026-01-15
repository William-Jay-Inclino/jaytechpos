<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::ownedBy()
            ->with(['unit'])
            ->orderBy('product_name')
            ->get();

        return Inertia::render('products/Index', [
            'products' => ProductResource::collection($products)->resolve(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $units = Unit::orderBy('unit_name')->get(['id', 'unit_name', 'abbreviation']);

        return Inertia::render('products/Create', [
            'units' => $units,
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validated();
        $validated['user_id'] = Auth::id();

        $product = Product::create($validated);
        $product->load(['unit']);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully!',
            'product' => new ProductResource($product),
        ]);
    }

    public function edit(string $id)
    {
        $product = Product::with(['unit'])->findOrFail($id);

        $this->authorize('view', $product);

        $units = Unit::orderBy('unit_name')->get(['id', 'unit_name', 'abbreviation']);

        return Inertia::render('products/Edit', [
            'product' => (new ProductResource($product))->resolve(),
            'units' => $units,
        ]);
    }

    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Product::findOrFail($id);

        $this->authorize('update', $product);

        $validated = $request->validated();
        $product->update($validated);
        $product->load(['unit']);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully!',
            'product' => new ProductResource($product),
        ]);
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        // Check if product is referenced in sales items
        if ($product->salesItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this product because it has been used in sales transactions.',
            ], 422);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'msg' => 'Product deleted successfully.',
        ]);
    }
}
