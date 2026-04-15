<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    use HasUuids;

    protected $fillable = [
        'code', 'type', 'value', 'min_order_amount',
        'max_uses', 'used_count', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'value'            => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'expires_at'       => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_coupons')
                    ->withPivot('discount_applied')
                    ->withTimestamps();
    }

    public function isValid(float $orderAmount): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;
        if ($orderAmount < $this->min_order_amount) return false;
        return true;
    }

    public function calculateDiscount(float $orderAmount): float
    {
        return $this->type === 'percentage'
            ? round($orderAmount * ($this->value / 100), 2)
            : min($this->value, $orderAmount); // flat discount can't exceed order total
    }
}