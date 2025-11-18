<?php

use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
