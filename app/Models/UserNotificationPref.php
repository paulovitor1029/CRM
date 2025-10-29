<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotificationPref extends Model
{
    use HasFactory;

    protected $table = 'user_notification_prefs';

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'preferences', 'push_subscriptions'
    ];

    protected $casts = [
        'preferences' => 'array',
        'push_subscriptions' => 'array',
    ];
}

