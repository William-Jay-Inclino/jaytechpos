<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can view product create page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/products/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($assert) => $assert
        ->component('products/Create')
        ->has('categories')
        ->has('units')
    );
});

test('user can create a new product', function () {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create();

    $productData = [
        'product_name' => 'Test Product',
        'description' => 'Test Description',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'unit_price' => 100.50,
        'cost_price' => 75.25,
        'status' => 'active',
    ];

    $response = $this->actingAs($user)->post('/products', $productData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Product created successfully!',
    ]);
    $response->assertJsonStructure([
        'success',
        'message',
        'product' => [
            'id',
            'product_name',
            'unit_price',
            'cost_price',
            'status',
            'product_category',
            'unit',
        ],
    ]);

    $this->assertDatabaseHas('products', [
        'product_name' => 'Test Product',
        'user_id' => $user->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'unit_price' => '100.50',
        'cost_price' => '75.25',
        'status' => 'active',
    ]);
});

test('product creation requires valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/products', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'product_name',
        'category_id',
        'unit_id',
        'unit_price',
        'cost_price',
        'status',
    ]);
});

test('product name must be globally unique', function () {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create();

    Product::factory()->create([
        'user_id' => $user->id,
        'product_name' => 'Existing Product',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    $productData = [
        'product_name' => 'Existing Product',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'unit_price' => 100.50,
        'cost_price' => 75.25,
        'status' => 'active',
    ];

    $response = $this->actingAs($user)->post('/products', $productData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['product_name']);
});
