<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->createExpensesForUser($user);
        }
    }

    private function createExpensesForUser(User $user): void
    {
        $categories = ExpenseCategory::where('user_id', $user->id)->get();

        if ($categories->isEmpty()) {
            $this->command->warn("No expense categories found for user: {$user->name}");

            return;
        }

        // Generate expenses from January 2025 to present
        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::now();

        $expenseCount = 0;
        $targetExpenses = $this->getExpenseTargetByBusinessType($user);

        // Generate monthly recurring expenses
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $monthlyExpenses = $this->createMonthlyExpenses($user, $categories, $currentDate);
            $expenseCount += $monthlyExpenses;
            $currentDate->addMonth();
        }

        // Generate random ad-hoc expenses
        for ($i = 0; $i < ($targetExpenses - $expenseCount) && $i < 50; $i++) {
            $this->createRandomExpense($user, $categories, $startDate, $endDate);
            $expenseCount++;
        }

        $this->command->info("Created {$expenseCount} expenses for user: {$user->name}");
    }

    private function getExpenseTargetByBusinessType(User $user): int
    {
        return match ($user->email) {
            'roberto.cruz@demo.com' => rand(80, 120), // Mini Grocery - More operational expenses
            'maria.santos@demo.com' => rand(60, 90),  // Fruits/Vegetables - Medium expenses
            'luz.reyes@demo.com' => rand(40, 70),     // Sari-Sari - Lower expenses
            default => rand(50, 80), // Default
        };
    }

    private function createMonthlyExpenses(User $user, $categories, Carbon $month): int
    {
        $expenseCount = 0;
        $businessExpenses = $this->getBusinessTypeExpenses($user->email);

        foreach ($businessExpenses['monthly'] as $expenseData) {
            $category = $categories->where('name', $expenseData['category'])->first();
            if (! $category) {
                continue;
            }

            // Create expense on random day of the month (1st-28th)
            $expenseDate = $month->copy()->addDays(rand(0, 27));
            if ($expenseDate->isFuture()) {
                continue;
            }

            Expense::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'name' => $expenseData['description'],
                'amount' => $this->generateExpenseAmount($expenseData['min'], $expenseData['max']),
                'expense_date' => $expenseDate->addHours(rand(8, 18))->addMinutes(rand(0, 59)),
            ]);

            $expenseCount++;
        }

        return $expenseCount;
    }

    private function createRandomExpense(User $user, $categories, Carbon $startDate, Carbon $endDate): void
    {
        $businessExpenses = $this->getBusinessTypeExpenses($user->email);
        $randomExpense = $businessExpenses['random'][array_rand($businessExpenses['random'])];

        $category = $categories->where('name', $randomExpense['category'])->first();
        if (! $category) {
            return;
        }

        $totalDays = $startDate->diffInDays($endDate);
        $randomDay = rand(0, $totalDays);
        $expenseDate = $startDate->copy()->addDays($randomDay);

        if ($expenseDate->isFuture()) {
            return;
        }

        Expense::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => $randomExpense['description'],
            'amount' => $this->generateExpenseAmount($randomExpense['min'], $randomExpense['max']),
            'expense_date' => $expenseDate->addHours(rand(8, 18))->addMinutes(rand(0, 59)),
        ]);
    }

    private function getBusinessTypeExpenses(string $email): array
    {
        return match ($email) {
            'maria.santos@demo.com' => [ // Fruits/Vegetables/Rice Store
                'monthly' => [
                    ['category' => 'Rent', 'description' => 'Store Rent', 'min' => 6000, 'max' => 8000],
                    ['category' => 'Utilities', 'description' => 'Electricity Bill', 'min' => 1200, 'max' => 1800],
                    ['category' => 'Utilities', 'description' => 'Water Bill', 'min' => 200, 'max' => 400],
                    ['category' => 'Transportation', 'description' => 'Market Transport', 'min' => 1500, 'max' => 2500],
                ],
                'random' => [
                    ['category' => 'Supplies', 'description' => 'Plastic Bags', 'min' => 150, 'max' => 350],
                    ['category' => 'Supplies', 'description' => 'Weighing Scale Repair', 'min' => 300, 'max' => 800],
                    ['category' => 'Government Fees & Permits', 'description' => 'Business Permit Renewal', 'min' => 1500, 'max' => 2000],
                    ['category' => 'Equipment & Maintenance', 'description' => 'Display Rack Repair', 'min' => 500, 'max' => 1000],
                ],
            ],
            'roberto.cruz@demo.com' => [ // Mini Grocery Store
                'monthly' => [
                    ['category' => 'Rent', 'description' => 'Store Rent', 'min' => 12000, 'max' => 15000],
                    ['category' => 'Utilities', 'description' => 'Electricity Bill', 'min' => 2500, 'max' => 3500],
                    ['category' => 'Utilities', 'description' => 'Water Bill', 'min' => 400, 'max' => 600],
                    ['category' => 'Staff Wages', 'description' => 'Employee Salary', 'min' => 10000, 'max' => 12000],
                ],
                'random' => [
                    ['category' => 'Equipment & Maintenance', 'description' => 'Freezer Maintenance', 'min' => 1500, 'max' => 2500],
                    ['category' => 'Supplies', 'description' => 'Shopping Bags', 'min' => 300, 'max' => 700],
                    ['category' => 'Government Fees & Permits', 'description' => 'FDA Permit', 'min' => 2000, 'max' => 3000],
                    ['category' => 'Insurance', 'description' => 'Store Insurance', 'min' => 1500, 'max' => 2000],
                ],
            ],
            'luz.reyes@demo.com' => [ // Sari-Sari Store
                'monthly' => [
                    ['category' => 'Utilities', 'description' => 'Electricity Bill', 'min' => 600, 'max' => 1000],
                    ['category' => 'Supplies', 'description' => 'Load Cards', 'min' => 800, 'max' => 1200],
                ],
                'random' => [
                    ['category' => 'Supplies', 'description' => 'Small Plastic Bags', 'min' => 80, 'max' => 200],
                    ['category' => 'Transportation', 'description' => 'Supplier Pickup', 'min' => 150, 'max' => 350],
                    ['category' => 'Government Fees & Permits', 'description' => 'Barangay Permit', 'min' => 300, 'max' => 600],
                    ['category' => 'Miscellaneous', 'description' => 'Store Repairs', 'min' => 200, 'max' => 500],
                ],
            ],
            default => [ // Default for Jay
                'monthly' => [
                    ['category' => 'Rent', 'description' => 'Store Rent', 'min' => 8000, 'max' => 12000],
                    ['category' => 'Utilities', 'description' => 'Electricity Bill', 'min' => 1500, 'max' => 2200],
                ],
                'random' => [
                    ['category' => 'Supplies', 'description' => 'General Supplies', 'min' => 300, 'max' => 700],
                    ['category' => 'Miscellaneous', 'description' => 'Miscellaneous Expenses', 'min' => 200, 'max' => 500],
                ],
            ]
        };
    }

    private function generateExpenseAmount(int $min, int $max): float
    {
        // Add some variance to make amounts more realistic
        $baseAmount = rand($min, $max);
        $variance = rand(-50, 50); // ±50 peso variance

        return max(0, $baseAmount + $variance);
    }
}
