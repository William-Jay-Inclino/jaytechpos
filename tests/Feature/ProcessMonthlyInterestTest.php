<?php

use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\User;
use App\Services\CustomerService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('skips customers who already have monthly interest this month', function () {
    // Create a customer with utang
    $customer = Customer::factory()->create([
        'has_utang' => true,
    ]);

    // Create an existing monthly_interest transaction for current month
    $customer->customerTransactions()->create([
        'user_id' => $customer->user_id,
        'transaction_type' => 'monthly_interest',
        'reference_id' => null,
        'previous_balance' => 100.00,
        'new_balance' => 103.00,
        'transaction_desc' => 'Existing monthly interest',
        'transaction_date' => now(),
        'transaction_amount' => 3.00,
    ]);

    $service = new CustomerService;

    $created = $service->processMonthlyInterest();

    expect($created->count())->toBe(0);
    expect($customer->customerTransactions()->where('transaction_type', 'monthly_interest')->count())->toBe(1);
});

it('applies monthly interest when no existing monthly interest and positive balance', function () {
    // Create a customer with utang and a positive running balance via a prior transaction
    $customer = Customer::factory()->create([
        'has_utang' => true,
        // Try to set an interest rate; service will normalize >1 values as percents
        'interest_rate' => 0.05,
    ]);

    // Seed a prior transaction so running_utang_balance is positive.
    $customer->customerTransactions()->create([
        'user_id' => $customer->user_id,
        'transaction_type' => 'starting_balance',
        'reference_id' => null,
        'previous_balance' => 0.00,
        'new_balance' => 1000.00,
        'transaction_desc' => 'Initial balance',
        'transaction_date' => now()->subMonth(),
        'transaction_amount' => 1000.00,
    ]);

    $service = new CustomerService;

    $created = $service->processMonthlyInterest();

    expect($created->count())->toBe(1);
    $txn = $created->first();
    expect($txn->transaction_type)->toBe('monthly_interest');
    expect($txn->transaction_amount)->toBeGreaterThan(0.0);
});

it('applies monthly interest to customers with utang and logs an audit activity', function () {
    $user = User::factory()->create();

    // Create a customer with outstanding balance and a specific interest rate
    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'has_utang' => true,
        'interest_rate' => 0.05, // 5%
    ]);

    // Create initial transaction to establish a running balance of 1000.00
    $starting = CustomerTransaction::create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'transaction_type' => 'starting_balance',
        'previous_balance' => 0,
        'new_balance' => 1000,
        'transaction_desc' => 'Starting Balance',
        'transaction_date' => now(),
        'transaction_amount' => 1000,
    ]);

    // Clear activities to ensure only the audit entry is present after run
    Activity::query()->delete();

    $created = app(\App\Services\CustomerService::class)->processMonthlyInterest();

    // One transaction should be created for this customer
    expect($created)->toHaveCount(1);

    $transaction = CustomerTransaction::where('customer_id', $customer->id)
        ->where('transaction_type', 'monthly_interest')
        ->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->transaction_amount)->toBe('50.00')
        ->and($transaction->previous_balance)->toBe('1000.00')
        ->and($transaction->new_balance)->toBe('1050.00');

    // Audit activity should exist and contain summary properties
    $activity = Activity::where('description', 'Monthly interest processing completed')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('processed'))->toBe(1)
        ->and($activity->properties->get('total_customers'))->toBe(1)
        ->and($activity->properties->get('skipped_already_applied'))->toBe(0)
        ->and($activity->properties->get('skipped_zero_interest'))->toBe(0)
        ->and(in_array($transaction->id, $activity->properties->get('created_transaction_ids')))->toBeTrue();
});
