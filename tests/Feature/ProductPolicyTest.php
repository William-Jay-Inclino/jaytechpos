<?php

use App\Models\Product;
use App\Models\SalesItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ProductPolicy', function () {
    beforeEach(function () {
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();

        $this->user1Product = Product::factory()->create([
            'user_id' => $this->user1->id,
        ]);

        $this->user2Product = Product::factory()->create([
            'user_id' => $this->user2->id,
        ]);
    });

    describe('viewAny', function () {
        it('allows authenticated users to view products list', function () {
            $this->actingAs($this->user1);

            expect($this->user1->can('viewAny', Product::class))->toBeTrue();
        });
    });

    describe('view', function () {
        it('allows users to view their own products', function () {
            $this->actingAs($this->user1);

            expect($this->user1->can('view', $this->user1Product))->toBeTrue();
        });

        it('denies users from viewing other users products', function () {
            $this->actingAs($this->user1);

            expect($this->user1->can('view', $this->user2Product))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows authenticated users to create products', function () {
            $this->actingAs($this->user1);

            expect($this->user1->can('create', Product::class))->toBeTrue();
        });
    });

    describe('update', function () {
        it('allows users to update their own products', function () {
            $this->actingAs($this->user1);

            expect($this->user1->can('update', $this->user1Product))->toBeTrue();
        });

        it('denies users from updating other users products', function () {
            $this->actingAs($this->user1);

            expect($this->user1->can('update', $this->user2Product))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows users to delete their own products when not used in sales', function () {
            $this->actingAs($this->user1);

            expect($this->user1->can('delete', $this->user1Product))->toBeTrue();
        });

        it('denies users from deleting other users products', function () {
            $this->actingAs($this->user1);

            expect($this->user1->can('delete', $this->user2Product))->toBeFalse();
        });

        it('allows deletion attempt even when product has sales items - controller handles business logic', function () {
            $this->actingAs($this->user1);

            // Create a sales item for the product
            SalesItem::factory()->create([
                'product_id' => $this->user1Product->id,
            ]);

            // Refresh the product to ensure relationship is loaded
            $this->user1Product->refresh();

            // Policy allows the attempt (ownership check passes)
            // Business logic validation happens in controller
            expect($this->user1->can('delete', $this->user1Product))->toBeTrue();
        });
    });
});
