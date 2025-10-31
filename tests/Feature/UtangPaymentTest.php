<?php

use App\Models\Customer;
use App\Models\User;
use App\Models\UtangPayment;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user can view utang payments page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/utang-payments');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('utang-payments/Index')
        ->has('customers')
    );
});

test('user can create utang payment', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);

    $paymentData = [
        'customer_id' => $customer->id,
        'payment_amount' => 500.00,
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Test payment notes',
    ];

    $response = $this->actingAs($user)->post('/utang-payments', $paymentData);

    $response->assertRedirect('/utang-payments');
    $response->assertSessionHas('message', 'Payment recorded successfully!');

    $this->assertDatabaseHas('utang_payments', [
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'payment_amount' => 500.00,
        'notes' => 'Test payment notes',
    ]);
});

test('user can only access their own customers', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCustomer = Customer::factory()->create(['user_id' => $otherUser->id]);

    $paymentData = [
        'customer_id' => $otherCustomer->id,
        'payment_amount' => 500.00,
        'payment_date' => now()->format('Y-m-d\TH:i'),
    ];

    $response = $this->actingAs($user)->post('/utang-payments', $paymentData);

    $response->assertSessionHasErrors('customer_id');
    $this->assertDatabaseMissing('utang_payments', [
        'customer_id' => $otherCustomer->id,
    ]);
});

test('user can get customer transaction history', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);

    // Create some test data
    UtangPayment::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
    ]);

    $response = $this->actingAs($user)->get("/api/customers/{$customer->id}/transactions");

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'transactions' => [
            '*' => [
                'id',
                'type',
                'date',
                'amount',
                'formatted_amount',
                'description',
            ],
        ],
    ]);
});

test('user cannot access other users customer transactions', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCustomer = Customer::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->get("/api/customers/{$otherCustomer->id}/transactions");

    $response->assertForbidden();
});
