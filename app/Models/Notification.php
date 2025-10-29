<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id', 'user_id', 'type', 'title', 'body', 'data', 'channel', 'read_at', 'delivered_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];
}
