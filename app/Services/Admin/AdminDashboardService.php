<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * Main dashboard stats card data.
     */
    public function getStats(): array
    {
        $now          = now();
        $thisMonth    = $now->copy()->startOfMonth();
        $lastMonth    = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Total revenue (paid orders only)
        $totalRevenue = Payment::where('status', 'paid')
            ->sum('amount');

        // This month revenue
        $thisMonthRevenue = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$thisMonth, $now])
            ->sum('amount');

        // Last month revenue
        $lastMonthRevenue = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$lastMonth, $lastMonthEnd])
            ->sum('amount');

        // Revenue growth percentage
        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        return [
            'revenue' => [
                'total'        => round($totalRevenue, 2),
                'this_month'   => round($thisMonthRevenue, 2),
                'last_month'   => round($lastMonthRevenue, 2),
                'growth'       => $revenueGrowth, // percentage
            ],
            'orders' => [
                'total'      => Order::count(),
                'this_month' => Order::where('created_at', '>=', $thisMonth)->count(),
                'pending'    => Order::where('status', 'pending')->count(),
                'processing' => Order::where('status', 'processing')->count(),
            ],
            'customers' => [
                'total'      => User::where('role', 'customer')->count(),
                'this_month' => User::where('role', 'customer')
                    ->where('created_at', '>=', $thisMonth)
                    ->count(),
            ],
            'products' => [
                'total'       => Product::count(),
                'active'      => Product::where('is_active', true)->count(),
                'out_of_stock' => Product::whereHas('variants', fn($q) =>
                    $q->where('stock_qty', 0)
                )->count(),
            ],
        ];
    }

    /**
     * Revenue data for chart — last 12 months.
     */
    public function getRevenueChart(): array
    {
        return Payment::where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw("TO_CHAR(paid_at, 'Mon YYYY') as month"),
                DB::raw("DATE_TRUNC('month', paid_at) as month_date"),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('month', 'month_date')
            ->orderBy('month_date')
            ->get()
            ->map(fn($row) => [
                'month'   => $row->month,
                'revenue' => round($row->revenue, 2),
                'orders'  => $row->orders,
            ])
            ->toArray();
    }

    /**
     * Recent 10 orders for dashboard table.
     */
    public function getRecentOrders(): \Illuminate\Database\Eloquent\Collection
    {
        return Order::with(['user', 'latestPayment'])
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Top 5 selling products by revenue.
     */
    public function getTopProducts(): array
    {
        return DB::table('order_items')
            ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.status', '!=', 'refunded')
            ->select(
                'products.id',
                'products.name',
                'products.slug',
                DB::raw('SUM(order_items.qty) as total_sold'),
                DB::raw('SUM(order_items.line_total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.slug')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Order status breakdown for pie chart.
     */
    public function getOrderStatusBreakdown(): array
    {
        return Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($row) => [$row->status => $row->count])
            ->toArray();
    }
}