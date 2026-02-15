<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Inventory;
use App\Services\ProductService;
use App\Services\SaleService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private SaleService $saleService,
        private UserService $userService
    ) {}

    public function index(): Response
    {
        $userId = Auth::id();
        $today = Carbon::today();
        $currentYear = Carbon::now()->year;

        // Daily Statistics
        $dailyStats = $this->getDailyStatistics($userId, $today);

        // Utang Statistics
        // $utangStats = $this->getUtangStatistics($userId, $today);

        // Best Selling Products
        $bestSellingProducts = $this->getBestSellingProducts($userId);

        // Cash Flow Data
        $cashFlowData = $this->userService->getCashFlow($currentYear, $userId);

        // Low Stock Products
        $lowStockProducts = $this->getLowStockProducts($userId);

        // Sales Chart Data (last 30 days)
        // $salesChartData = $this->getSalesChartData($userId);

        return Inertia::render('Dashboard', [
            'dailyStats' => $dailyStats,
            // 'utangStats' => $utangStats,
            'bestSellingProducts' => $bestSellingProducts,
            'cashFlowData' => $cashFlowData,
            'lowStockProducts' => $lowStockProducts,
            // 'salesChartData' => $salesChartData,
            'currentYear' => $currentYear,
        ]);
    }

    private function getDailyStatistics(int $userId, Carbon $today): array
    {
        // Total sales today
        $totalSalesToday = $this->saleService->getTotalSales($today, $today, $userId);

        // Cash sales today
        $cashSalesToday = $this->saleService->getTotalSales($today, $today, $userId, 'cash');

        // Utang sales today
        $utangSalesToday = $this->saleService->getTotalSales($today, $today, $userId, 'utang');

        return [
            'total_sales_today' => $totalSalesToday,
            'cash_sales_today' => $cashSalesToday,
            'utang_sales_today' => $utangSalesToday,
        ];
    }

    private function getUtangStatistics(int $userId, Carbon $today): array
    {
        // Total amount receivable from customer utangs using customer running balances
        $totalAmountReceivable = Customer::where('user_id', $userId)
            ->where('has_utang', true)
            ->get()
            ->sum(fn ($customer) => $customer->running_utang_balance);

        return [
            'total_amount_receivable' => $totalAmountReceivable,
        ];
    }

    private function getLowStockProducts(int $userId): array
    {
        return Inventory::lowStock()
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with('product')
            ->orderBy('quantity', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($inventory) {
                return [
                    'product_id' => $inventory->product_id,
                    'product_name' => $inventory->product->product_name,
                    'quantity' => number_format((float) $inventory->quantity, 2, '.', ''),
                    'low_stock_threshold' => number_format((float) $inventory->low_stock_threshold, 2, '.', ''),
                ];
            })
            ->toArray();
    }

    private function getBestSellingProducts(int $userId): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();
        $yearStart = Carbon::now()->startOfYear();

        return [
            'today' => $this->productService->getBestSellingProducts($today, $today, $userId, 8),
            'week' => $this->productService->getBestSellingProducts($weekStart, $today, $userId, 8),
            'month' => $this->productService->getBestSellingProducts($monthStart, $today, $userId, 8),
            'year' => $this->productService->getBestSellingProducts($yearStart, $today, $userId, 8),
        ];
    }

    private function getSalesChartData(int $userId): array
    {
        $currentDate = Carbon::today();
        $startDate = Carbon::now()->startOfYear(); // January 1st of current year

        $salesData = [];
        $labels = [];

        // Group by month for year-to-date view
        for ($month = $startDate->copy(); $month->lte($currentDate); $month->addMonth()) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            // Don't go beyond current date
            if ($monthEnd->gt($currentDate)) {
                $monthEnd = $currentDate->copy();
            }

            $monthlySales = $this->saleService->getTotalSales($monthStart, $monthEnd, $userId);
            $salesData[] = $monthlySales;
            $labels[] = $month->format('M');
        }

        return [
            'labels' => $labels,
            'data' => $salesData,
        ];
    }

    public function getCashFlowData(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $userId = Auth::id();

        $cashFlowData = $this->userService->getCashFlow($year, $userId);

        return response()->json([
            'data' => $cashFlowData,
            'currentYear' => (int) $year,
        ]);
    }

    public function getSalesChartDataForYear(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $userId = Auth::id();

        $salesChartData = $this->getSalesChartDataByYear($userId, (int) $year);

        return response()->json([
            'data' => $salesChartData,
            'currentYear' => (int) $year,
        ]);
    }

    public function getBestSellingProductsForYear(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $userId = Auth::id();

        $bestSellingProducts = $this->getBestSellingProductsByYear($userId, (int) $year);

        return response()->json([
            'data' => $bestSellingProducts,
            'currentYear' => (int) $year,
        ]);
    }

    private function getBestSellingProductsByYear(int $userId, int $year): array
    {
        $currentDate = Carbon::today();

        // Calculate date ranges for the specified year
        $yearStart = Carbon::create($year, 1, 1);
        $yearEnd = Carbon::create($year, 12, 31);

        // If it's the current year, adjust ranges accordingly
        if ($year === Carbon::now()->year) {
            $today = $currentDate;
            $weekStart = Carbon::now()->startOfWeek();
            $monthStart = Carbon::now()->startOfMonth();
        } else {
            // For past years, use the last day of the year
            $today = $yearEnd;
            $weekStart = $yearEnd->copy()->startOfWeek();
            $monthStart = $yearEnd->copy()->startOfMonth();
        }

        return [
            'today' => $year === Carbon::now()->year
                ? $this->productService->getBestSellingProducts($today, $today, $userId, 8)
                : [], // No "today" data for past years
            'week' => $this->productService->getBestSellingProducts($weekStart, $today, $userId, 8),
            'month' => $this->productService->getBestSellingProducts($monthStart, $today, $userId, 8),
            'year' => $this->productService->getBestSellingProducts($yearStart, $yearEnd, $userId, 8),
        ];
    }

    private function getSalesChartDataByYear(int $userId, int $year): array
    {
        $currentDate = Carbon::today();
        $startDate = Carbon::create($year, 1, 1); // January 1st of specified year
        $endDate = Carbon::create($year, 12, 31); // December 31st of specified year

        // If it's the current year, don't go beyond today
        if ($year === Carbon::now()->year) {
            $endDate = $currentDate;
        }

        $salesData = [];
        $labels = [];

        // Group by month for the selected year
        for ($month = $startDate->copy(); $month->lte($endDate); $month->addMonth()) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            // Don't go beyond current date for current year
            if ($year === Carbon::now()->year && $monthEnd->gt($currentDate)) {
                $monthEnd = $currentDate->copy();
            }

            $monthlySales = $this->saleService->getTotalSales($monthStart, $monthEnd, $userId);
            $salesData[] = $monthlySales;
            $labels[] = $month->format('M');
        }

        return [
            'labels' => $labels,
            'data' => $salesData,
        ];
    }
}
