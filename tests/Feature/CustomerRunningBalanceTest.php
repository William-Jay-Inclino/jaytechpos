<?php

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use App\Models\UtangTracking;
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

    // Create a utang tracking record for current month
    UtangTracking::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'beginning_balance' => 1500.00,
        'computation_date' => now()->startOfMonth(),
        'interest_rate' => 3.0,
    ]);

    // Transform customer using resource
    $resource = new CustomerResource($customer->fresh());
    $customerData = $resource->toArray(request());

    // Assert that running balance is included
    expect($customerData)
        ->toHaveKey('running_utang_balance')
        ->and($customerData['running_utang_balance'])
        ->toBe(1500.00);
});
