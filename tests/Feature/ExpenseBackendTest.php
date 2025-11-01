<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create new expense via API', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);

    $expenseData = [
        'name' => 'Office Supplies',
        'category_id' => $category->id,
        'amount' => 150.50,
        'expense_date' => '2024-01-15',
    ];

    $response = $this->actingAs($user)->postJson(route('expenses.store'), $expenseData);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Expense created successfully!',
    ]);

    $this->assertDatabaseHas('expenses', [
        'user_id' => $user->id,
        'name' => 'Office Supplies',
        'category_id' => $category->id,
        'amount' => 150.50,
        'expense_date' => '2024-01-15 00:00:00',
    ]);
});

test('can update expense via API', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);
    $expense = Expense::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

    $updateData = [
        'name' => 'Updated Office Supplies',
        'category_id' => $category->id,
        'amount' => 200.75,
        'expense_date' => '2024-01-20',
    ];

    $response = $this->actingAs($user)->putJson(route('expenses.update', $expense), $updateData);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Expense updated successfully!',
    ]);

    $expense->refresh();
    expect($expense->name)->toBe('Updated Office Supplies');
    expect($expense->amount)->toBe('200.75');
});

test('can delete expense via API', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);
    $expense = Expense::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

    $response = $this->actingAs($user)->deleteJson(route('expenses.destroy', $expense));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'Expense deleted successfully!',
    ]);

    $this->assertModelMissing($expense);
});

test('expense authorization works correctly', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['user_id' => $user1->id]);
    $expense = Expense::factory()->create(['user_id' => $user1->id, 'category_id' => $category->id]);

    // User2 cannot update user1's expense
    $response = $this->actingAs($user2)->putJson(route('expenses.update', $expense), [
        'name' => 'Hacked',
        'category_id' => $category->id,
        'amount' => 1,
        'expense_date' => '2024-01-01',
    ]);
    $response->assertForbidden();

    // User2 cannot delete user1's expense
    $response = $this->actingAs($user2)->deleteJson(route('expenses.destroy', $expense));
    $response->assertForbidden();
});

test('expense validation works correctly', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);

    // Test missing required fields
    $response = $this->actingAs($user)->postJson(route('expenses.store'), []);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'category_id', 'amount', 'expense_date']);

    // Test invalid amount
    $response = $this->actingAs($user)->postJson(route('expenses.store'), [
        'name' => 'Test Expense',
        'category_id' => $category->id,
        'amount' => 'not-a-number',
        'expense_date' => '2024-01-15',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['amount']);

    // Test invalid date format
    $response = $this->actingAs($user)->postJson(route('expenses.store'), [
        'name' => 'Test Expense',
        'category_id' => $category->id,
        'amount' => 100,
        'expense_date' => 'invalid-date',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['expense_date']);

    // Test invalid category (doesn't exist)
    $response = $this->actingAs($user)->postJson(route('expenses.store'), [
        'name' => 'Test Expense',
        'category_id' => 999999,
        'amount' => 100,
        'expense_date' => '2024-01-15',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['category_id']);
});
