<?php

return [    
    'store_id'       => env('SSLCOMMERZ_STORE_ID'),
    'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
    'is_live'        => env('SSLCOMMERZ_IS_LIVE', false),
    'success_url'    => env('BACKEND_URL') . '/api/v1/payment/sslcommerz/success',
    'fail_url'       => env('BACKEND_URL') . '/api/v1/payment/sslcommerz/fail',
    'cancel_url'     => env('BACKEND_URL') . '/api/v1/payment/sslcommerz/cancel',
    'ipn_url'        => env('BACKEND_URL') . '/api/v1/payment/sslcommerz/ipn',
];

