<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;

it('saves payment details when creating a cash sale', function () {
    // Create test data
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['user_id' => $user->id, 'unit_id' => $unit->id]);

    // Prepare sale data with payment details
    $saleData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 50.00,
            ],
        ],
        'total_amount' => 100.00,
        'paid_amount' => 80.00,
        'amount_tendered' => 120.00,
        'deduct_from_balance' => 20.00,
        'payment_type' => 'cash',
        'customer_id' => $customer->id,
        'transaction_date' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Test sale with payment details',
    ];

    // Create the sale
    $response = $this->actingAs($user)->post('/sales', $saleData);

    $response->assertSuccessful();

    // Verify the sale was created with correct payment details
    $sale = Sale::where('user_id', $user->id)->first();

    expect($sale)->not->toBeNull();
    expect($sale->amount_tendered)->toEqual('120.00');
    expect($sale->deduct_from_balance)->toEqual('20.00');
    expect($sale->total_amount)->toEqual('100.00');
    expect($sale->paid_amount)->toEqual('80.00');
    expect($sale->payment_type)->toEqual('cash');
});

it('saves payment details as null for utang sales when amount_tendered is not provided', function () {
    // Create test data
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['user_id' => $user->id, 'unit_id' => $unit->id]);

    // Prepare utang sale data without amount_tendered
    $saleData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 75.00,
            ],
        ],
        'total_amount' => 75.00,
        'paid_amount' => 0.00,
        'payment_type' => 'utang',
        'customer_id' => $customer->id,
        'transaction_date' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Test utang sale',
    ];

    // Create the sale
    $response = $this->actingAs($user)->post('/sales', $saleData);

    $response->assertSuccessful();

    // Verify the sale was created with correct payment details
    $sale = Sale::where('user_id', $user->id)->first();

    expect($sale)->not->toBeNull();
    expect($sale->amount_tendered)->toBeNull();
    expect($sale->deduct_from_balance)->toEqual('0.00');
    expect($sale->payment_type)->toEqual('utang');
});
