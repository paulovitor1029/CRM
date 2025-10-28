<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowState extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'flow_definition_id', 'key', 'name', 'initial', 'terminal',
    ];

    protected $casts = [
        'initial' => 'boolean',
        'terminal' => 'boolean',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id');
    }
}

