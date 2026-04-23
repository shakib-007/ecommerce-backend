<?php

namespace App\Services\Admin;

use App\Models\Order;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminOrderService
{
    /**
     * Paginated orders with filters for admin table.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Order::with(['user', 'latestPayment'])
            ->withCount('items');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_number', 'ILIKE', "%{$filters['search']}%")
                  ->orWhereHas('user', fn($q2) =>
                      $q2->where('name', 'ILIKE', "%{$filters['search']}%")
                         ->orWhere('email', 'ILIKE', "%{$filters['search']}%")
                  );
            });
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['payment_status'])) {
            $query->whereHas('latestPayment', fn($q) =>
                $q->where('status', $filters['payment_status'])
            );
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Update order status with validation.
     * Certain status transitions are not allowed.
     */
    public function updateStatus(Order $order, string $newStatus, ?string $note = null): Order
    {
        $currentStatus = $order->status;

        // Define allowed transitions
        $allowedTransitions = [
            'pending'    => ['confirmed', 'cancelled'],
            'confirmed'  => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped'    => ['delivered'],
            'delivered'  => ['refunded'],
            'cancelled'  => [], // terminal state
            'refunded'   => [], // terminal state
        ];

        $allowed = $allowedTransitions[$currentStatus] ?? [];

        if (!in_array($newStatus, $allowed)) {
            throw new \InvalidArgumentException(
                "Cannot transition order from '{$currentStatus}' to '{$newStatus}'."
            );
        }

        $order->update(['status' => $newStatus]);

        // Auto-update payment status on refund
        if ($newStatus === 'refunded') {
            $order->latestPayment?->update(['status' => 'refunded']);
        }

        return $order->fresh(['user', 'latestPayment', 'items', 'address']);
    }

    /**
     * Full order detail for admin view.
     */
    public function getDetail(string $orderId): Order
    {
        return Order::with([
            'user',
            'address',
            'items.variant.product.images',
            'latestPayment',
            'coupons',
        ])->findOrFail($orderId);
    }
}