<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('products index returns products with correct structure', function () {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'product_name' => 'Test Product',
    ]);

    $response = $this->actingAs($user)->get('/products');

    $response->assertStatus(200);
    $response->assertInertia(fn ($assert) => $assert
        ->component('products/Index')
        ->has('products')
        ->has('categories')
    );
});

test('products index only shows products for authenticated user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $category1 = ProductCategory::factory()->create(['user_id' => $user1->id]);
    $category2 = ProductCategory::factory()->create(['user_id' => $user2->id]);
    $unit = Unit::factory()->create();

    Product::factory()->create([
        'user_id' => $user1->id,
        'category_id' => $category1->id,
        'unit_id' => $unit->id,
        'product_name' => 'User 1 Product',
    ]);

    Product::factory()->create([
        'user_id' => $user2->id,
        'category_id' => $category2->id,
        'unit_id' => $unit->id,
        'product_name' => 'User 2 Product',
    ]);

    $response = $this->actingAs($user1)->get('/products');

    $response->assertStatus(200);
    $response->assertInertia(fn ($assert) => $assert
        ->component('products/Index')
        ->has('products', 1)
    );
});
