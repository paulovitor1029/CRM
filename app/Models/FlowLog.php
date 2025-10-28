<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'flow_definition_id', 'action', 'details', 'user_id',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id');
    }
}

