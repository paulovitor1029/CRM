<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['subscription_id', 'action', 'before', 'after', 'user_id', 'origin'];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];
}

