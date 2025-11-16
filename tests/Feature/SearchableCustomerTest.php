<?php

use App\Models\Customer;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('sales page renders with customers for search', function () {
    $user = User::factory()->create();
    Customer::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/sales');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('sales/Index')
        ->has('customers', 3)
    );
});

test('utang payments page renders with customers for search', function () {
    $user = User::factory()->create();
    Customer::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/utang-payments');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('utang-payments/Index')
        ->has('customers', 3)
    );
});
