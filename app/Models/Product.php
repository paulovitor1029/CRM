<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id', 'category_id', 'name', 'sku', 'price_cents', 'currency', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'price_cents' => 'int',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
