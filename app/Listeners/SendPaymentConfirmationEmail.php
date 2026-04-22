<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentConfirmationEmail implements ShouldQueue
{
    public function handle(OrderPaid $event): void
    {
        // You can add payment confirmation email here later
        // For now just log it
        \Illuminate\Support\Facades\Log::info(
            "Payment confirmed for order: {$event->order->order_number}"
        );
    }
}