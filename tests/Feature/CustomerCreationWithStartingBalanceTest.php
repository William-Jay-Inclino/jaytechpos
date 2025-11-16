<?php

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('customer can be created without starting balance', function () {
    $customerData = [
        'name' => 'John Doe',
        'mobile_number' => '09123456789',
        'remarks' => 'Regular customer',
        'interest_rate' => 3.0,
    ];

    $response = $this->post('/customers', $customerData);

    $response->assertRedirect('/customers');

    $customer = Customer::where('name', 'John Doe')->first();
    expect($customer)->not->toBeNull();
    expect($customer->name)->toBe('John Doe');
    expect($customer->mobile_number)->toBe('09123456789');
    expect($customer->user_id)->toBe($this->user->id);

    // No transaction should be created
    expect(CustomerTransaction::where('customer_id', $customer->id)->count())->toBe(0);
});

test('customer can be created with zero starting balance', function () {
    $customerData = [
        'name' => 'Jane Doe',
        'mobile_number' => '09987654321',
        'starting_balance' => '0',
        'remarks' => 'New customer',
        'interest_rate' => 2.5,
    ];

    $response = $this->post('/customers', $customerData);

    $response->assertRedirect('/customers');

    $customer = Customer::where('name', 'Jane Doe')->first();
    expect($customer)->not->toBeNull();

    // No transaction should be created for zero balance
    expect(CustomerTransaction::where('customer_id', $customer->id)->count())->toBe(0);
});

test('customer can be created with starting balance and creates transaction record', function () {
    $customerData = [
        'name' => 'Bob Smith',
        'mobile_number' => '09111111111',
        'starting_balance' => '1500.50',
        'remarks' => 'Customer with debt',
        'interest_rate' => 4.0,
    ];

    $response = $this->post('/customers', $customerData);

    $response->assertRedirect('/customers');

    $customer = Customer::where('name', 'Bob Smith')->first();
    expect($customer)->not->toBeNull();
    expect($customer->name)->toBe('Bob Smith');
    expect($customer->user_id)->toBe($this->user->id);

    // Transaction should be created
    $transaction = CustomerTransaction::where('customer_id', $customer->id)->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->user_id)->toBe($this->user->id);
    expect($transaction->transaction_type)->toBe('starting_balance');
    expect($transaction->previous_balance)->toBe('0.00');
    expect($transaction->new_balance)->toBe('1500.50');
    expect($transaction->transaction_amount)->toBe('1500.50');
    expect($transaction->transaction_desc)->toBe('Starting Balance');
    expect($transaction->reference_id)->toBeNull();
});

test('starting balance transaction has correct transaction type', function () {
    $customerData = [
        'name' => 'Transaction Type Test',
        'mobile_number' => '09123456789',
        'starting_balance' => '500.25',
        'interest_rate' => 3.5,
    ];

    $response = $this->post('/customers', $customerData);

    $response->assertRedirect('/customers');

    $customer = Customer::where('name', 'Transaction Type Test')->first();
    expect($customer)->not->toBeNull();

    // Verify transaction type is exactly 'starting_balance'
    $transaction = CustomerTransaction::where('customer_id', $customer->id)->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->transaction_type)->toBe('starting_balance');
    expect($transaction->transaction_desc)->toBe('Starting Balance');
});

test('starting balance validation works correctly', function () {
    // Test negative starting balance
    $response = $this->post('/customers', [
        'name' => 'Test User',
        'starting_balance' => '-100',
    ]);

    $response->assertSessionHasErrors('starting_balance');

    // Test non-numeric starting balance
    $response = $this->post('/customers', [
        'name' => 'Test User',
        'starting_balance' => 'invalid',
    ]);

    $response->assertSessionHasErrors('starting_balance');
});
