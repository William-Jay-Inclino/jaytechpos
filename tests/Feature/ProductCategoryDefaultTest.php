<?php

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it can create a default category', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/product-categories', [
        'name' => 'Default Category',
        'description' => 'This is the default category',
        'status' => 'active',
        'is_default' => true,
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
        'message' => 'Category created successfully!',
    ]);

    $category = ProductCategory::where('name', 'Default Category')->first();
    expect($category->is_default)->toBeTrue();
});

test('only one default category is allowed per user', function () {
    $user = User::factory()->create();

    // Create first default category
    $this->actingAs($user)->postJson('/api/product-categories', [
        'name' => 'First Default',
        'status' => 'active',
        'is_default' => true,
    ]);

    // Create second default category
    $this->actingAs($user)->postJson('/api/product-categories', [
        'name' => 'Second Default',
        'status' => 'active',
        'is_default' => true,
    ]);

    $defaultCategories = ProductCategory::where('user_id', $user->id)
        ->where('is_default', true)
        ->get();

    expect($defaultCategories)->toHaveCount(1);
    expect($defaultCategories->first()->name)->toBe('Second Default');
});

test('it can update a category to be default', function () {
    $user = User::factory()->create();

    // Create two categories
    $category1 = ProductCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Category 1',
        'is_default' => true,
    ]);

    $category2 = ProductCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Category 2',
        'is_default' => false,
    ]);

    // Update second category to be default
    $response = $this->actingAs($user)->putJson("/api/product-categories/{$category2->id}", [
        'name' => 'Category 2',
        'status' => 'active',
        'is_default' => true,
    ]);

    $response->assertStatus(200);

    // Refresh models
    $category1->refresh();
    $category2->refresh();

    expect($category1->is_default)->toBeFalse();
    expect($category2->is_default)->toBeTrue();
});

test('default scope works correctly', function () {
    $user = User::factory()->create();

    ProductCategory::factory()->create([
        'user_id' => $user->id,
        'is_default' => false,
    ]);

    $defaultCategory = ProductCategory::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
    ]);

    $result = ProductCategory::default($user->id)->first();

    expect($result->id)->toBe($defaultCategory->id);
});
