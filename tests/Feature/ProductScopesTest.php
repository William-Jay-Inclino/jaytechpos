<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Product Scopes', function () {
    beforeEach(function () {
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();

        // Create products for user1
        $this->activeProduct1 = Product::factory()->create([
            'user_id' => $this->user1->id,
            'status' => 'active',
            'product_name' => 'Active Product 1',
        ]);

        $this->inactiveProduct1 = Product::factory()->create([
            'user_id' => $this->user1->id,
            'status' => 'inactive',
            'product_name' => 'Inactive Product 1',
        ]);

        // Create products for user2
        $this->activeProduct2 = Product::factory()->create([
            'user_id' => $this->user2->id,
            'status' => 'active',
            'product_name' => 'Active Product 2',
        ]);
    });

    it('filters active products only with active scope', function () {
        $activeProducts = Product::active()->get();

        expect($activeProducts)->toHaveCount(2);
        expect($activeProducts->pluck('status')->unique()->toArray())->toEqual(['active']);
    });

    it('filters products by user with ownedBy scope', function () {
        $user1Products = Product::ownedBy($this->user1->id)->get();
        $user2Products = Product::ownedBy($this->user2->id)->get();

        expect($user1Products)->toHaveCount(2);
        expect($user2Products)->toHaveCount(1);

        expect($user1Products->pluck('user_id')->unique()->toArray())->toEqual([$this->user1->id]);
        expect($user2Products->pluck('user_id')->unique()->toArray())->toEqual([$this->user2->id]);
    });

    it('uses authenticated user when no user ID provided in ownedBy scope', function () {
        $this->actingAs($this->user1);

        $userProducts = Product::ownedBy()->get();

        expect($userProducts)->toHaveCount(2);
        expect($userProducts->pluck('user_id')->unique()->toArray())->toEqual([$this->user1->id]);
    });

    it('combines active and owned filters with availableForSale scope', function () {
        $this->actingAs($this->user1);

        $availableProducts = Product::availableForSale()->get();

        expect($availableProducts)->toHaveCount(1);
        expect($availableProducts->first()->id)->toBe($this->activeProduct1->id);
        expect($availableProducts->first()->status)->toBe('active');
        expect($availableProducts->first()->user_id)->toBe($this->user1->id);
    });

    it('filters availableForSale by specific user', function () {
        $user2AvailableProducts = Product::availableForSale($this->user2->id)->get();

        expect($user2AvailableProducts)->toHaveCount(1);
        expect($user2AvailableProducts->first()->id)->toBe($this->activeProduct2->id);
        expect($user2AvailableProducts->first()->user_id)->toBe($this->user2->id);
    });

    it('returns empty collection when user has no active products', function () {
        // Update all user1 products to inactive
        Product::ownedBy($this->user1->id)->update(['status' => 'inactive']);

        $availableProducts = Product::availableForSale($this->user1->id)->get();

        expect($availableProducts)->toHaveCount(0);
    });
});
