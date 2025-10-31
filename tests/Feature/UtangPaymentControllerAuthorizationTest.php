<?php

use App\Models\Customer;
use App\Models\User;
use App\Models\UtangPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->customer = Customer::factory()->create(['user_id' => $this->user->id]);
    $this->otherCustomer = Customer::factory()->create(['user_id' => $this->otherUser->id]);
    $this->utangPayment = UtangPayment::factory()->create(['user_id' => $this->user->id]);
    $this->otherUtangPayment = UtangPayment::factory()->create(['user_id' => $this->otherUser->id]);
});

describe('UtangPaymentController → index authorization', function () {
    it('allows authenticated users to view utang payments index', function () {
        $response = $this->actingAs($this->user)->get('/utang-payments');

        $response->assertSuccessful();
    });

    it('redirects unauthenticated users to login', function () {
        $response = $this->get('/utang-payments');

        $response->assertRedirect('/login');
    });
});

describe('UtangPaymentController → store authorization', function () {
    it('allows authenticated users to create payments for their own customers', function () {
        $paymentData = [
            'customer_id' => $this->customer->id,
            'payment_amount' => 100.00,
            'payment_date' => '2025-10-30T11:08',
            'notes' => 'Test payment',
        ];

        $response = $this->actingAs($this->user)->post('/utang-payments', $paymentData);

        $response->assertRedirect('/utang-payments');
    });

    it('prevents unauthenticated users from creating payments', function () {
        $paymentData = [
            'customer_id' => $this->customer->id,
            'payment_amount' => 100.00,
            'payment_date' => '2025-10-30T11:08',
            'notes' => 'Test payment',
        ];

        $response = $this->post('/utang-payments', $paymentData);

        $response->assertRedirect('/login');
    });

    it('prevents users from creating payments for other users customers via validation', function () {
        // The request validation already includes customer ownership check
        // so this will fail at validation level, not authorization level
        $paymentData = [
            'customer_id' => $this->otherCustomer->id,
            'payment_amount' => 100.00,
            'payment_date' => '2025-10-30T11:08',
            'notes' => 'Test payment',
        ];

        $response = $this->actingAs($this->user)->post('/utang-payments', $paymentData);

        // This fails validation (302 redirect back with errors) rather than authorization (403)
        $response->assertRedirect();
        $response->assertSessionHasErrors(['customer_id']);
    });
});
