<?php

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['status' => 'active']);
    $this->otherUser = User::factory()->create(['status' => 'active']);
});

describe('utangPayments', function () {
    it('displays utang payments page', function () {
        $response = $this->actingAs($this->user)->get('/utang-payments');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('utang-payments/Index')
            ->has('customers')
        );
    });

    it('only shows customers owned by authenticated user', function () {
        Customer::factory()->count(3)->create(['user_id' => $this->user->id]);
        Customer::factory()->count(2)->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->get('/utang-payments');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('customers', 3)
        );
    });

    it('orders customers by name', function () {
        Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Zara']);
        Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Alice']);
        Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Mike']);

        $response = $this->actingAs($this->user)->get('/utang-payments');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('customers.0.name', 'Alice')
            ->where('customers.1.name', 'Mike')
            ->where('customers.2.name', 'Zara')
        );
    });

    it('requires authentication', function () {
        $response = $this->get('/utang-payments');

        $response->assertRedirect('/login');
    });
});

describe('storeUtangPayment', function () {
    it('records payment successfully', function () {
        $customer = Customer::factory()->withUtang()->create(['user_id' => $this->user->id]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 1000,
        ]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 500,
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Payment received',
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'msg' => 'Payment recorded successfully!',
        ]);

        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'transaction_type' => 'utang_payment',
            'previous_balance' => 1000,
            'new_balance' => 500,
            'transaction_amount' => 500,
            'transaction_desc' => 'Payment received',
        ]);
    });

    it('records payment with default notes when notes are not provided', function () {
        $customer = Customer::factory()->withUtang()->create(['user_id' => $this->user->id]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 1000,
        ]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 500,
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertSuccessful();

        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'transaction_type' => 'utang_payment',
            'transaction_desc' => '---',
        ]);
    });

    it('marks customer as no utang when balance reaches zero', function () {
        $customer = Customer::factory()->withUtang()->create(['user_id' => $this->user->id]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 500,
        ]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 500,
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Final payment',
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertSuccessful();

        expect($customer->fresh()->has_utang)->toBeFalse();

        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'new_balance' => 0,
        ]);
    });

    it('marks customer as no utang when payment exceeds balance', function () {
        $customer = Customer::factory()->withUtang()->create(['user_id' => $this->user->id]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 300,
        ]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 300,
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Overpayment',
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertSuccessful();

        expect($customer->fresh()->has_utang)->toBeFalse();
    });

    it('validates required customer_id field', function () {
        $data = [
            'payment_amount' => 500,
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_id']);
    });

    it('validates customer_id exists and belongs to user', function () {
        $otherCustomer = Customer::factory()->create(['user_id' => $this->otherUser->id]);

        $data = [
            'customer_id' => $otherCustomer->id,
            'payment_amount' => 500,
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_id']);
    });

    it('validates required payment_amount field', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'customer_id' => $customer->id,
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_amount']);
    });

    it('validates payment_amount is numeric', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 'invalid',
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_amount']);
    });

    it('validates payment_amount minimum value', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 0,
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_amount']);
    });

    it('validates payment_amount does not exceed customer balance', function () {
        $customer = Customer::factory()->withUtang()->create(['user_id' => $this->user->id]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 500,
        ]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 1000,
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_amount']);
    });

    it('validates required payment_date field', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 500,
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_date']);
    });

    it('validates payment_date is a valid date', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 500,
            'payment_date' => 'invalid-date',
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment_date']);
    });

    it('validates notes max length', function () {
        $customer = Customer::factory()->withUtang()->create(['user_id' => $this->user->id]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 500,
        ]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 100,
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'notes' => str_repeat('a', 1001),
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['notes']);
    });

    it('requires authentication', function () {
        $customer = Customer::factory()->create();

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 500,
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->postJson('/utang-payments', $data);

        $response->assertUnauthorized();
    });

    it('handles customer not found gracefully', function () {
        $data = [
            'customer_id' => 99999,
            'payment_amount' => 500,
            'payment_date' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_id']);
    });

    it('records correct transaction amounts and balances', function () {
        $customer = Customer::factory()->withUtang()->create(['user_id' => $this->user->id]);

        // Create initial balance transaction
        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 2000,
        ]);

        // First payment
        $this->actingAs($this->user)->postJson('/utang-payments', [
            'customer_id' => $customer->id,
            'payment_amount' => 750,
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'notes' => 'First payment',
        ]);

        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'transaction_type' => 'utang_payment',
            'previous_balance' => 2000,
            'new_balance' => 1250,
            'transaction_amount' => 750,
        ]);

        // Second payment
        $this->actingAs($this->user)->postJson('/utang-payments', [
            'customer_id' => $customer->id,
            'payment_amount' => 500,
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Second payment',
        ]);

        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'transaction_type' => 'utang_payment',
            'previous_balance' => 1250,
            'new_balance' => 750,
            'transaction_amount' => 500,
        ]);
    });

    it('uses authenticated user id for transaction', function () {
        $customer = Customer::factory()->withUtang()->create(['user_id' => $this->user->id]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 1000,
        ]);

        $data = [
            'customer_id' => $customer->id,
            'payment_amount' => 500,
            'payment_date' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Payment',
        ];

        $this->actingAs($this->user)->postJson('/utang-payments', $data);

        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'transaction_type' => 'utang_payment',
        ]);
    });
});
