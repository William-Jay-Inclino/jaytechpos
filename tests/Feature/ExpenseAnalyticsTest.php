<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

test('authenticated user can view expense analytics page', function () {
    $response = get('/expenses/analytics');

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('expenses/Analytics')
            ->has('chartData')
            ->has('selectedYear')
        );
});

test('expense analytics shows correct chart data for year', function () {
    $category = ExpenseCategory::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Food',
        'color' => '#FF0000',
    ]);

    Expense::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'expense_date' => now()->format('Y-m-d'),
        'amount' => 100,
    ]);

    $response = get('/expenses/analytics?year='.now()->year);

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('expenses/Analytics')
            ->where('chartData.0.label', 'Food')
            ->where('chartData.0.count', 3)
            ->where('chartData.0.amount', 300)
            ->where('chartData.0.color', '#FF0000')
            ->has('chartData.0.id')
        );
});

test('guest cannot view expense analytics page', function () {
    auth()->logout();

    $response = get('/expenses/analytics');

    $response->assertRedirect('/login');
});

test('can fetch category expenses', function () {
    $category = ExpenseCategory::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Food',
    ]);

    Expense::factory()->count(5)->create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'expense_date' => now()->format('Y-m-d'),
        'amount' => 100,
    ]);

    $response = get("/expenses/category/{$category->id}?year=".now()->year);

    $response->assertSuccessful()
        ->assertJson([
            'expenses' => true,
        ])
        ->assertJsonCount(5, 'expenses');
});

test('cannot fetch other users category expenses', function () {
    $otherUser = User::factory()->create();

    $category = ExpenseCategory::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Food',
    ]);

    Expense::factory()->count(3)->create([
        'user_id' => $otherUser->id,
        'category_id' => $category->id,
        'expense_date' => now()->format('Y-m-d'),
    ]);

    $response = get("/expenses/category/{$category->id}?year=".now()->year);

    $response->assertSuccessful()
        ->assertJsonCount(0, 'expenses');
});
