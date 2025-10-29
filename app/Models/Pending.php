<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pending extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['task_id', 'assigned_to'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

