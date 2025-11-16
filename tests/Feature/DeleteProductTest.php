<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SalesItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can delete a product that has no sales items', function () {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    $response = $this->actingAs($user)->delete("/products/{$product->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Product deleted successfully!',
    ]);

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

test('user cannot delete a product that has sales items', function () {
    $user = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    // Create a sale with sales item referencing this product
    $sale = Sale::factory()->create(['user_id' => $user->id]);
    SalesItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
    ]);

    $response = $this->actingAs($user)->delete("/products/{$product->id}");

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Cannot delete this product because it has been used in sales transactions.',
    ]);

    // Product should still exist
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
    ]);
});

test('user cannot delete another users product', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $user2->id]);
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'user_id' => $user2->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    $response = $this->actingAs($user1)->delete("/products/{$product->id}");

    $response->assertStatus(403);

    // Product should still exist
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
    ]);
});
