<?php

use App\Jobs\ProcessMonthlyUtangTracking;
use App\Models\Customer;
use App\Models\User;
use App\Models\UtangTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('processes monthly utang tracking for customers with utang', function () {
    // Create customers with utang
    $customerWithUtang = Customer::factory()->create([
        'user_id' => $this->user->id,
        'has_utang' => true,
        'interest_rate' => 5.0,
    ]);

    $customerWithoutUtang = Customer::factory()->create([
        'user_id' => $this->user->id,
        'has_utang' => false,
    ]);

    // Create initial utang tracking with balance
    $initialTracking = UtangTracking::factory()->create([
        'user_id' => $this->user->id,
        'customer_id' => $customerWithUtang->id,
        'beginning_balance' => 1000.00,
        'computation_date' => now()->startOfMonth(),
        'interest_rate' => 5.0,
    ]);

    // Mock next month
    $nextMonth = now()->addMonth();
    $this->travelTo($nextMonth);

    // Run the job
    $job = new ProcessMonthlyUtangTracking;
    $job->handle();

    // Assert new tracking record created with interest
    $newTracking = UtangTracking::where('customer_id', $customerWithUtang->id)
        ->whereMonth('computation_date', $nextMonth->month)
        ->whereYear('computation_date', $nextMonth->year)
        ->first();

    expect($newTracking)->not->toBeNull();
    expect($newTracking->beginning_balance)->toBe(1050.0); // 1000 + 5% interest
    expect($newTracking->interest_rate)->toBe(5.0);

    // Assert no tracking record created for customer without utang
    $noTracking = UtangTracking::where('customer_id', $customerWithoutUtang->id)
        ->whereMonth('computation_date', $nextMonth->month)
        ->whereYear('computation_date', $nextMonth->year)
        ->first();

    expect($noTracking)->toBeNull();
});

it('skips customers with zero balance', function () {
    $customer = Customer::factory()->create([
        'user_id' => $this->user->id,
        'has_utang' => true,
    ]);

    // No existing tracking records means zero balance

    // Run the job
    $job = new ProcessMonthlyUtangTracking;
    $job->handle();

    // Assert no tracking record created for zero balance
    $tracking = UtangTracking::where('customer_id', $customer->id)->first();
    expect($tracking)->toBeNull();
});

it('skips if record already exists for current month', function () {
    $customer = Customer::factory()->create([
        'user_id' => $this->user->id,
        'has_utang' => true,
    ]);

    // Create existing tracking for current month
    $existingTracking = UtangTracking::factory()->create([
        'user_id' => $this->user->id,
        'customer_id' => $customer->id,
        'beginning_balance' => 1000.00,
        'computation_date' => now()->startOfMonth(),
        'interest_rate' => 3.0,
    ]);

    $initialCount = UtangTracking::where('customer_id', $customer->id)->count();

    // Run the job
    $job = new ProcessMonthlyUtangTracking;
    $job->handle();

    // Assert no new record created
    $finalCount = UtangTracking::where('customer_id', $customer->id)->count();
    expect($finalCount)->toBe($initialCount);
});

it('calculates interest correctly', function () {
    $customer = Customer::factory()->create([
        'user_id' => $this->user->id,
        'has_utang' => true,
        'interest_rate' => 10.0, // Custom rate
    ]);

    // Create initial tracking
    UtangTracking::factory()->create([
        'user_id' => $this->user->id,
        'customer_id' => $customer->id,
        'beginning_balance' => 2000.00,
        'computation_date' => now()->startOfMonth(),
        'interest_rate' => 10.0,
    ]);

    // Move to next month
    $this->travelTo(now()->addMonth());

    // Run the job
    $job = new ProcessMonthlyUtangTracking;
    $job->handle();

    // Check new balance calculation (2000 + 10% = 2200)
    $newTracking = UtangTracking::where('customer_id', $customer->id)
        ->latest('computation_date')
        ->first();

    expect($newTracking->beginning_balance)->toBe(2200.0);
    expect($newTracking->interest_rate)->toBe(10.0);
});
