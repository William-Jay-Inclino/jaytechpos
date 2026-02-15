<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('copies all products from source user to target user', function () {
    $sourceUser = User::factory()->create();
    $targetUser = User::factory()->create();

    $products = Product::factory()->count(3)->create([
        'user_id' => $sourceUser->id,
        'status' => 'active',
    ]);

    $this->artisan('products:copy', ['from' => $sourceUser->id, 'to' => $targetUser->id])
        ->assertSuccessful()
        ->expectsOutputToContain('Copied 3 product(s)');

    expect(Product::where('user_id', $targetUser->id)->count())->toBe(3);

    foreach ($products as $product) {
        $copiedProduct = Product::where('user_id', $targetUser->id)
            ->where('product_name', $product->product_name)
            ->first();

        expect($copiedProduct)->not->toBeNull();
        expect($copiedProduct->unit_id)->toBe($product->unit_id);
        expect($copiedProduct->unit_price)->toEqual($product->unit_price);
        expect($copiedProduct->cost_price)->toEqual($product->cost_price);
        expect($copiedProduct->description)->toBe($product->description);
        expect($copiedProduct->status)->toBe($product->status);
    }
});

it('skips products that already exist for target user', function () {
    $sourceUser = User::factory()->create();
    $targetUser = User::factory()->create();

    Product::factory()->create([
        'user_id' => $sourceUser->id,
        'product_name' => 'Shared Product',
    ]);

    Product::factory()->create([
        'user_id' => $targetUser->id,
        'product_name' => 'Shared Product',
    ]);

    $uniqueProduct = Product::factory()->create([
        'user_id' => $sourceUser->id,
        'product_name' => 'Unique Product',
    ]);

    $this->artisan('products:copy', ['from' => $sourceUser->id, 'to' => $targetUser->id])
        ->assertSuccessful()
        ->expectsOutputToContain('Copied 1 product(s)')
        ->expectsOutputToContain('Skipped 1 product(s)');

    expect(Product::where('user_id', $targetUser->id)->count())->toBe(2);
});

it('skips products with case-insensitive name matching', function () {
    $sourceUser = User::factory()->create();
    $targetUser = User::factory()->create();

    Product::factory()->create([
        'user_id' => $sourceUser->id,
        'product_name' => 'Rice',
    ]);

    Product::factory()->create([
        'user_id' => $targetUser->id,
        'product_name' => 'rice',
    ]);

    $this->artisan('products:copy', ['from' => $sourceUser->id, 'to' => $targetUser->id])
        ->assertSuccessful()
        ->expectsOutputToContain('Skipped 1 product(s)');

    expect(Product::where('user_id', $targetUser->id)->count())->toBe(1);
});

it('fails when source user does not exist', function () {
    $targetUser = User::factory()->create();

    $this->artisan('products:copy', ['from' => 99999, 'to' => $targetUser->id])
        ->assertFailed()
        ->expectsOutputToContain('Source user with ID 99999 not found');
});

it('fails when target user does not exist', function () {
    $sourceUser = User::factory()->create();

    $this->artisan('products:copy', ['from' => $sourceUser->id, 'to' => 99999])
        ->assertFailed()
        ->expectsOutputToContain('Target user with ID 99999 not found');
});

it('fails when source and target user are the same', function () {
    $user = User::factory()->create();

    $this->artisan('products:copy', ['from' => $user->id, 'to' => $user->id])
        ->assertFailed()
        ->expectsOutputToContain('Source and target user cannot be the same');
});

it('succeeds with warning when source user has no products', function () {
    $sourceUser = User::factory()->create();
    $targetUser = User::factory()->create();

    $this->artisan('products:copy', ['from' => $sourceUser->id, 'to' => $targetUser->id])
        ->assertSuccessful()
        ->expectsOutputToContain('No products found');
});

it('does not modify source user products', function () {
    $sourceUser = User::factory()->create();
    $targetUser = User::factory()->create();

    Product::factory()->count(2)->create(['user_id' => $sourceUser->id]);

    $this->artisan('products:copy', ['from' => $sourceUser->id, 'to' => $targetUser->id])
        ->assertSuccessful();

    expect(Product::where('user_id', $sourceUser->id)->count())->toBe(2);
});
