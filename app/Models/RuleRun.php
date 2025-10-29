<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuleRun extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['rule_id','outbox_id','status','attempts','logs','started_at','finished_at'];

    protected $casts = [
        'logs' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}

