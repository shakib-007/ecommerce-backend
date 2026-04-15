<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, BelongsToMany};

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'address_id', 'order_number', 'status',
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

    // Latest payment attempt
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isPaid(): bool
    {
        return $this->payments()->where('status', 'paid')->exists();
    }
}