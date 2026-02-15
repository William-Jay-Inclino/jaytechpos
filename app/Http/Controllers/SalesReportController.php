<?php

namespace App\Http\Controllers;

use App\Http\Resources\SaleResource;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SalesReportController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Sale::class);

        $filters = $this->validateFilters($request);

        // Get sales data
        $salesData = $this->getSalesDataQuery($filters);

        // Get chart data
        $chartData = $this->getChartDataQuery($filters);

        // Get payment type distribution
        $paymentTypeData = $this->getPaymentTypeDistribution($filters);

        return Inertia::render('sales-report/Index', [
            'filters' => $filters,
            'sales' => $salesData,
            'chartData' => $chartData,
            'paymentTypeData' => $paymentTypeData,
        ]);
    }

    public function getSalesData(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);
        $salesData = $this->getSalesDataQuery($filters);

        return response()->json([
            'data' => $salesData,
        ]);
    }

    public function getChartData(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);
        $chartData = $this->getChartDataQuery($filters);

        return response()->json([
            'data' => $chartData,
        ]);
    }

    public function getPaymentTypeData(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);
        $paymentTypeData = $this->getPaymentTypeDistribution($filters);

        return response()->json([
            'data' => $paymentTypeData,
        ]);
    }

    private function validateFilters(Request $request): array
    {
        $filters = $request->validate([
            'period' => 'nullable|string|in:today,week,month,year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'payment_type' => 'nullable|string|in:cash,utang,all',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Set defaults
        $filters['period'] = $filters['period'] ?? 'month';
        $filters['payment_type'] = $filters['payment_type'] ?? 'all';
        $filters['page'] = $filters['page'] ?? 1;
        $filters['per_page'] = $filters['per_page'] ?? 20;

        // Set date range based on period if not custom
        if ($filters['period'] !== 'custom') {
            $dateRange = $this->getDateRangeForPeriod($filters['period']);
            $filters['start_date'] = $dateRange['start'];
            $filters['end_date'] = $dateRange['end'];
        }

        return $filters;
    }

    private function getDateRangeForPeriod(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [
                'start' => $now->copy()->startOfDay()->format('Y-m-d'),
                'end' => $now->copy()->endOfDay()->format('Y-m-d'),
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek()->format('Y-m-d'),
                'end' => $now->copy()->endOfWeek()->format('Y-m-d'),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $now->copy()->endOfMonth()->format('Y-m-d'),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear()->format('Y-m-d'),
                'end' => $now->copy()->endOfYear()->format('Y-m-d'),
            ],
            default => [
                'start' => $now->copy()->startOfDay()->format('Y-m-d'),
                'end' => $now->copy()->endOfDay()->format('Y-m-d'),
            ],
        };
    }

    private function getSalesDataQuery(array $filters): array
    {
        $query = Sale::with(['user', 'customer', 'salesItems.product'])
            ->where('user_id', Auth::id());

        // Apply date filters
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('transaction_date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);
        }

        // Apply payment type filter
        if (! empty($filters['payment_type']) && $filters['payment_type'] !== 'all') {
            $query->where('payment_type', $filters['payment_type']);
        }

        $sales = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(
                perPage: $filters['per_page'],
                page: $filters['page']
            );

        return [
            'data' => SaleResource::collection($sales->items())->resolve(),
            'pagination' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
                'from' => $sales->firstItem(),
                'to' => $sales->lastItem(),
            ],
            'summary' => $this->getSalesSummary($filters),
        ];
    }

    private function getSalesSummary(array $filters): array
    {
        $query = Sale::where('user_id', Auth::id());

        // Apply date filters only (payment type filter should not affect summary stats)
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('transaction_date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_transactions,
            SUM(total_amount) as total_sales,
            SUM(paid_amount) as total_paid,
            SUM(CASE WHEN payment_type = \'cash\' THEN total_amount ELSE 0 END) as cash_sales,
            SUM(CASE WHEN payment_type = \'utang\' THEN total_amount ELSE 0 END) as utang_sales,
            AVG(total_amount) as average_sale
        ')->first();

        return [
            'total_transactions' => (int) $summary->total_transactions,
            'total_sales' => (float) $summary->total_sales ?? 0,
            'total_paid' => (float) $summary->total_paid ?? 0,
            'cash_sales' => (float) $summary->cash_sales ?? 0,
            'utang_sales' => (float) $summary->utang_sales ?? 0,
            'average_sale' => (float) $summary->average_sale ?? 0,
        ];
    }

    private function getChartDataQuery(array $filters): array
    {
        $query = Sale::where('user_id', Auth::id());

        // Apply date filters only (payment type filter should not affect chart)
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('transaction_date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);
        }

        // Get all sales data ordered by date
        $salesData = $query->selectRaw('
            DATE(transaction_date) as sale_date,
            SUM(total_amount) as daily_sales,
            COUNT(*) as transaction_count
        ')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();

        // Process data to group by weeks
        $weeklyData = [];
        $labels = [];
        $data = [];

        foreach ($salesData as $sale) {
            $saleDate = Carbon::parse($sale->sale_date);
            $weekStart = $saleDate->copy()->startOfWeek();
            $weekEnd = $saleDate->copy()->endOfWeek();

            // Create a unique key for the week
            $weekKey = $weekStart->format('Y-W');

            if (! isset($weeklyData[$weekKey])) {
                $weeklyData[$weekKey] = [
                    'week_start' => $weekStart,
                    'week_end' => $weekEnd,
                    'total_sales' => 0,
                    'transaction_count' => 0,
                ];
            }

            $weeklyData[$weekKey]['total_sales'] += (float) $sale->daily_sales;
            $weeklyData[$weekKey]['transaction_count'] += $sale->transaction_count;
        }

        // Sort by week and prepare final data
        ksort($weeklyData);

        foreach ($weeklyData as $weekData) {
            $weekStart = $weekData['week_start'];
            $weekEnd = $weekData['week_end'];

            // Format week range label
            if ($weekStart->format('M') === $weekEnd->format('M')) {
                // Same month: "Mar 1-7"
                $labels[] = $weekStart->format('M j').'-'.$weekEnd->format('j');
            } else {
                // Different months: "Mar 30 - Apr 5"
                $labels[] = $weekStart->format('M j').' - '.$weekEnd->format('M j');
            }

            $data[] = $weekData['total_sales'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getPaymentTypeDistribution(array $filters): array
    {
        $query = Sale::where('user_id', Auth::id());

        // Apply date filters
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('transaction_date', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);
        }

        // Don't apply payment type filter for pie chart - we want to show distribution

        $distribution = $query->selectRaw('
            payment_type,
            COUNT(*) as count,
            SUM(total_amount) as total_amount
        ')
            ->groupBy('payment_type')
            ->get();

        $labels = [];
        $data = [];
        $colors = [
            'cash' => '#10B981', // emerald-500
            'utang' => '#F59E0B', // amber-500
        ];

        foreach ($distribution as $row) {
            $labels[] = ucfirst($row->payment_type);
            $data[] = (float) $row->total_amount;
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => array_values($colors),
        ];
    }
}
