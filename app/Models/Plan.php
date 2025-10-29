<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id', 'product_id', 'name', 'billing_interval', 'billing_period', 'trial_days', 'pro_rata', 'courtesy_days', 'limits'
    ];

    protected $casts = [
        'pro_rata' => 'boolean',
        'trial_days' => 'int',
        'courtesy_days' => 'int',
        'limits' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

