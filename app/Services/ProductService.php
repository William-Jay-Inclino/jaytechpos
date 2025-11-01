<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Get best selling products for a specific date range and user.
     */
    public function getBestSellingProducts(string|Carbon $startDate, string|Carbon $endDate, int $userId, int $limit = 10): Collection
    {
        $startDate = is_string($startDate) ? Carbon::parse($startDate) : $startDate->copy();
        $endDate = is_string($endDate) ? Carbon::parse($endDate) : $endDate->copy();

        return Product::select('products.*')
            ->selectRaw('SUM(sales_items.quantity) as total_sold')
            ->join('sales_items', 'products.id', '=', 'sales_items.product_id')
            ->join('sales', 'sales_items.sale_id', '=', 'sales.id')
            ->where('sales.user_id', $userId)
            ->whereBetween('sales.transaction_date', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->groupBy('products.id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }
}
