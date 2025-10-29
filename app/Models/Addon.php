<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Addon extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id', 'product_id', 'name', 'billing_interval', 'billing_period', 'active', 'limits'
    ];

    protected $casts = [
        'active' => 'boolean',
        'limits' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

