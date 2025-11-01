<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Expense::class);

        // Get month and year from request, default to current month/year
        $month = $request->get('month', now()->format('m'));
        $year = $request->get('year', now()->format('Y'));

        $expenses = Expense::ownedBy()
            ->with(['category'])
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $categories = ExpenseCategory::ownedBy()
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        // Generate pie chart data
        $chartData = $expenses->groupBy('category.name')
            ->map(function ($categoryExpenses, $categoryName) {
                $category = $categoryExpenses->first()?->category;

                return [
                    'label' => $categoryName ?? 'No Category',
                    'amount' => $categoryExpenses->sum('amount'),
                    'count' => $categoryExpenses->count(),
                    'color' => $category?->color ?? '#6B7280', // Default to gray if no category
                ];
            })
            ->values();

        return Inertia::render('expenses/Index', [
            'expenses' => ExpenseResource::collection($expenses)->resolve(),
            'categories' => $categories,
            'chartData' => $chartData,
            'selectedMonth' => (int) $month,
            'selectedYear' => (int) $year,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Expense::class);

        $categories = ExpenseCategory::ownedBy()
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('expenses/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreExpenseRequest $request)
    {
        $this->authorize('create', Expense::class);

        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        $expense = Expense::create($validated);
        $expense->load(['category']);

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully!',
            'expense' => new ExpenseResource($expense),
        ]);
    }

    public function edit(string $id)
    {
        $expense = Expense::with(['category'])->findOrFail($id);

        $this->authorize('view', $expense);

        $categories = ExpenseCategory::ownedBy()
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('expenses/Edit', [
            'expense' => (new ExpenseResource($expense))->resolve(),
            'categories' => $categories,
        ]);
    }

    public function update(UpdateExpenseRequest $request, string $id)
    {
        $expense = Expense::findOrFail($id);

        $this->authorize('update', $expense);

        $validated = $request->validated();
        $expense->update($validated);
        $expense->load(['category']);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully!',
            'expense' => new ExpenseResource($expense),
        ]);
    }

    public function destroy(string $id)
    {
        $expense = Expense::findOrFail($id);

        $this->authorize('delete', $expense);

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully!',
        ]);
    }
}
