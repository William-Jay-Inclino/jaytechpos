<?php

use App\Models\Customer;
use App\Models\User;
use App\Models\UtangPayment;
use App\Traits\HandlesTimezone;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create([
        'user_id' => $this->user->id,
    ]);
});

test('stores utang payment with correct Manila timezone', function () {
    // Create a payment with Manila time
    $manilaDateTime = '2025-10-30 10:48:00';

    $payment = UtangPayment::create([
        'user_id' => $this->user->id,
        'customer_id' => $this->customer->id,
        'payment_amount' => 100.00,
        'previous_balance' => 500.00,
        'new_balance' => 400.00,
        'payment_date' => $manilaDateTime,
        'notes' => 'Test payment',
    ]);

    expect($payment->payment_date->format('Y-m-d H:i:s'))
        ->toBe('2025-10-30 10:48:00');
});

test('converts UTC time back to Manila time correctly', function () {
    $converter = new class
    {
        use HandlesTimezone;

        public function convertToManila($utcDateTime)
        {
            return $this->utcToManila($utcDateTime);
        }
    };

    // Create a payment
    $payment = UtangPayment::create([
        'user_id' => $this->user->id,
        'customer_id' => $this->customer->id,
        'payment_amount' => 100.00,
        'previous_balance' => 500.00,
        'new_balance' => 400.00,
        'payment_date' => '2025-10-30 10:48:00',
        'notes' => 'Test payment',
    ]);

    // Convert the stored UTC datetime back to Manila time
    $manilaTime = $converter->convertToManila($payment->payment_date);

    expect($manilaTime)->toBe('2025-10-30 10:48:00');
});

test('payment does not show as midnight when created at 10:48 AM', function () {
    // Create a payment at 10:48 AM Manila time
    $payment = UtangPayment::create([
        'user_id' => $this->user->id,
        'customer_id' => $this->customer->id,
        'payment_amount' => 100.00,
        'previous_balance' => 500.00,
        'new_balance' => 400.00,
        'payment_date' => '2025-10-30 10:48:00',
        'notes' => 'Test payment at 10:48 AM',
    ]);

    $converter = new class
    {
        use HandlesTimezone;

        public function convertToManila($utcDateTime)
        {
            return $this->utcToManila($utcDateTime);
        }
    };

    $displayTime = $converter->convertToManila($payment->payment_date);

    // Should show 10:48:00, not 00:00:00 (midnight)
    expect($displayTime)->toContain('10:48:00');
    expect($displayTime)->not->toContain('00:00:00');
});
