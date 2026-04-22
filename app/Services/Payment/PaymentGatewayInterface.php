<?php

namespace App\Services\Payment;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * Initiate payment — returns data needed to redirect user.
     */
    public function initiate(Order $order): PaymentInitResult;

    /**
     * Verify a payment after gateway callback.
     */
    public function verify(array $payload): PaymentVerifyResult;
}