<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('expenses index returns expenses with correct structure', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);
    // Create expense for current month/year to match the controller filter
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'expense_date' => now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($user)->get(route('expenses.index'));

    $response->assertOk();
    $response->assertInertia(fn ($assert) => $assert
        ->component('expenses/Index')
        ->has('expenses', 1)
        ->has('categories', 1)
        ->where('expenses.0.id', $expense->id)
        ->where('expenses.0.name', $expense->name)
    );
});

test('expenses index only shows expenses for authenticated user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $category1 = ExpenseCategory::factory()->create(['user_id' => $user1->id]);
    $category2 = ExpenseCategory::factory()->create(['user_id' => $user2->id]);

    // Create expenses for current month/year to match the controller filter
    Expense::factory()->create([
        'user_id' => $user1->id,
        'category_id' => $category1->id,
        'expense_date' => now()->format('Y-m-d'),
    ]);
    Expense::factory()->create([
        'user_id' => $user2->id,
        'category_id' => $category2->id,
        'expense_date' => now()->format('Y-m-d'),
    ]);

    $response = $this->actingAs($user1)->get(route('expenses.index'));

    $response->assertOk();
    $response->assertInertia(fn ($assert) => $assert
        ->component('expenses/Index')
        ->has('expenses', 1)
        ->has('categories', 1)
    );
});

test('can show create expense form', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('expenses.create'));

    $response->assertOk();
    $response->assertInertia(fn ($assert) => $assert
        ->component('expenses/Create')
        ->has('categories', 1)
    );
});

test('can create new expense', function () {
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

test('cannot access other users expenses', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['user_id' => $user1->id]);
    $expense = Expense::factory()->create(['user_id' => $user1->id, 'category_id' => $category->id]);

    // User2 cannot view user1's expense
    $response = $this->actingAs($user2)->get(route('expenses.edit', $expense));
    $response->assertForbidden();

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
