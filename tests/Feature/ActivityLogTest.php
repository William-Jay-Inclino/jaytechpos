<?php

use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs user creation activity', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect(Activity::where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('event', 'created')
        ->exists())->toBeTrue();
});

it('logs user update activity', function () {
    $user = User::factory()->create(['name' => 'Original Name']);

    Activity::query()->delete();

    $user->update(['name' => 'Updated Name']);

    $activity = Activity::where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('attributes'))->toHaveKey('name')
        ->and($activity->properties->get('old'))->toHaveKey('name');
});

it('logs product creation activity', function () {
    $product = Product::factory()->create(['product_name' => 'Test Product']);

    expect(Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->where('event', 'created')
        ->exists())->toBeTrue();
});

it('logs product update activity', function () {
    $product = Product::factory()->create(['unit_price' => 100]);

    Activity::query()->delete();

    $product->update(['unit_price' => 150]);

    $activity = Activity::where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('attributes')['unit_price'])->toBe('150.00')
        ->and($activity->properties->get('old')['unit_price'])->toBe('100.00');
});

it('logs customer creation activity', function () {
    $customer = Customer::factory()->create(['name' => 'Test Customer']);

    expect(Activity::where('subject_type', Customer::class)
        ->where('subject_id', $customer->id)
        ->where('event', 'created')
        ->exists())->toBeTrue();
});

it('logs customer update activity', function () {
    $customer = Customer::factory()->create(['has_utang' => false]);

    Activity::query()->delete();

    $customer->update(['has_utang' => true]);

    $activity = Activity::where('subject_type', Customer::class)
        ->where('subject_id', $customer->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('attributes')['has_utang'])->toBeTrue()
        ->and($activity->properties->get('old')['has_utang'])->toBeFalse();
});

it('logs customer transaction creation activity', function () {
    $transaction = CustomerTransaction::factory()->create([
        'transaction_type' => 'utang_payment',
        'transaction_amount' => 500,
    ]);

    expect(Activity::where('subject_type', CustomerTransaction::class)
        ->where('subject_id', $transaction->id)
        ->where('event', 'created')
        ->exists())->toBeTrue();
});

it('logs customer transaction update activity', function () {
    $transaction = CustomerTransaction::factory()->create(['transaction_amount' => 100]);

    Activity::query()->delete();

    $transaction->update(['transaction_amount' => 200]);

    $activity = Activity::where('subject_type', CustomerTransaction::class)
        ->where('subject_id', $transaction->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('attributes')['transaction_amount'])->toBe('200.00')
        ->and($activity->properties->get('old')['transaction_amount'])->toBe('100.00');
});

it('logs expense creation activity', function () {
    $expense = Expense::factory()->create(['name' => 'Office Supplies']);

    expect(Activity::where('subject_type', Expense::class)
        ->where('subject_id', $expense->id)
        ->where('event', 'created')
        ->exists())->toBeTrue();
});

it('logs expense update activity', function () {
    $expense = Expense::factory()->create(['amount' => 500]);

    Activity::query()->delete();

    $expense->update(['amount' => 750]);

    $activity = Activity::where('subject_type', Expense::class)
        ->where('subject_id', $expense->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('attributes')['amount'])->toBe('750.00')
        ->and($activity->properties->get('old')['amount'])->toBe('500.00');
});

it('does not log when no attributes are changed', function () {
    $user = User::factory()->create(['name' => 'Test User']);

    $activityCount = Activity::where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->count();

    $user->update(['name' => 'Test User']);

    $newActivityCount = Activity::where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->count();

    expect($newActivityCount)->toBe($activityCount);
});

it('logs deletion activity', function () {
    $product = Product::factory()->create();
    $productId = $product->id;

    $product->delete();

    expect(Activity::where('subject_type', Product::class)
        ->where('subject_id', $productId)
        ->where('event', 'deleted')
        ->exists())->toBeTrue();
});
