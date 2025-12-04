<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = ExpenseCategory::factory()->create(['user_id' => $this->user->id]);
});

describe('index', function () {
    it('displays expenses index page', function () {
        actingAs($this->user);

        $response = get('/expenses');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenses/Index')
            ->has('expenses')
            ->has('categories')
            ->has('selectedMonth')
            ->has('selectedYear')
        );
    });

    it('only shows expenses owned by authenticated user', function () {
        $otherUser = User::factory()->create();
        $otherCategory = ExpenseCategory::factory()->create(['user_id' => $otherUser->id]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'expense_date' => now(),
        ]);

        Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
            'expense_date' => now(),
        ]);

        actingAs($this->user);

        $response = get('/expenses');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenses/Index')
            ->has('expenses', 1)
        );
    });

    it('filters expenses by month and year', function () {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'expense_date' => '2024-01-15',
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'expense_date' => '2024-02-15',
        ]);

        actingAs($this->user);

        $response = get('/expenses?month=1&year=2024');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenses/Index')
            ->has('expenses', 1)
            ->where('selectedMonth', 1)
            ->where('selectedYear', 2024)
        );
    });

    it('orders expenses by date and creation time descending', function () {
        $expense1 = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'expense_date' => '2024-01-10',
            'created_at' => now()->subHours(2),
        ]);

        $expense2 = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'expense_date' => '2024-01-10',
            'created_at' => now()->subHours(1),
        ]);

        $expense3 = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'expense_date' => '2024-01-15',
        ]);

        actingAs($this->user);

        $response = get('/expenses?month=1&year=2024');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenses/Index')
            ->where('expenses.0.id', $expense3->id)
            ->where('expenses.1.id', $expense2->id)
            ->where('expenses.2.id', $expense1->id)
        );
    });

    it('requires authentication', function () {
        $response = get('/expenses');

        $response->assertRedirect(route('login'));
    });
});

describe('analytics', function () {
    it('displays expenses analytics page', function () {
        actingAs($this->user);

        $response = get('/expenses/analytics');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenses/Analytics')
            ->has('chartData')
            ->has('selectedYear')
            ->has('monthlyExpenses')
        );
    });

    it('filters analytics by year', function () {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'expense_date' => '2024-01-15',
        ]);

        Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'expense_date' => '2023-01-15',
        ]);

        actingAs($this->user);

        $response = get('/expenses/analytics?year=2024');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenses/Analytics')
            ->where('selectedYear', 2024)
        );
    });

    it('requires authentication', function () {
        $response = get('/expenses/analytics');

        $response->assertRedirect(route('login'));
    });
});

describe('categoryExpenses', function () {
    it('returns expenses for specific category', function () {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'expense_date' => now()->startOfYear(),
        ]);

        actingAs($this->user);

        $response = get("/expenses/category/{$this->category->id}");

        $response->assertOk();
        $response->assertJson([
            'expenses' => [
                [
                    'id' => $expense->id,
                    'name' => $expense->name,
                ],
            ],
        ]);
    });

    it('requires authentication', function () {
        $response = get("/expenses/category/{$this->category->id}");

        $response->assertRedirect(route('login'));
    });
});

describe('create', function () {
    it('displays create expense page', function () {
        actingAs($this->user);

        $response = get('/expenses/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenses/Create')
            ->has('categories')
        );
    });

    it('requires authentication', function () {
        $response = get('/expenses/create');

        $response->assertRedirect(route('login'));
    });
});

