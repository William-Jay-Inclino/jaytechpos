<?php

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

/**
 * Helper function to create a customer with a specific balance
 */
function createCustomerWithBalance(User $user, float $balance): Customer
{
    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'has_utang' => $balance > 0,
    ]);

    if ($balance > 0) {
        CustomerTransaction::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'transaction_type' => 'balance_update',
            'transaction_date' => now(),
            'previous_balance' => 0,
            'new_balance' => $balance,
            'transaction_amount' => $balance,
            'transaction_desc' => 'Initial balance for testing',
            'reference_id' => null,
        ]);
    }

    return $customer->fresh();
}

it('validates payment amount does not exceed customer balance', function () {
    $customer = createCustomerWithBalance($this->user, 1000.00);

    $response = post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 1500.00, // Exceeds balance
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Test payment',
    ]);

    $response->assertSessionHasErrors('payment_amount');
    $response->assertRedirect();
});

it('allows payment amount equal to customer balance', function () {
    $customer = createCustomerWithBalance($this->user, 1000.00);

    $response = post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 1000.00, // Exact balance
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Full payment',
    ]);

    $response->assertRedirect('/utang-payments');
    $response->assertSessionHasNoErrors();

    assertDatabaseHas('customer_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'utang_payment',
        'transaction_amount' => 1000.00,
    ]);
});

it('allows payment amount less than customer balance', function () {
    $customer = createCustomerWithBalance($this->user, 1000.00);

    $response = post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 500.00, // Less than balance
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Partial payment',
    ]);

    $response->assertRedirect('/utang-payments');
    $response->assertSessionHasNoErrors();

    assertDatabaseHas('customer_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'utang_payment',
        'transaction_amount' => 500.00,
    ]);
});

it('requires payment amount to be at least 0.01', function () {
    $customer = createCustomerWithBalance($this->user, 1000.00);

    $response = post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 0.00,
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Zero payment',
    ]);

    $response->assertSessionHasErrors('payment_amount');
});

it('requires customer to belong to authenticated user', function () {
    $otherUser = User::factory()->create();
    $customer = createCustomerWithBalance($otherUser, 1000.00);

    $response = post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 500.00,
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Test payment',
    ]);

    $response->assertSessionHasErrors('customer_id');
});

it('validates all required fields are present', function () {
    $response = post('/utang-payments', []);

    $response->assertSessionHasErrors([
        'customer_id',
        'payment_amount',
        'payment_date',
    ]);
});

it('updates has_utang status when balance reaches zero', function () {
    $customer = createCustomerWithBalance($this->user, 500.00);

    post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 500.00,
        'payment_date' => now()->format('Y-m-d\TH:i'),
        'notes' => 'Final payment',
    ]);

    $customer->refresh();
    expect($customer->has_utang)->toBeFalse();
});

it('displays formatted error message with currency symbol', function () {
    $customer = createCustomerWithBalance($this->user, 1234.56);

    $response = post('/utang-payments', [
        'customer_id' => $customer->id,
        'payment_amount' => 2000.00,
        'payment_date' => now()->format('Y-m-d\TH:i'),
    ]);

    $response->assertSessionHasErrors('payment_amount');
    $errors = session('errors');
    expect($errors->get('payment_amount')[0])
        ->toContain('₱1,234.56')
        ->toContain('cannot exceed');
});
