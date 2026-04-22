<?php

namespace App\Services\Payment;

class PaymentInitResult
{
    public function __construct(
        public bool   $success,
        public string $redirectUrl = '',
        public string $message     = '',
        public array  $data        = [],
    ) {}
}