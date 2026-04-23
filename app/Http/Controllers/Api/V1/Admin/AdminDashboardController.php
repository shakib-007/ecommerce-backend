<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __construct(
        private AdminDashboardService $dashboardService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'stats'            => $this->dashboardService->getStats(),
            'revenue_chart'    => $this->dashboardService->getRevenueChart(),
            'recent_orders'    => OrderResource::collection(
                $this->dashboardService->getRecentOrders()
            ),
            'top_products'     => $this->dashboardService->getTopProducts(),
            'order_breakdown'  => $this->dashboardService->getOrderStatusBreakdown(),
        ]);
    }
}