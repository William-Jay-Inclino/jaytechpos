<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\UtangPayment;
use Illuminate\Support\Collection;

class UserService
{
    /**
     * Get cash flow data for a specific year and user.
     * Returns monthly breakdown of income, expenses, and net cash flow.
     */
    public function getCashFlow(int $year, int $userId): Collection
    {
        $months = collect(range(1, 12))->map(function ($month) use ($year, $userId) {
            $income = $this->getMonthlyIncome($year, $month, $userId);
            $expense = $this->getMonthlyExpense($year, $month, $userId);
            $cashFlow = $income - $expense;

            return [
                'month' => $month,
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'cash_flow' => round($cashFlow, 2),
            ];
        });

        return $months;
    }

    /**
     * Get total income for a specific month (cash sales + utang payments).
     */
    private function getMonthlyIncome(int $year, int $month, int $userId): float
    {
        // Cash sales income
        $cashSales = Sale::where('user_id', $userId)
            ->where('payment_type', 'cash')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->sum('total_amount');

        // Utang payments income
        $utangPayments = UtangPayment::where('user_id', $userId)
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('payment_amount');

        return $cashSales + $utangPayments;
    }

    /**
     * Get total expenses for a specific month.
     */
    private function getMonthlyExpense(int $year, int $month, int $userId): float
    {
        return Expense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->sum('amount');
    }
}
