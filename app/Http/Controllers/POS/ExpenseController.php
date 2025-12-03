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

        // Use date range instead of whereYear/whereMonth for better performance
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate)); // Last day of month

        $expenses = Expense::ownedBy()
            ->with(['category'])
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $categories = ExpenseCategory::ownedBy()
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('expenses/Index', [
            'expenses' => ExpenseResource::collection($expenses)->resolve(),
            'categories' => $categories,
            'selectedMonth' => (int) $month,
            'selectedYear' => (int) $year,
        ]);
    }

    public function analytics(Request $request)
    {
        $this->authorize('viewAny', Expense::class);

        // Get year from request, default to current year
        $year = $request->get('year', now()->format('Y'));

        $expenses = Expense::ownedBy()
            ->with(['category'])
            ->whereYear('expense_date', $year)
            ->get();

        // Generate pie chart data with category IDs
        $chartData = $expenses->groupBy('category.id')
            ->map(function ($categoryExpenses) {
                $category = $categoryExpenses->first()?->category;

                return [
                    'id' => $category?->id,
                    'label' => $category?->name ?? 'No Category',
                    'amount' => $categoryExpenses->sum('amount'),
                    'count' => $categoryExpenses->count(),
                    'color' => $category?->color ?? '#6B7280', // Default to gray if no category
                ];
            })
            ->values();

        // Expenses by month for the year
        $monthlyExpenses = $expenses->groupBy(function ($expense) {
            return \Carbon\Carbon::parse($expense->expense_date)->format('F');
        })->map(function ($monthExpenses, $month) {
            return [
                'month' => $month,
                'amount' => $monthExpenses->sum('amount'),
            ];
        })->sortBy(function ($item) {
            // Sort by month number
            return \DateTime::createFromFormat('F', $item['month'])->format('n');
        })->values();

        return Inertia::render('expenses/Analytics', [
            'chartData' => $chartData,
            'selectedYear' => (int) $year,
            'monthlyExpenses' => $monthlyExpenses,
        ]);
    }

    public function categoryExpenses(Request $request, $categoryId)
    {
        $this->authorize('viewAny', Expense::class);

        $year = $request->get('year', now()->format('Y'));

        $expenses = Expense::ownedBy()
            ->where('category_id', $categoryId)
            ->whereYear('expense_date', $year)
            ->orderBy('expense_date', 'desc')
            ->get(['id', 'name', 'amount', 'expense_date']);

        return response()->json([
            'expenses' => $expenses,
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

        return redirect()->route('expenses.index')
            ->with('message', 'Expense created successfully!');
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

    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return response()->json([
            'success' => true,
            'msg' => 'User deleted successfully.',
        ]);
    }
}
