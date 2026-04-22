<?php

namespace App\Services;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\CodGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentInitResult;
use App\Services\Payment\SslCommerzGateway;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Resolve the correct gateway by name.
     */
    public function gateway(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'sslcommerz' => app(SslCommerzGateway::class),
            'cod'        => app(CodGateway::class),
            default      => throw new \InvalidArgumentException(
                "Unknown payment gateway: {$name}"
            ),
        };
    }

    /**
     * Initiate payment for an order.
     * Returns redirect URL for SSLCommerz,
     * or empty string for COD.
     */
    public function initiatePayment(Order $order): PaymentInitResult
    {
        $payment = $order->latestPayment;

        if (!$payment) {
            throw new \RuntimeException('No payment record found for order.');
        }

        return $this->gateway($payment->gateway)
            ->initiate($order);
    }

    /**
     * Handle SSLCommerz success/fail/cancel/IPN callbacks.
     * Returns the frontend redirect URL.
     */
    public function handleSslCommerzCallback(
        string $type,
        array  $payload
    ): string {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL'));

        // Find order by order number (tran_id in SSLCommerz)
        $order = Order::where('order_number', $payload['tran_id'] ?? '')
            ->with('latestPayment')
            ->first();

        if (!$order) {
            Log::error('SSLCommerz callback: order not found', $payload);
            return "{$frontendUrl}/payment/failed?reason=order_not_found";
        }

        $payment = $order->latestPayment;

        // Handle cancel
        if ($type === 'cancel') {
            $payment->update(['status' => 'failed', 'meta' => $payload]);
            return "{$frontendUrl}/payment/cancelled?order={$order->order_number}";
        }

        // Handle fail
        if ($type === 'fail') {
            $payment->update(['status' => 'failed', 'meta' => $payload]);
            $order->update(['status' => 'cancelled']);
            return "{$frontendUrl}/payment/failed?order={$order->order_number}";
        }

        // Handle success & IPN — verify with SSLCommerz
        $result = $this->gateway('sslcommerz')->verify($payload);

        if ($result->success) {
            $this->confirmPayment($order, $payment, $result->transactionId, $payload);
            return "{$frontendUrl}/payment/success?order={$order->order_number}";
        }

        $payment->update(['status' => 'failed', 'meta' => $payload]);
        return "{$frontendUrl}/payment/failed?order={$order->order_number}";
    }

    /**
     * Mark payment as paid and order as confirmed.
     * Called by SSLCommerz callback and admin COD confirmation.
     */
    public function confirmPayment(
        Order   $order,
        Payment $payment,
        string  $transactionId,
        array   $meta = []
    ): void {
        // Prevent double-processing
        if ($payment->status === 'paid') {
            return;
        }

        $payment->update([
            'status'         => 'paid',
            'gateway_txn_id' => $transactionId,
            'paid_at'        => now(),
            'meta'           => $meta,
        ]);

        $order->update(['status' => 'confirmed']);

        // Fire event — triggers any post-payment listeners
        OrderPaid::dispatch($order);

        Log::info("Payment confirmed for order {$order->order_number}");
    }

    /**
     * Admin manually confirms COD payment on delivery.
     */
    public function confirmCodPayment(Order $order): void
    {
        $payment = $order->latestPayment;

        if ($payment->gateway !== 'cod') {
            throw new \RuntimeException('This order is not a COD order.');
        }

        $this->confirmPayment(
            $order,
            $payment,
            'COD-' . now()->timestamp,
            ['confirmed_by' => 'admin', 'confirmed_at' => now()->toISOString()]
        );

        $order->update(['status' => 'delivered']);
    }
}