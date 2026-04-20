<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'price',
        'compare_price', 'stock_qty', 'is_active',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'variant_attributes',
            'variant_id',
            'attribute_value_id'
        )->with('group'); // always eager-load the group name
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_qty', '>', 0);
    }
}
