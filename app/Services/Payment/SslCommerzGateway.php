<?php

namespace App\Services\Payment;

use App\Models\Order;
use Karim007\LaravelSslcommerzTokenize\Facade\SslCommerzTokenize;

class SslCommerzGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order): PaymentInitResult
    {
        // Load necessary relations
        $order->load(['user', 'address']);

        $postData = [
            // Transaction info
            'tran_id'      => $order->order_number,
            'total_amount' => $order->total,
            'currency'     => 'BDT',

            // Customer info
            'cus_name'     => $order->user->name,
            'cus_email'    => $order->user->email,
            'cus_phone'    => $order->user->phone ?? '01700000000',
            'cus_add1'     => $order->address->line1,
            'cus_city'     => $order->address->city,
            'cus_country'  => $order->address->country,
            'cus_postcode' => $order->address->postal_code ?? '1200',

            // Shipping info (same as billing for now)
            'ship_name'    => $order->user->name,
            'ship_add1'    => $order->address->line1,
            'ship_city'    => $order->address->city,
            'ship_country' => $order->address->country,
            'ship_postcode'=> $order->address->postal_code ?? '1200',

            // Product info
            'product_name'     => 'Order ' . $order->order_number,
            'product_category' => 'Mixed',
            'product_profile'  => 'general',

            // URLs — SSLCommerz redirects here
            'success_url' => config('sslcommerz.success_url'),
            'fail_url'    => config('sslcommerz.fail_url'),
            'cancel_url'  => config('sslcommerz.cancel_url'),
            'ipn_url'     => config('sslcommerz.ipn_url'),

            // Store our order ID so we can find it on callback
            'value_a'     => $order->id,
        ];

        try {
            $response = SslCommerzTokenize::makePayment($postData);

            if (isset($response['GatewayPageURL'])) {
                return new PaymentInitResult(
                    success:     true,
                    redirectUrl: $response['GatewayPageURL'],
                    data:        $response,
                );
            }

            return new PaymentInitResult(
                success: false,
                message: $response['failedreason'] ?? 'Payment initiation failed.',
            );

        } catch (\Exception $e) {
            return new PaymentInitResult(
                success: false,
                message: 'Payment gateway error: ' . $e->getMessage(),
            );
        }
    }

    public function verify(array $payload): PaymentVerifyResult
    {
        try {
            // Validate the IPN data with SSLCommerz
            $isValid = SslCommerzTokenize::validate_payment_from_sslcommerz($payload);

            if (!$isValid) {
                return new PaymentVerifyResult(
                    success: false,
                    message: 'Payment validation failed.',
                );
            }

            $status = $payload['status'] ?? '';

            if ($status !== 'VALID' && $status !== 'VALIDATED') {
                return new PaymentVerifyResult(
                    success: false,
                    message: "Payment status: {$status}",
                    data:    $payload,
                );
            }

            return new PaymentVerifyResult(
                success:       true,
                transactionId: $payload['bank_tran_id'] ?? $payload['tran_id'],
                data:          $payload,
            );

        } catch (\Exception $e) {
            return new PaymentVerifyResult(
                success: false,
                message: 'Verification error: ' . $e->getMessage(),
            );
        }
    }
}