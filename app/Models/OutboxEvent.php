<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'outbox';

    protected $fillable = ['organization_id','event_key','payload','occurred_at','status','attempts','last_error','processed_at'];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
