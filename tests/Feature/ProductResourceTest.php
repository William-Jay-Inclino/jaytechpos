<?php

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it transforms product data correctly', function () {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'product_name' => 'Test Product',
        'description' => 'Test Description',
        'unit_price' => 100.50,
        'cost_price' => 75.25,
        'status' => 'active',
    ]);

    $resource = new ProductResource($product);
    $result = $resource->toArray(request());

    expect($result)->toHaveKeys([
        'id', 'user_id', 'category_id', 'unit_id', 'product_name',
        'description', 'unit_price', 'cost_price', 'status',
        'created_at', 'updated_at',
    ]);

    expect($result['id'])->toBe($product->id);
    expect($result['user_id'])->toBe($user->id);
    expect($result['category_id'])->toBe($category->id);
    expect($result['unit_id'])->toBe($unit->id);
    expect($result['product_name'])->toBe('Test Product');
    expect($result['description'])->toBe('Test Description');
    expect($result['unit_price'])->toBe(100.50);
    expect($result['cost_price'])->toBe(75.25);
    expect($result['status'])->toBe('active');
    expect($result['created_at'])->toBeString();
    expect($result['updated_at'])->toBeString();
});

test('it includes related models when loaded', function () {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Electronics',
        'description' => 'Electronic items',
        'status' => 'active',
    ]);
    $unit = Unit::factory()->create([
        'unit_name' => 'Piece',
        'abbreviation' => 'pcs',
    ]);

    $product = Product::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    // Load the relationships
    $product->load(['productCategory', 'unit']);

    $resource = new ProductResource($product);
    $result = $resource->toArray(request());

    expect($result)->toHaveKey('product_category');
    expect($result['product_category'])->toEqual([
        'id' => $category->id,
        'name' => 'Electronics',
        'description' => 'Electronic items',
        'status' => 'active',
    ]);

    expect($result)->toHaveKey('unit');
    expect($result['unit'])->toEqual([
        'id' => $unit->id,
        'unit_name' => 'Piece',
        'abbreviation' => 'pcs',
    ]);
});

test('it does not include related models when not loaded', function () {
    $product = Product::factory()->create();

    $resource = new ProductResource($product);
    $result = $resource->toArray(request());

    expect($result)->not->toHaveKey('product_category');
    expect($result)->not->toHaveKey('unit');
});
