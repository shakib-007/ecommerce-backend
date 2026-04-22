<?php

namespace App\Services\Payment;

use App\Models\Order;

class CodGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order): PaymentInitResult
    {
        // COD needs no redirect — order is already confirmed
        return new PaymentInitResult(
            success:     true,
            redirectUrl: '',
            message:     'Cash on delivery order confirmed.',
            data:        ['method' => 'cod'],
        );
    }

    public function verify(array $payload): PaymentVerifyResult
    {
        // COD payment is verified manually by admin on delivery
        // This will be called when admin marks as delivered + paid
        return new PaymentVerifyResult(
            success:       true,
            transactionId: 'COD-' . now()->timestamp,
            message:       'COD payment confirmed by admin.',
            data:          $payload,
        );
    }
}