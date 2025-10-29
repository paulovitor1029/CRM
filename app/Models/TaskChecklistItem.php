<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskChecklistItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['task_id', 'description', 'checked', 'checked_at'];

    protected $casts = [
        'checked' => 'boolean',
        'checked_at' => 'datetime',
    ];
}

