<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::ownedBy()
            ->with(['unit'])
            ->orderBy('product_name');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(product_name) like ?', ["%{$search}%"])
                    ->orWhere('barcode', $search)
                    ->orWhereHas('unit', fn ($q) => $q->whereRaw('LOWER(unit_name) like ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(abbreviation) like ?', ["%{$search}%"]));
            });
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('status', $request->status);
        }

        $products = $query->paginate(15)->withQueryString();

        return Inertia::render('products/Index', [
            'products' => ProductResource::collection($products),
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
            ],
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
