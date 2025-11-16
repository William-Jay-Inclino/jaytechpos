<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a sale with date and time', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $transactionDateTime = '2025-11-16 14:30:00';

    $response = $this->postJson('/sales', [
        'customer_id' => $customer->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 100.00,
            ],
        ],
        'total_amount' => 200.00,
        'paid_amount' => 200.00,
        'amount_tendered' => 200.00,
        'payment_type' => 'cash',
        'notes' => null,
        'transaction_date' => $transactionDateTime,
        'deduct_from_balance' => 0,
    ]);

    $response->assertSuccessful();

    $sale = Sale::first();
    expect($sale)->not->toBeNull();
    expect($sale->transaction_date->format('Y-m-d H:i:s'))->toBe($transactionDateTime);
});

it('can create a sale with date only (defaults to 00:00:00 time)', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $transactionDate = '2025-11-16';

    $response = $this->postJson('/sales', [
        'customer_id' => $customer->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 50.00,
            ],
        ],
        'total_amount' => 50.00,
        'paid_amount' => 50.00,
        'amount_tendered' => 50.00,
        'payment_type' => 'cash',
        'notes' => null,
        'transaction_date' => $transactionDate,
        'deduct_from_balance' => 0,
    ]);

    $response->assertSuccessful();

    $sale = Sale::first();
    expect($sale)->not->toBeNull();
    expect($sale->transaction_date->format('Y-m-d'))->toBe($transactionDate);
});

it('validates transaction_date format', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $response = $this->postJson('/sales', [
        'customer_id' => $customer->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 50.00,
            ],
        ],
        'total_amount' => 50.00,
        'paid_amount' => 50.00,
        'amount_tendered' => 50.00,
        'payment_type' => 'cash',
        'notes' => null,
        'transaction_date' => 'invalid-date',
        'deduct_from_balance' => 0,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['transaction_date']);
});
