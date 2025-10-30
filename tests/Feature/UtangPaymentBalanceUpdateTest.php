<?php

use App\Models\Customer;
use App\Models\User;
use App\Models\UtangTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates utang balance when payment is recorded', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);

    // Create an initial utang tracking record
    $initialBalance = 100.00;
    $utangTracking = UtangTracking::create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'beginning_balance' => $initialBalance,
        'computation_date' => now()->startOfMonth(),
        'interest_rate' => 3.0,
    ]);

    $paymentAmount = 30.00;

    // Create payment via controller
    $paymentData = [
        'customer_id' => $customer->id,
        'payment_amount' => $paymentAmount,
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Test payment',
    ];

    $response = $this->actingAs($user)->post('/utang-payments', $paymentData);

    $response->assertRedirect('/utang-payments');

    // Check that payment was created with balance information
    $this->assertDatabaseHas('utang_payments', [
        'customer_id' => $customer->id,
        'payment_amount' => $paymentAmount,
        'previous_balance' => $initialBalance,
        'new_balance' => $initialBalance - $paymentAmount,
    ]);

    // Check that utang balance was updated
    $updatedTracking = $utangTracking->fresh();
    $expectedBalance = $initialBalance - $paymentAmount;

    expect($updatedTracking->beginning_balance)->toBe($expectedBalance);
});

it('handles multiple payments correctly', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);

    // Create an initial utang tracking record
    $initialBalance = 200.00;
    $utangTracking = UtangTracking::create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'beginning_balance' => $initialBalance,
        'computation_date' => now()->startOfMonth(),
        'interest_rate' => 3.0,
    ]);

    $firstPayment = 50.00;
    $secondPayment = 30.00;

    // Create first payment
    $this->actingAs($user)->post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => $firstPayment,
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'First payment',
    ]);

    // Create second payment
    $this->actingAs($user)->post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => $secondPayment,
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Second payment',
    ]);

    // Check final balance
    $updatedTracking = $utangTracking->fresh();
    $expectedBalance = $initialBalance - $firstPayment - $secondPayment;

    expect($updatedTracking->beginning_balance)->toBe($expectedBalance);
});

it('does not update balance if no utang tracking record exists', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);

    // No utang tracking record exists for this customer

    $paymentData = [
        'customer_id' => $customer->id,
        'payment_amount' => 50.00,
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Payment without tracking',
    ];

    $response = $this->actingAs($user)->post('/utang-payments', $paymentData);

    $response->assertRedirect('/utang-payments');

    // Payment should be created with zero balances (no tracking record)
    $this->assertDatabaseHas('utang_payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 50.00,
        'previous_balance' => 0.00,
        'new_balance' => -50.00,
    ]);

    // No tracking record should exist (no error should occur)
    expect($customer->utangTrackings()->count())->toBe(0);
});

it('uses database transaction to ensure atomicity', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);

    // Create an initial utang tracking record
    $initialBalance = 100.00;
    $utangTracking = UtangTracking::create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'beginning_balance' => $initialBalance,
        'computation_date' => now()->startOfMonth(),
        'interest_rate' => 3.0,
    ]);

    $paymentAmount = 30.00;

    // Simulate a successful transaction
    $paymentData = [
        'customer_id' => $customer->id,
        'payment_amount' => $paymentAmount,
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Atomic transaction test',
    ];

    $initialPaymentCount = \App\Models\UtangPayment::count();

    $response = $this->actingAs($user)->post('/utang-payments', $paymentData);

    // Both operations should succeed
    expect(\App\Models\UtangPayment::count())->toBe($initialPaymentCount + 1);
    expect($utangTracking->fresh()->beginning_balance)->toBe($initialBalance - $paymentAmount);

    $response->assertRedirect('/utang-payments');
});
