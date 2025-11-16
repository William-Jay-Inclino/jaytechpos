<?php

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('expense create page shows categories for authenticated user', function () {
    $user = User::factory()->create();

    // Create categories for the user
    ExpenseCategory::factory()->count(5)->create([
        'user_id' => $user->id,
    ]);

    actingAs($user);

    $response = get('/expenses/create');

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('expenses/Create')
            ->has('categories', 5)
            ->has('categories.0', fn (Assert $category) => $category
                ->has('id')
                ->has('name')
                ->has('color')
            )
        );
});

test('expense edit page shows categories for authenticated user', function () {
    $user = User::factory()->create();

    $category = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
    ]);

    $expense = \App\Models\Expense::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    actingAs($user);

    $response = get("/expenses/{$expense->id}/edit");

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('expenses/Edit')
            ->has('categories')
            ->has('expense')
        );
});
