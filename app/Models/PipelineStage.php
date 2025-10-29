<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineStage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'pipeline_id', 'key', 'name', 'position', 'initial', 'terminal',
    ];

    protected $casts = [
        'initial' => 'boolean',
        'terminal' => 'boolean',
        'position' => 'int',
    ];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }
}

