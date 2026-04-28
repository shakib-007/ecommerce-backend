<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use Illuminate\Support\Facades\Log;

class SendPaymentConfirmationEmail
{
    public function handle(OrderPaid $event): void
    {
        // You can add payment confirmation email here later
        // For now just log it
        Log::info(
            "Payment confirmed for order: {$event->order->order_number}"
        );

        // Add payment confirmation email here when ready
        // Mail::to($event->order->user->email)
        //     ->send(new PaymentConfirmationMail($event->order));
    }
}