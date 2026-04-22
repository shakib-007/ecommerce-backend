<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Cart extends Model
{
    use HasUuids;

    protected $fillable = ['user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculate cart subtotal from loaded items.
     * Always use variant price, not product base_price.
     */
    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(
            fn($item) => $item->variant->price * $item->qty
        );
    }

    /**
     * Total number of items in cart.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('qty');
    }
}