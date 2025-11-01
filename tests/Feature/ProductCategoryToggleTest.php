<?php

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it correctly toggles default category via API', function () {
    $user = User::factory()->create();

    // Create three categories, first one is default
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

    $category3 = ProductCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Category 3',
        'is_default' => false,
    ]);

    // Verify initial state
    expect($category1->fresh()->is_default)->toBeTrue();
    expect($category2->fresh()->is_default)->toBeFalse();
    expect($category3->fresh()->is_default)->toBeFalse();

    // Toggle category 2 to be default (simulating frontend toggle)
    $response = $this->actingAs($user)->putJson("/api/product-categories/{$category2->id}", [
        'name' => $category2->name,
        'description' => $category2->description,
        'status' => $category2->status,
        'is_default' => true, // Toggle to true
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);

    // Verify final state - only category 2 should be default
    expect($category1->fresh()->is_default)->toBeFalse();
    expect($category2->fresh()->is_default)->toBeTrue();
    expect($category3->fresh()->is_default)->toBeFalse();

    // Verify only one default exists for this user
    $defaultCount = ProductCategory::where('user_id', $user->id)
        ->where('is_default', true)
        ->count();

    expect($defaultCount)->toBe(1);
});

test('it correctly unsets default when toggling to false', function () {
    $user = User::factory()->create();

    $category = ProductCategory::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
    ]);

    // Toggle default to false
    $response = $this->actingAs($user)->putJson("/api/product-categories/{$category->id}", [
        'name' => $category->name,
        'description' => $category->description,
        'status' => $category->status,
        'is_default' => false,
    ]);

    $response->assertStatus(200);
    expect($category->fresh()->is_default)->toBeFalse();

    // No default categories should exist
    $defaultCount = ProductCategory::where('user_id', $user->id)
        ->where('is_default', true)
        ->count();

    expect($defaultCount)->toBe(0);
});

test('multiple users can have their own default categories', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create default category for user 1
    $category1 = ProductCategory::factory()->create([
        'user_id' => $user1->id,
        'is_default' => true,
    ]);

    // Create default category for user 2
    $category2 = ProductCategory::factory()->create([
        'user_id' => $user2->id,
        'is_default' => true,
    ]);

    // Both should remain default since they're for different users
    expect($category1->fresh()->is_default)->toBeTrue();
    expect($category2->fresh()->is_default)->toBeTrue();

    // Each user should have exactly one default
    $user1Defaults = ProductCategory::where('user_id', $user1->id)
        ->where('is_default', true)
        ->count();

    $user2Defaults = ProductCategory::where('user_id', $user2->id)
        ->where('is_default', true)
        ->count();

    expect($user1Defaults)->toBe(1);
    expect($user2Defaults)->toBe(1);
});
