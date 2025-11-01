<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\ProductService;
use App\Services\SaleService;
use App\Services\UserService;
use App\Services\UtangPaymentService;
use App\Services\UtangTrackingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private SaleService $saleService,
        private UserService $userService,
        private UtangPaymentService $utangPaymentService,
        private UtangTrackingService $utangTrackingService
    ) {}

    public function index(): Response
    {
        $userId = auth()->id();
        $today = Carbon::today();
        $currentYear = Carbon::now()->year;

        // Daily Statistics
        $dailyStats = $this->getDailyStatistics($userId, $today);

        // Utang Statistics
        $utangStats = $this->getUtangStatistics($userId, $today);

        // Best Selling Products
        $bestSellingProducts = $this->getBestSellingProducts($userId);

        // Cash Flow Data
        $cashFlowData = $this->userService->getCashFlow($currentYear, $userId);

        // Sales Chart Data (last 30 days)
        $salesChartData = $this->getSalesChartData($userId);

        return Inertia::render('Dashboard', [
            'dailyStats' => $dailyStats,
            'utangStats' => $utangStats,
            'bestSellingProducts' => $bestSellingProducts,
            'cashFlowData' => $cashFlowData,
            'salesChartData' => $salesChartData,
            'currentYear' => $currentYear,
        ]);
    }

    private function getDailyStatistics(int $userId, Carbon $today): array
    {
        // Total sales today
        $totalSalesToday = $this->saleService->getTotalSales($today, $today, $userId);

        // Total cash received today (cash sales + utang payments)
        $cashSalesToday = $this->saleService->getTotalSales($today, $today, $userId, 'cash');
        $utangPaymentsToday = $this->utangPaymentService->getTotalUtangPayments($today, $today, $userId);
        $totalCashToday = $cashSalesToday + $utangPaymentsToday;

        return [
            'total_sales_today' => $totalSalesToday,
            'total_cash_today' => $totalCashToday,
        ];
    }

    private function getUtangStatistics(int $userId, Carbon $today): array
    {
        // Total amount receivable from customer utangs
        $totalAmountReceivable = $this->utangTrackingService->getTotalAmountReceivable($userId);

        return [
            'total_amount_receivable' => $totalAmountReceivable,
        ];
    }

    private function getBestSellingProducts(int $userId): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $monthStart = Carbon::now()->startOfMonth();
        $yearStart = Carbon::now()->startOfYear();

        return [
            'today' => $this->productService->getBestSellingProducts($today, $today, $userId, 10),
            'week' => $this->productService->getBestSellingProducts($weekStart, $today, $userId, 10),
            'month' => $this->productService->getBestSellingProducts($monthStart, $today, $userId, 10),
            'year' => $this->productService->getBestSellingProducts($yearStart, $today, $userId, 10),
        ];
    }

    private function getSalesChartData(int $userId): array
    {
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays(29); // Last 30 days

        $salesData = [];
        $labels = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dailySales = $this->saleService->getTotalSales($date, $date, $userId);
            $salesData[] = $dailySales;
            $labels[] = $date->format('M j');
        }

        return [
            'labels' => $labels,
            'data' => $salesData,
        ];
    }

    public function getCashFlowData(Request $request): array
    {
        $year = $request->input('year', Carbon::now()->year);
        $userId = auth()->id();

        return $this->userService->getCashFlow($year, $userId)->toArray();
    }
}
