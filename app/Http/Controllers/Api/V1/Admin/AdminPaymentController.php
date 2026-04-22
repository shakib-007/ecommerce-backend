<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class AdminPaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * POST /api/v1/admin/orders/{orderId}/confirm-cod
     * Admin confirms COD payment was collected on delivery.
     */
    public function confirmCod(string $orderId): JsonResponse
    {
        $order = Order::where('id', $orderId)
            ->with('latestPayment')
            ->firstOrFail();

        if ($order->isPaid()) {
            return response()->json([
                'message' => 'This order is already paid.',
            ], 422);
        }

        $this->paymentService->confirmCodPayment($order);

        return response()->json([
            'message' => 'COD payment confirmed. Order marked as delivered.',
        ]);
    }

    /**
     * POST /api/v1/admin/orders/{orderId}/refund
     * Mark payment as refunded.
     */
    public function refund(string $orderId): JsonResponse
    {
        $order   = Order::with('latestPayment')->findOrFail($orderId);
        $payment = $order->latestPayment;

        if ($payment->status !== 'paid') {
            return response()->json([
                'message' => 'Only paid orders can be refunded.',
            ], 422);
        }

        $payment->update(['status' => 'refunded']);
        $order->update(['status' => 'refunded']);

        return response()->json([
            'message' => 'Order marked as refunded.',
        ]);
    }
}