describe('store', function () {
    it('creates an expense successfully', function () {
        actingAs($this->user);

        $expenseData = [
            'name' => 'Office Supplies',
            'category_id' => $this->category->id,
            'amount' => 150.50,
            'expense_date' => '2024-01-15',
        ];

        $response = post('/expenses', $expenseData);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'Expense created successfully!',
        ]);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $this->user->id,
            'name' => 'Office Supplies',
            'category_id' => $this->category->id,
            'amount' => 150.50,
        ]);
    });

    it('validates required name field', function () {
        actingAs($this->user);

        $response = post('/expenses', [
            'category_id' => $this->category->id,
            'amount' => 100,
            'expense_date' => '2024-01-15',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('validates name max length', function () {
        actingAs($this->user);

        $response = post('/expenses', [
            'name' => str_repeat('a', 256),
            'category_id' => $this->category->id,
            'amount' => 100,
            'expense_date' => '2024-01-15',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('validates required category_id field', function () {
        actingAs($this->user);

        $response = post('/expenses', [
            'name' => 'Office Supplies',
            'amount' => 100,
            'expense_date' => '2024-01-15',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    });

    it('validates category_id exists', function () {
        actingAs($this->user);

        $response = post('/expenses', [
            'name' => 'Office Supplies',
            'category_id' => 99999,
            'amount' => 100,
            'expense_date' => '2024-01-15',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    });

    it('validates required amount field', function () {
        actingAs($this->user);

        $response = post('/expenses', [
            'name' => 'Office Supplies',
            'category_id' => $this->category->id,
            'expense_date' => '2024-01-15',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    });

    it('validates amount is numeric', function () {
        actingAs($this->user);

        $response = post('/expenses', [
            'name' => 'Office Supplies',
            'category_id' => $this->category->id,
            'amount' => 'not-a-number',
            'expense_date' => '2024-01-15',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    });

    it('validates amount minimum value', function () {
        actingAs($this->user);

        $response = post('/expenses', [
            'name' => 'Office Supplies',
            'category_id' => $this->category->id,
            'amount' => 0,
            'expense_date' => '2024-01-15',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    });

    it('validates required expense_date field', function () {
        actingAs($this->user);

        $response = post('/expenses', [
            'name' => 'Office Supplies',
            'category_id' => $this->category->id,
            'amount' => 100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expense_date']);
    });

    it('validates expense_date is a valid date', function () {
        actingAs($this->user);

        $response = post('/expenses', [
            'name' => 'Office Supplies',
            'category_id' => $this->category->id,
            'amount' => 100,
            'expense_date' => 'not-a-date',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expense_date']);
    });

    it('requires authentication', function () {
        $response = post('/expenses', [
            'name' => 'Office Supplies',
            'category_id' => $this->category->id,
            'amount' => 100,
            'expense_date' => '2024-01-15',
        ]);

        $response->assertRedirect(route('login'));
    });
});

describe('edit', function () {
    it('displays edit expense page', function () {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        actingAs($this->user);

        $response = get("/expenses/{$expense->id}/edit");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('expenses/Edit')
            ->has('expense')
            ->has('categories')
            ->where('expense.id', $expense->id)
        );
    });

    it('prevents viewing other users expense', function () {
        $otherUser = User::factory()->create();
        $otherCategory = ExpenseCategory::factory()->create(['user_id' => $otherUser->id]);
        $expense = Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
        ]);

        actingAs($this->user);

        $response = get("/expenses/{$expense->id}/edit");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = get("/expenses/{$expense->id}/edit");

        $response->assertRedirect(route('login'));
    });
});

describe('update', function () {
    it('updates expense successfully', function () {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'name' => 'Old Name',
            'amount' => 100,
        ]);

        actingAs($this->user);

        $response = put("/expenses/{$expense->id}", [
            'name' => 'New Name',
            'category_id' => $this->category->id,
            'amount' => 200,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Expense updated successfully!',
        ]);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'name' => 'New Name',
            'amount' => 200,
        ]);
    });

    it('prevents updating other users expense', function () {
        $otherUser = User::factory()->create();
        $otherCategory = ExpenseCategory::factory()->create(['user_id' => $otherUser->id]);
        $expense = Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
        ]);

        actingAs($this->user);

        $response = put("/expenses/{$expense->id}", [
            'name' => 'Updated Name',
            'category_id' => $otherCategory->id,
            'amount' => 150,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
        ]);

        $response->assertForbidden();
    });

    it('validates required fields', function () {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        actingAs($this->user);

        $response = put("/expenses/{$expense->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'category_id', 'amount', 'expense_date']);
    });

    it('requires authentication', function () {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = put("/expenses/{$expense->id}", [
            'name' => 'Updated Name',
            'category_id' => $this->category->id,
            'amount' => 150,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('login'));
    });
});

describe('destroy', function () {
    it('deletes expense successfully', function () {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        actingAs($this->user);

        $response = delete("/expenses/{$expense->id}");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id,
        ]);
    });

    it('prevents deleting other users expense', function () {
        $otherUser = User::factory()->create();
        $otherCategory = ExpenseCategory::factory()->create(['user_id' => $otherUser->id]);
        $expense = Expense::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
        ]);

        actingAs($this->user);

        $response = delete("/expenses/{$expense->id}");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $expense = Expense::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = delete("/expenses/{$expense->id}");

        $response->assertRedirect(route('login'));
    });
});
