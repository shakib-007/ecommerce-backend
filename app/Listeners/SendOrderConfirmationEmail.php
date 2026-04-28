<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Mail;

// ShouldQueue → runs in background, doesn't slow down the response
class SendOrderConfirmationEmail
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load(['user', 'items', 'address']);

        Mail::to($order->user->email)
            ->send(new OrderConfirmationMail($order));
    }
}