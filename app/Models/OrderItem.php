<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'variant_id', 'qty',
        'unit_price', 'variant_snapshot', 'line_total',
    ];

    protected $casts = [
        'variant_snapshot' => 'array', // auto JSON encode/decode
        'unit_price'       => 'decimal:2',
        'line_total'       => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}