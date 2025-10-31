<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Customer Scopes', function () {
    beforeEach(function () {
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();

        // Create customers for user1
        $this->customer1WithUtang = Customer::factory()->create([
            'user_id' => $this->user1->id,
            'name' => 'Customer 1 With Utang',
            'has_utang' => true,
        ]);

        $this->customer1WithoutUtang = Customer::factory()->create([
            'user_id' => $this->user1->id,
            'name' => 'Customer 1 Without Utang',
            'has_utang' => false,
        ]);

        // Create customers for user2
        $this->customer2WithUtang = Customer::factory()->create([
            'user_id' => $this->user2->id,
            'name' => 'Customer 2 With Utang',
            'has_utang' => true,
        ]);
    });

    describe('ownedBy scope', function () {
        it('filters customers by user', function () {
            $user1Customers = Customer::ownedBy($this->user1->id)->get();
            $user2Customers = Customer::ownedBy($this->user2->id)->get();

            expect($user1Customers)->toHaveCount(2);
            expect($user2Customers)->toHaveCount(1);

            expect($user1Customers->pluck('user_id')->unique()->toArray())->toEqual([$this->user1->id]);
            expect($user2Customers->pluck('user_id')->unique()->toArray())->toEqual([$this->user2->id]);
        });

        it('uses authenticated user when no user ID provided', function () {
            $this->actingAs($this->user1);

            $userCustomers = Customer::ownedBy()->get();

            expect($userCustomers)->toHaveCount(2);
            expect($userCustomers->pluck('user_id')->unique()->toArray())->toEqual([$this->user1->id]);
        });
    });

    describe('withUtang scope', function () {
        it('filters customers with utang only', function () {
            $customersWithUtang = Customer::withUtang()->get();

            expect($customersWithUtang)->toHaveCount(2);
            expect($customersWithUtang->pluck('has_utang')->unique()->toArray())->toEqual([true]);
        });
    });

    describe('withoutUtang scope', function () {
        it('filters customers without utang only', function () {
            $customersWithoutUtang = Customer::withoutUtang()->get();

            expect($customersWithoutUtang)->toHaveCount(1);
            expect($customersWithoutUtang->pluck('has_utang')->unique()->toArray())->toEqual([false]);
        });
    });

    describe('combined scopes', function () {
        it('can combine ownedBy with withUtang', function () {
            $this->actingAs($this->user1);

            $user1CustomersWithUtang = Customer::ownedBy()->withUtang()->get();

            expect($user1CustomersWithUtang)->toHaveCount(1);
            expect($user1CustomersWithUtang->first()->id)->toBe($this->customer1WithUtang->id);
        });

        it('can combine ownedBy with withoutUtang', function () {
            $this->actingAs($this->user1);

            $user1CustomersWithoutUtang = Customer::ownedBy()->withoutUtang()->get();

            expect($user1CustomersWithoutUtang)->toHaveCount(1);
            expect($user1CustomersWithoutUtang->first()->id)->toBe($this->customer1WithoutUtang->id);
        });
    });
});
