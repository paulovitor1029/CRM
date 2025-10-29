<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'key', 'name', 'target_response_minutes', 'target_resolution_minutes', 'working_hours', 'active'
    ];

    protected $casts = [
        'target_response_minutes' => 'int',
        'target_resolution_minutes' => 'int',
        'working_hours' => 'array',
        'active' => 'boolean',
    ];
}

