<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::ownedBy()
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    public function active()
    {
        $categories = ProductCategory::activeOwned()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'is_default' => 'sometimes|boolean',
        ]);

        $validated['user_id'] = auth()->id();

        // Ensure is_default is set to a boolean value
        $validated['is_default'] = $validated['is_default'] ?? false;

        // If setting as default, unset other defaults for this user
        if ($validated['is_default']) {
            ProductCategory::ownedBy()->update(['is_default' => false]);
        }

        $category = ProductCategory::create($validated);

        return response()->json([
            'success' => true,
            'category' => $category,
            'message' => 'Category created successfully!',
        ], 201);
    }

    public function update(Request $request, ProductCategory $productCategory)
    {
        // Ensure the category belongs to the authenticated user
        if ($productCategory->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name,'.$productCategory->id,
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'is_default' => 'sometimes|boolean',
        ]);

        // Ensure is_default is set to a boolean value
        $validated['is_default'] = $validated['is_default'] ?? false;

        // If setting as default, unset other defaults for this user
        if ($validated['is_default']) {
            ProductCategory::ownedBy()->where('id', '!=', $productCategory->id)->update(['is_default' => false]);
        }

        $productCategory->update($validated);

        return response()->json([
            'success' => true,
            'category' => $productCategory->fresh(),
            'message' => 'Category updated successfully!',
        ]);
    }

    public function destroy(ProductCategory $productCategory)
    {
        // Ensure the category belongs to the authenticated user
        if ($productCategory->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check if category has products
        if ($productCategory->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing products',
            ], 422);
        }

        $productCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully!',
        ]);
    }
}
