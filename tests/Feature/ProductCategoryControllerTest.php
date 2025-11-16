<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can get all product categories for authenticated user', function () {
    // Create categories for this user
    ProductCategory::factory()->count(3)->create(['user_id' => $this->user->id]);

    // Create category for another user (should not be returned)
    $otherUser = User::factory()->create();
    ProductCategory::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->getJson('/api/product-categories');

    $response->assertOk()
        ->assertJsonCount(3, 'categories')
        ->assertJson(['success' => true]);
});

it('can get only active product categories', function () {
    // Create active categories
    ProductCategory::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'status' => 'active',
    ]);

    // Create inactive category
    ProductCategory::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'inactive',
    ]);

    $response = $this->getJson('/api/product-categories/active');

    $response->assertOk()
        ->assertJsonCount(2, 'categories')
        ->assertJson(['success' => true]);

    // Only id and name are returned for active categories
    $categories = $response->json('categories');
    expect(count($categories))->toBe(2);
    foreach ($categories as $category) {
        expect($category)->toHaveKeys(['id', 'name']);
    }
});

it('can create a new product category', function () {
    $categoryData = [
        'name' => 'Test Category',
        'description' => 'Test Description',
        'status' => 'active',
    ];

    $response = $this->postJson('/api/product-categories', $categoryData);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Category created successfully!',
        ]);

    $this->assertDatabaseHas('product_categories', [
        'name' => 'Test Category',
        'user_id' => $this->user->id,
        'status' => 'active',
    ]);
});

it('validates required fields when creating category', function () {
    $response = $this->postJson('/api/product-categories', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'status']);
});

it('validates unique name when creating category', function () {
    // Create existing category
    ProductCategory::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Existing Category',
    ]);

    $response = $this->postJson('/api/product-categories', [
        'name' => 'Existing Category',
        'status' => 'active',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('can update a product category', function () {
    $category = ProductCategory::factory()->create(['user_id' => $this->user->id]);

    $updateData = [
        'name' => 'Updated Category Name',
        'description' => 'Updated Description',
        'status' => 'inactive',
    ];

    $response = $this->putJson("/api/product-categories/{$category->id}", $updateData);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Category updated successfully!',
        ]);

    $this->assertDatabaseHas('product_categories', [
        'id' => $category->id,
        'name' => 'Updated Category Name',
        'status' => 'inactive',
    ]);
});

it('cannot update category from another user', function () {
    $otherUser = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->putJson("/api/product-categories/{$category->id}", [
        'name' => 'Hacked Name',
    ]);

    $response->assertForbidden()
        ->assertJson([
            'success' => false,
            'message' => 'Unauthorized',
        ]);
});

it('can delete category without associated products', function () {
    $category = ProductCategory::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/product-categories/{$category->id}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Category deleted successfully!',
        ]);

    $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
});

it('cannot delete category with associated products', function () {
    $category = ProductCategory::factory()->create(['user_id' => $this->user->id]);

    // Create a unit and product
    $unit = Unit::factory()->create();
    Product::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    $response = $this->deleteJson("/api/product-categories/{$category->id}");

    $response->assertUnprocessable()
        ->assertJson([
            'success' => false,
            'message' => 'Cannot delete category with existing products',
        ]);

    $this->assertDatabaseHas('product_categories', ['id' => $category->id]);
});

it('cannot delete category from another user', function () {
    $otherUser = User::factory()->create();
    $category = ProductCategory::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->deleteJson("/api/product-categories/{$category->id}");

    $response->assertForbidden()
        ->assertJson([
            'success' => false,
            'message' => 'Unauthorized',
        ]);
});
