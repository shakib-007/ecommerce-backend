<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};

class CartItem extends Model
{
    use HasUuids;

    protected $fillable = ['cart_id', 'variant_id', 'qty'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Line total for this item.
     */
    public function getLineTotalAttribute(): float
    {
        return $this->variant->price * $this->qty;
    }
}