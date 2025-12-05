<?php

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Setting;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new CustomerService;
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    // Set default interest rate for testing
    Setting::updateOrCreate(
        ['key' => 'default_utang_interest_rate'],
        ['value' => '3']
    );
});

describe('processMonthlyInterest', function () {
    it('creates interest transactions for customers with utang', function () {
        $customer = Customer::factory()
            ->withUtang()
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now()->subMonths(2),
            ]);

        // Create an initial transaction to establish running balance
        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'transaction_type' => 'sale',
            'previous_balance' => 0,
            'new_balance' => 1000.00,
            'transaction_amount' => 1000.00,
            'transaction_date' => now()->subMonth(),
        ]);

        $result = $this->service->processMonthlyInterest();

        expect($result)->toHaveCount(1);
        expect($result->first())
            ->transaction_type->toBe('monthly_interest')
            ->customer_id->toBe($customer->id);

        // Verify interest was calculated (default 3% = 30.00)
        $transaction = $result->first();
        expect((float) $transaction->previous_balance)->toBe(1000.00);
        expect((float) $transaction->transaction_amount)->toBe(30.00);
        expect((float) $transaction->new_balance)->toBe(1030.00);
    });

    it('excludes customers created in the current month', function () {
        // Customer created this month
        $newCustomer = Customer::factory()
            ->withUtang()
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now(),
            ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $newCustomer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 0,
            'new_balance' => 1000.00,
            'transaction_amount' => 1000.00,
        ]);

        // Customer created last month
        $oldCustomer = Customer::factory()
            ->withUtang()
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now()->subMonth(),
            ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $oldCustomer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 0,
            'new_balance' => 1000.00,
            'transaction_amount' => 1000.00,
        ]);

        $result = $this->service->processMonthlyInterest();

        // Only old customer should be processed
        expect($result)->toHaveCount(1);
        expect($result->first()->customer_id)->toBe($oldCustomer->id);
    });

    it('includes customers created in previous months of current year', function () {
        $customer = Customer::factory()
            ->withUtang()
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now()->startOfYear()->addMonth(),
            ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 0,
            'new_balance' => 500.00,
            'transaction_amount' => 500.00,
        ]);

        $result = $this->service->processMonthlyInterest();

        expect($result)->toHaveCount(1);
        expect($result->first()->customer_id)->toBe($customer->id);
    });

    it('skips customers without has_utang flag', function () {
        $customer = Customer::factory()->create([
            'user_id' => $this->user->id,
            'has_utang' => false,
            'created_at' => now()->subMonths(2),
        ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 0,
            'new_balance' => 1000.00,
            'transaction_amount' => 1000.00,
        ]);

        $result = $this->service->processMonthlyInterest();

        expect($result)->toHaveCount(0);
    });

    it('skips customers with zero balance', function () {
        $customer = Customer::factory()
            ->withUtang()
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now()->subMonths(2),
            ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 100,
            'new_balance' => 0,
            'transaction_amount' => 100,
            'transaction_type' => 'utang_payment',
        ]);

        $result = $this->service->processMonthlyInterest();

        expect($result)->toHaveCount(0);
    });

    it('skips customers with negative balance', function () {
        $customer = Customer::factory()
            ->withUtang()
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now()->subMonths(2),
            ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 0,
            'new_balance' => -50,
            'transaction_amount' => -50,
        ]);

        $result = $this->service->processMonthlyInterest();

        expect($result)->toHaveCount(0);
    });

    it('prevents duplicate monthly interest for same month', function () {
        $customer = Customer::factory()
            ->withUtang()
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now()->subMonths(2),
            ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 0,
            'new_balance' => 1000.00,
            'transaction_amount' => 1000.00,
        ]);

        // First processing
        $result1 = $this->service->processMonthlyInterest();
        expect($result1)->toHaveCount(1);

        // Second processing in same month should skip
        $result2 = $this->service->processMonthlyInterest();
        expect($result2)->toHaveCount(0);
    });

    it('uses customer specific interest rate when set', function () {
        $customer = Customer::factory()
            ->withUtang()
            ->withCustomInterestRate(5.0)
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now()->subMonths(2),
            ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 0,
            'new_balance' => 1000.00,
            'transaction_amount' => 1000.00,
        ]);

        $result = $this->service->processMonthlyInterest();

        expect($result)->toHaveCount(1);
        // 5% of 1000 = 50.00
        expect((float) $result->first()->transaction_amount)->toBe(50.00);
    });

    it('calculates interest correctly on running balance', function () {
        $customer = Customer::factory()
            ->withUtang()
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now()->subMonths(2),
            ]);

        // Initial sale
        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 0,
            'new_balance' => 1000.00,
            'transaction_amount' => 1000.00,
            'transaction_type' => 'sale',
        ]);

        // Payment
        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 1000.00,
            'new_balance' => 500.00,
            'transaction_amount' => 500.00,
            'transaction_type' => 'utang_payment',
        ]);

        $result = $this->service->processMonthlyInterest();

        expect($result)->toHaveCount(1);
        // 3% of 500 = 15.00
        expect((float) $result->first()->transaction_amount)->toBe(15.00);
        expect((float) $result->first()->previous_balance)->toBe(500.00);
        expect((float) $result->first()->new_balance)->toBe(515.00);
    });

    it('processes multiple customers in one run', function () {
        $customers = Customer::factory()
            ->count(3)
            ->withUtang()
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now()->subMonths(2),
            ]);

        foreach ($customers as $customer) {
            CustomerTransaction::factory()->create([
                'customer_id' => $customer->id,
                'user_id' => $this->user->id,
                'previous_balance' => 0,
                'new_balance' => 1000.00,
                'transaction_amount' => 1000.00,
            ]);
        }

        $result = $this->service->processMonthlyInterest();

        expect($result)->toHaveCount(3);
    });

    it('returns empty collection when no eligible customers', function () {
        $result = $this->service->processMonthlyInterest();

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($result)->toHaveCount(0);
    });

    it('sets correct transaction properties', function () {
        $customer = Customer::factory()
            ->withUtang()
            ->create([
                'user_id' => $this->user->id,
                'created_at' => now()->subMonths(2),
            ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'previous_balance' => 0,
            'new_balance' => 1000.00,
            'transaction_amount' => 1000.00,
        ]);

        $result = $this->service->processMonthlyInterest();

        $transaction = $result->first();
        expect($transaction->transaction_type)->toBe('monthly_interest');
        expect($transaction->reference_id)->toBeNull();
        expect($transaction->transaction_desc)->toContain('Monthly interest applied');
        expect($transaction->transaction_desc)->toContain('Interest Rate: 3%');
        expect($transaction->transaction_date->isToday())->toBeTrue();
    });
});
