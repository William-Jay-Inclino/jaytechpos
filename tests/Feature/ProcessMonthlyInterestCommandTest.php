<?php

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Set default interest rate for testing
    Setting::updateOrCreate(
        ['key' => 'default_utang_interest_rate'],
        ['value' => '3']
    );
});

it('executes the utang:process-monthly-tracking command successfully', function () {
    $customer = Customer::factory()
        ->withUtang()
        ->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subMonths(2),
        ]);

    // Create an initial transaction
    CustomerTransaction::factory()->create([
        'customer_id' => $customer->id,
        'user_id' => $this->user->id,
        'transaction_type' => 'sale',
        'previous_balance' => 0,
        'new_balance' => 1000.00,
        'transaction_amount' => 1000.00,
        'transaction_date' => now()->subMonth(),
    ]);

    $exitCode = Artisan::call('utang:process-monthly-tracking');

    expect($exitCode)->toBe(0);

    // Verify interest transaction was created
    $interestTransactions = CustomerTransaction::where('transaction_type', 'monthly_interest')
        ->where('customer_id', $customer->id)
        ->get();

    expect($interestTransactions)->toHaveCount(1);

    $transaction = $interestTransactions->first();
    expect((float) $transaction->previous_balance)->toBe(1000.00);
    expect((float) $transaction->transaction_amount)->toBe(30.00); // 3% interest
    expect((float) $transaction->new_balance)->toBe(1030.00);
});

it('processes multiple customers with utang', function () {
    $customers = Customer::factory()
        ->count(3)
        ->withUtang()
        ->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subMonths(2),
        ]);

    // Create initial transactions for each customer
    foreach ($customers as $customer) {
        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'transaction_type' => 'sale',
            'previous_balance' => 0,
            'new_balance' => 1000.00,
            'transaction_amount' => 1000.00,
            'transaction_date' => now()->subMonth(),
        ]);
    }

    $exitCode = Artisan::call('utang:process-monthly-tracking');

    expect($exitCode)->toBe(0);

    // Verify interest transactions were created for all customers
    $interestTransactions = CustomerTransaction::where('transaction_type', 'monthly_interest')->get();
    expect($interestTransactions)->toHaveCount(3);
});

it('skips customers without utang', function () {
    $customerWithUtang = Customer::factory()
        ->withUtang()
        ->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subMonths(2),
        ]);

    $customerWithoutUtang = Customer::factory()
        ->create([
            'user_id' => $this->user->id,
            'has_utang' => false,
            'created_at' => now()->subMonths(2),
        ]);

    // Create transaction for customer with utang
    CustomerTransaction::factory()->create([
        'customer_id' => $customerWithUtang->id,
        'user_id' => $this->user->id,
        'transaction_type' => 'sale',
        'previous_balance' => 0,
        'new_balance' => 1000.00,
        'transaction_amount' => 1000.00,
        'transaction_date' => now()->subMonth(),
    ]);

    $exitCode = Artisan::call('utang:process-monthly-tracking');

    expect($exitCode)->toBe(0);

    // Only one interest transaction should be created
    $interestTransactions = CustomerTransaction::where('transaction_type', 'monthly_interest')->get();
    expect($interestTransactions)->toHaveCount(1);
    expect($interestTransactions->first()->customer_id)->toBe($customerWithUtang->id);
});

it('outputs useful information during execution', function () {
    $customer = Customer::factory()
        ->withUtang()
        ->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subMonths(2),
        ]);

    CustomerTransaction::factory()->create([
        'customer_id' => $customer->id,
        'user_id' => $this->user->id,
        'transaction_type' => 'sale',
        'previous_balance' => 0,
        'new_balance' => 1000.00,
        'transaction_amount' => 1000.00,
        'transaction_date' => now()->subMonth(),
    ]);

    Artisan::call('utang:process-monthly-tracking');
    $output = Artisan::output();

    expect($output)
        ->toContain('Starting monthly interest processing')
        ->toContain('Total customers considered')
        ->toContain('Interest transactions created');
});
