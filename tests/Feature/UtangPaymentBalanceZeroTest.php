<?php

use App\Models\Customer;
use App\Models\UtangTracking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('sets has_utang to false when payment makes balance zero', function () {
    // Create a customer with utang
    $customer = Customer::factory()->create([
        'user_id' => $this->user->id,
        'has_utang' => true,
    ]);

    // Create utang tracking with a balance
    $utangTracking = UtangTracking::factory()->create([
        'user_id' => $this->user->id,
        'customer_id' => $customer->id,
        'beginning_balance' => 500.00,
        'computation_date' => now()->startOfMonth(),
        'interest_rate' => 3.0,
    ]);

    // Record a payment that exactly matches the balance
    $response = $this->post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 500.00,
        'payment_date' => now()->format('Y-m-d H:i'),
        'notes' => 'Full payment',
    ]);

    $response->assertRedirect('/utang-payments');
    $response->assertSessionHas('message', 'Payment recorded successfully!');

    // Refresh the customer model
    $customer->refresh();

    // Assert has_utang is now false
    expect($customer->has_utang)->toBeFalse();

    // Assert the utang tracking balance is updated
    $utangTracking->refresh();
    expect($utangTracking->beginning_balance)->toBe(0.0);
});

it('sets has_utang to false when payment exceeds balance', function () {
    // Create a customer with utang
    $customer = Customer::factory()->create([
        'user_id' => $this->user->id,
        'has_utang' => true,
    ]);

    // Create utang tracking with a balance
    UtangTracking::factory()->create([
        'user_id' => $this->user->id,
        'customer_id' => $customer->id,
        'beginning_balance' => 300.00,
        'computation_date' => now()->startOfMonth(),
        'interest_rate' => 3.0,
    ]);

    // Record a payment that exceeds the balance
    $response = $this->post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 400.00,
        'payment_date' => now()->format('Y-m-d H:i'),
        'notes' => 'Overpayment',
    ]);

    $response->assertRedirect('/utang-payments');

    // Refresh the customer model
    $customer->refresh();

    // Assert has_utang is now false
    expect($customer->has_utang)->toBeFalse();
});

it('keeps has_utang true when balance remains positive', function () {
    // Create a customer with utang
    $customer = Customer::factory()->create([
        'user_id' => $this->user->id,
        'has_utang' => true,
    ]);

    // Create utang tracking with a balance
    UtangTracking::factory()->create([
        'user_id' => $this->user->id,
        'customer_id' => $customer->id,
        'beginning_balance' => 1000.00,
        'computation_date' => now()->startOfMonth(),
        'interest_rate' => 3.0,
    ]);

    // Record a partial payment
    $response = $this->post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 300.00,
        'payment_date' => now()->format('Y-m-d H:i'),
        'notes' => 'Partial payment',
    ]);

    $response->assertRedirect('/utang-payments');

    // Refresh the customer model
    $customer->refresh();

    // Assert has_utang remains true
    expect($customer->has_utang)->toBeTrue();
});
