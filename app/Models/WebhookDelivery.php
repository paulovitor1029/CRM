<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'endpoint_id','outbox_id','event_key','payload','idempotency_key','status','attempts','next_attempt_at','last_error','response_status','delivered_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'next_attempt_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'endpoint_id');
    }
}

