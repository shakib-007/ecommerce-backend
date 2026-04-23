<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Services\Admin\AdminOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function __construct(private AdminOrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->list($request->all());

        return response()->json([
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->orderService->getDetail($id);

        return response()->json([
            'data' => new OrderDetailResource($order),
        ]);
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        string $id
    ): JsonResponse {
        $order = \App\Models\Order::findOrFail($id);

        try {
            $order = $this->orderService->updateStatus(
                $order,
                $request->status,
                $request->note
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Order status updated to '{$request->status}'.",
            'data'    => new OrderDetailResource($order),
        ]);
    }
}