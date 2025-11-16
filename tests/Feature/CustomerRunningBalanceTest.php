<?php

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes running utang balance in customer resource', function () {
    // Create a user and customer
    $user = User::factory()->create();
    $this->actingAs($user);

    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Customer',
        'mobile_number' => '09123456789',
        'has_utang' => true,
    ]);

    // Create a customer transaction record to establish a balance
    CustomerTransaction::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'transaction_type' => 'sale',
        'transaction_date' => now()->startOfMonth(),
        'previous_balance' => 0.00,
        'new_balance' => 1500.00,
        'transaction_amount' => 1500.00,
        'transaction_desc' => 'Initial sale transaction',
    ]);

    // Transform customer using resource
    $resource = new CustomerResource($customer->fresh());
    $customerData = $resource->toArray(request());

    // Assert that running balance is included
    expect($customerData)
        ->toHaveKey('running_utang_balance')
        ->and($customerData['running_utang_balance'])
        ->toBe('1500.00');
});
