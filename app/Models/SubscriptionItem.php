<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['subscription_id', 'item_type', 'item_id', 'quantity', 'price_cents', 'currency'];

    protected $casts = [
        'quantity' => 'int',
        'price_cents' => 'int',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}

