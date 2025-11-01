<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::ownedBy()
            ->with(['productCategory', 'unit'])
            ->orderBy('product_name')
            ->get();

        $categories = ProductCategory::activeOwned()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('products/Index', [
            'products' => ProductResource::collection($products)->resolve(),
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = ProductCategory::activeOwned()
            ->orderBy('name')
            ->get(['id', 'name']);

        $units = Unit::orderBy('unit_name')->get(['id', 'unit_name', 'abbreviation']);

        $defaultCategory = ProductCategory::default()->first();

        return Inertia::render('products/Create', [
            'categories' => $categories,
            'units' => $units,
            'defaultCategoryId' => $defaultCategory?->id,
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        $product = Product::create($validated);
        $product->load(['productCategory', 'unit']);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully!',
            'product' => new ProductResource($product),
        ]);
    }

    public function edit(string $id)
    {
        $product = Product::with(['productCategory', 'unit'])->findOrFail($id);

        $this->authorize('view', $product);

        $categories = ProductCategory::activeOwned()
            ->orderBy('name')
            ->get(['id', 'name']);

        $units = Unit::orderBy('unit_name')->get(['id', 'unit_name', 'abbreviation']);

        return Inertia::render('products/Edit', [
            'product' => (new ProductResource($product))->resolve(),
            'categories' => $categories,
            'units' => $units,
        ]);
    }

    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Product::findOrFail($id);

        $this->authorize('update', $product);

        $validated = $request->validated();
        $product->update($validated);
        $product->load(['productCategory', 'unit']);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully!',
            'product' => new ProductResource($product),
        ]);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);

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
            'message' => 'Product deleted successfully!',
        ]);
    }
}
