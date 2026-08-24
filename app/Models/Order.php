<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, BelongsToMany, HasOne};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'user_id', 'guest_name', 'guest_email', 'guest_phone',
        'address_id', 'order_number', 'status',
        'subtotal', 'discount_total', 'shipping_fee', 'total', 'notes',
    ];

    protected $casts = [
        'subtotal'       => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_fee'   => 'decimal:2',
        'total'          => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'order_coupons')
                    ->withPivot('discount_applied')
                    ->withTimestamps();
    }

    /**
     * Latest payment attempt.
     * Avoid latestOfMany()/MAX(uuid) — PostgreSQL does not support MAX(uuid).
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->whereRaw(
            'payments.id = (
                SELECT p.id
                FROM payments AS p
                WHERE p.order_id = payments.order_id
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT 1
            )'
        );
    }

    public function isPaid(): bool
    {
        return $this->payments()->where('status', 'paid')->exists();
    }

    public function customerName(): string
    {
        return $this->user?->name ?? $this->guest_name ?? 'Guest';
    }

    public function customerEmail(): ?string
    {
        return $this->user?->email ?? $this->guest_email;
    }

    public function customerPhone(): ?string
    {
        return $this->user?->phone ?? $this->guest_phone;
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }
}