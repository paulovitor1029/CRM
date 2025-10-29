<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEndpoint extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['organization_id','event_key','url','secret','headers','active'];

    protected $casts = [
        'headers' => 'array',
        'active' => 'boolean',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'endpoint_id');
    }
}
