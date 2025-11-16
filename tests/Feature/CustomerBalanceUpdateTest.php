<?php

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create([
        'user_id' => $this->user->id,
    ]);
    $this->actingAs($this->user);
});

test('user can update customer balance with note', function () {
    $initialBalance = $this->customer->running_utang_balance;
    $newBalance = 1500.50;
    $note = 'Adjusted for previous payment error';

    $response = $this->patch("/customers/{$this->customer->id}/balance", [
        'balance' => $newBalance,
        'note' => $note,
    ]);

    $response->assertRedirect();

    // Check transaction was created
    $transaction = CustomerTransaction::where('customer_id', $this->customer->id)
        ->where('transaction_type', 'balance_update')
        ->first();

    expect($transaction)->not->toBeNull();
    expect($transaction->user_id)->toBe($this->user->id);
    expect($transaction->transaction_type)->toBe('balance_update');
    expect((float) $transaction->previous_balance)->toBe((float) $initialBalance);
    expect((float) $transaction->new_balance)->toBe($newBalance);
    expect((float) $transaction->transaction_amount)->toBe($newBalance);
    expect($transaction->transaction_desc)->toBe($note);
});

test('user can update customer balance without note', function () {
    $initialBalance = $this->customer->running_utang_balance;
    $newBalance = 2000.00;

    $response = $this->patch("/customers/{$this->customer->id}/balance", [
        'balance' => $newBalance,
    ]);

    $response->assertRedirect();

    // Check transaction was created
    $transaction = CustomerTransaction::where('customer_id', $this->customer->id)
        ->where('transaction_type', 'balance_update')
        ->first();

    expect($transaction)->not->toBeNull();
    expect($transaction->transaction_desc)->toBe('Balance Update');
});

test('user cannot update customer balance to same amount', function () {
    $currentBalance = $this->customer->running_utang_balance;

    $response = $this->patch("/customers/{$this->customer->id}/balance", [
        'balance' => $currentBalance,
        'note' => 'Should fail',
    ]);

    $response->assertSessionHas('error');

    // No transaction should be created
    expect(CustomerTransaction::where('customer_id', $this->customer->id)->count())->toBe(0);
});

test('user cannot update balance with negative amount', function () {
    $response = $this->patch("/customers/{$this->customer->id}/balance", [
        'balance' => -100,
        'note' => 'Should fail',
    ]);

    $response->assertSessionHasErrors('balance');
});

test('user cannot update balance with invalid note', function () {
    $longNote = str_repeat('a', 501); // Exceeds 500 character limit

    $response = $this->patch("/customers/{$this->customer->id}/balance", [
        'balance' => 1000,
        'note' => $longNote,
    ]);

    $response->assertSessionHasErrors('note');
});

test('user cannot update balance of customer they do not own', function () {
    $otherUser = User::factory()->create();
    $otherCustomer = Customer::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->patch("/customers/{$otherCustomer->id}/balance", [
        'balance' => 1000,
    ]);

    $response->assertForbidden();
});

test('guest cannot update customer balance', function () {
    auth()->logout();

    $response = $this->patch("/customers/{$this->customer->id}/balance", [
        'balance' => 1000,
    ]);

    $response->assertRedirect('/login');
});

test('balance update creates transaction with correct data', function () {
    $initialBalance = $this->customer->running_utang_balance;
    $newBalance = 750.25;
    $note = 'Manual adjustment';

    $this->patch("/customers/{$this->customer->id}/balance", [
        'balance' => $newBalance,
        'note' => $note,
    ]);

    $transaction = CustomerTransaction::where('customer_id', $this->customer->id)->first();

    expect((float) $transaction->previous_balance)->toBe((float) $initialBalance);
    expect((float) $transaction->new_balance)->toBe($newBalance);
    expect((float) $transaction->transaction_amount)->toBe($newBalance);
    expect($transaction->transaction_desc)->toBe($note);
    expect($transaction->transaction_type)->toBe('balance_update');
    expect($transaction->reference_id)->toBeNull();
    expect($transaction->transaction_date)->not->toBeNull();
});
