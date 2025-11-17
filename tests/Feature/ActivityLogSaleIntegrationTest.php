<?php

use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs customer transaction when creating a sale with customer', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);
    $unit = Unit::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'unit_id' => $unit->id,
        'unit_price' => 100,
    ]);

    Activity::query()->delete();

    // Create a sale which also creates a customer transaction
    $sale = Sale::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'total_amount' => 100,
        'paid_amount' => 0,
        'payment_type' => 'utang',
    ]);

    // Create the customer transaction (as the controller would)
    $transaction = CustomerTransaction::create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'transaction_type' => 'sale',
        'reference_id' => $sale->id,
        'previous_balance' => 0,
        'new_balance' => 100,
        'transaction_desc' => $sale->invoice_number,
        'transaction_date' => $sale->transaction_date,
        'transaction_amount' => $sale->total_amount,
    ]);

    // Sale should NOT be logged
    $saleActivities = Activity::where('subject_type', Sale::class)
        ->where('subject_id', $sale->id)
        ->count();

    expect($saleActivities)->toBe(0);

    // CustomerTransaction SHOULD be logged
    $transactionActivity = Activity::where('subject_type', CustomerTransaction::class)
        ->where('subject_id', $transaction->id)
        ->where('event', 'created')
        ->first();

    expect($transactionActivity)->not->toBeNull()
        ->and($transactionActivity->properties->get('attributes')['transaction_type'])->toBe('sale')
        ->and($transactionActivity->properties->get('attributes')['transaction_amount'])->toBe('100.00')
        ->and($transactionActivity->properties->get('attributes')['reference_id'])->toBe($sale->id);
});

it('logs utang payment as customer transaction', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'has_utang' => true,
    ]);

    // Create initial transaction to establish balance
    CustomerTransaction::create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'transaction_type' => 'starting_balance',
        'previous_balance' => 0,
        'new_balance' => 1000,
        'transaction_desc' => 'Starting Balance',
        'transaction_date' => now(),
        'transaction_amount' => 1000,
    ]);

    Activity::query()->delete();

    // Make a payment
    $payment = CustomerTransaction::create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'transaction_type' => 'utang_payment',
        'previous_balance' => 1000,
        'new_balance' => 500,
        'transaction_desc' => 'Payment received',
        'transaction_date' => now(),
        'transaction_amount' => 500,
    ]);

    // Should be logged
    $activity = Activity::where('subject_type', CustomerTransaction::class)
        ->where('subject_id', $payment->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('attributes')['transaction_type'])->toBe('utang_payment')
        ->and($activity->properties->get('attributes')['transaction_amount'])->toBe('500.00')
        ->and($activity->properties->get('attributes')['new_balance'])->toBe('500.00');
});

it('tracks all transaction types correctly', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);

    Activity::query()->delete();

    $transactionTypes = [
        'starting_balance' => 1000,
        'utang_payment' => -500,
        'monthly_interest' => 50,
        'balance_update' => 100,
    ];

    $balance = 0;
    foreach ($transactionTypes as $type => $amount) {
        $previousBalance = $balance;
        $balance += $amount;

        CustomerTransaction::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'transaction_type' => $type,
            'previous_balance' => $previousBalance,
            'new_balance' => $balance,
            'transaction_desc' => ucwords(str_replace('_', ' ', $type)),
            'transaction_date' => now(),
            'transaction_amount' => abs($amount),
        ]);
    }

    // All should be logged
    $activities = Activity::where('subject_type', CustomerTransaction::class)->get();

    expect($activities)->toHaveCount(4);

    foreach ($transactionTypes as $type => $amount) {
        $activity = $activities->firstWhere(function ($a) use ($type) {
            return $a->properties->get('attributes')['transaction_type'] === $type;
        });

        expect($activity)->not->toBeNull()
            ->and($activity->event)->toBe('created');
    }
});
