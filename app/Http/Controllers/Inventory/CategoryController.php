<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Category;

class CategoryController extends Controller
{
    
    public function index(): Response
    {
        $categories = Category::orderBy('category_name')->get();
        return Inertia::render('categories/Index', [
            "categories" => $categories
        ]);
    }

    
    public function create(): Response
    {
        return Inertia::render('categories/Create');
    }

    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        Category::create($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created successfully.');
    }
    
    
    public function edit(Category $category)
    {   
        return Inertia::render('categories/Edit', [
            'category' => $category
        ]);
    }

    
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');

    }

    
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
