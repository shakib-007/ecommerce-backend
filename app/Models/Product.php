<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Product extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug',
        'description', 'base_price', 'is_featured', 'is_active',
    ];

    protected $casts = [
        'base_price'  => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Scope: only show active, non-deleted products publicly
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}