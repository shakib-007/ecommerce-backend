<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};

class AttributeValue extends Model
{
    use HasUuids;

    protected $fillable = ['group_id', 'value', 'meta'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'group_id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'variant_attributes',
            'attribute_value_id',
            'variant_id'
        );
    }
}