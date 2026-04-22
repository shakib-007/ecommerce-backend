<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * POST /api/v1/payment/initiate/{orderId}
     * Initiate payment after order is placed.
     * Returns SSLCommerz redirect URL or COD confirmation.
     */
    public function initiate(Request $request, string $orderId): JsonResponse
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->with(['latestPayment', 'user', 'address'])
            ->firstOrFail();

        // Don't re-initiate if already paid
        if ($order->isPaid()) {
            return response()->json([
                'message' => 'This order has already been paid.',
            ], 422);
        }

        $result = $this->paymentService->initiatePayment($order);

        if (!$result->success) {
            return response()->json([
                'message' => $result->message,
            ], 422);
        }

        // COD — no redirect needed
        if ($order->latestPayment->gateway === 'cod') {
            return response()->json([
                'message'        => 'Cash on delivery order is confirmed.',
                'payment_method' => 'cod',
                'order_number'   => $order->order_number,
            ]);
        }

        // SSLCommerz — return redirect URL to frontend
        return response()->json([
            'payment_method' => 'sslcommerz',
            'redirect_url'   => $result->redirectUrl,
        ]);
    }

    // ── SSLCommerz Callbacks ──────────────────────────────────────
    // These are NOT API endpoints — SSLCommerz calls these directly
    // so they must be outside auth:sanctum middleware

    /**
     * POST /api/v1/payment/sslcommerz/success
     */
    public function sslSuccess(Request $request): RedirectResponse
    {
        $url = $this->paymentService->handleSslCommerzCallback(
            'success',
            $request->all()
        );
        return redirect($url);
    }

    /**
     * POST /api/v1/payment/sslcommerz/fail
     */
    public function sslFail(Request $request): RedirectResponse
    {
        $url = $this->paymentService->handleSslCommerzCallback(
            'fail',
            $request->all()
        );
        return redirect($url);
    }

    /**
     * POST /api/v1/payment/sslcommerz/cancel
     */
    public function sslCancel(Request $request): RedirectResponse
    {
        $url = $this->paymentService->handleSslCommerzCallback(
            'cancel',
            $request->all()
        );
        return redirect($url);
    }

    /**
     * POST /api/v1/payment/sslcommerz/ipn
     * Instant Payment Notification — server to server.
     * Most reliable callback — always implement this.
     */
    public function sslIpn(Request $request): JsonResponse
    {
        $this->paymentService->handleSslCommerzCallback(
            'ipn',
            $request->all()
        );

        // SSLCommerz expects a 200 response
        return response()->json(['status' => 'ok']);
    }
}