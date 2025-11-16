<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can view product edit page', function () {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    $response = $this->actingAs($user)->get("/products/{$product->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($assert) => $assert
        ->component('products/Edit')
        ->has('product')
        ->has('categories')
        ->has('units')
    );
});

test('user can update a product', function () {
    $user = User::factory()->create();
    $category1 = ProductCategory::factory()->create(['user_id' => $user->id]);
    $category2 = ProductCategory::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category1->id,
        'unit_id' => $unit->id,
        'product_name' => 'Original Name',
        'unit_price' => 100.00,
        'cost_price' => 75.00,
    ]);

    $updateData = [
        'product_name' => 'Updated Product Name',
        'description' => 'Updated description',
        'category_id' => $category2->id,
        'unit_id' => $unit->id,
        'unit_price' => 150.00,
        'cost_price' => 100.00,
        'status' => 'inactive',
    ];

    $response = $this->actingAs($user)->put("/products/{$product->id}", $updateData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Product updated successfully!',
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
        'id' => $product->id,
        'product_name' => 'Updated Product Name',
        'category_id' => $category2->id,
        'unit_price' => '150.00',
        'cost_price' => '100.00',
        'status' => 'inactive',
    ]);
});

test('user cannot edit another users product', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $user2->id]);
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $user2->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    $response = $this->actingAs($user1)->get("/products/{$product->id}/edit");
    $response->assertStatus(403);

    // Provide valid data to test authorization, not validation
    $updateData = [
        'product_name' => 'Hacked Name',
        'description' => 'Hacked description',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'unit_price' => 100.00,
        'cost_price' => 75.00,
        'status' => 'active',
    ];

    $response = $this->actingAs($user1)->put("/products/{$product->id}", $updateData);
    $response->assertStatus(404);
});

test('product update requires valid data', function () {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    $response = $this->actingAs($user)->put("/products/{$product->id}", []);

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
