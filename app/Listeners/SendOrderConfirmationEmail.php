<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOrderConfirmationEmail
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load(['user', 'items', 'address']);
        $email = $order->customerEmail();

        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new OrderConfirmationMail($order));
        } catch (Throwable $e) {
            // Never fail the order because email could not be sent
            Log::warning('Order confirmation email failed', [
                'order_number' => $order->order_number,
                'email'        => $email,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}
