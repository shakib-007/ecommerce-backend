<?php

namespace App\Services\Payment;

class PaymentVerifyResult
{
    public function __construct(
        public bool   $success,
        public string $transactionId = '',
        public string $message       = '',
        public array  $data          = [],
    ) {}
}