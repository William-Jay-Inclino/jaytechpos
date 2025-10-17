<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', ProductCategory::class);

        $product_categories = ProductCategory::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return Inertia::render('product_categories/Index', [
            'product_categories' => $product_categories,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ProductCategory::class);

        return Inertia::render('product_categories/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', ProductCategory::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = auth()->id();

        ProductCategory::create($validated);

        return redirect()
            ->route('product-categories.index')
            ->with('success', 'Product Category created successfully.');
    }

    public function edit(ProductCategory $product_category)
    {
        $this->authorize('update', $product_category);

        return Inertia::render('product_categories/Edit', [
            'product_category' => $product_category,
        ]);
    }

    public function update(Request $request, ProductCategory $product_category)
    {
        $this->authorize('update', $product_category);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $product_category->update($validated);

        return back()->with('success', 'Product Category updated successfully.');

    }

    public function destroy(ProductCategory $product_category)
    {
        $this->authorize('delete', $product_category);

        $product_category->delete();

        return redirect()
            ->route('product-categories.index')
            ->with('success', 'Product Category deleted successfully.');
    }
}
