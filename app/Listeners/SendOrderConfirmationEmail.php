<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

// ShouldQueue → runs in background, doesn't slow down the response
class SendOrderConfirmationEmail implements ShouldQueue
{
    public int $tries = 3; // retry 3 times on failure
    public int $backoff = 60; // wait 60 seconds between retries

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load(['user', 'items', 'address']);

        Mail::to($order->user->email)
            ->send(new OrderConfirmationMail($order));
    }
}