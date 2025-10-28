<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowTransition extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'flow_definition_id', 'from_state_id', 'to_state_id', 'key',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(FlowDefinition::class, 'flow_definition_id');
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(FlowState::class, 'from_state_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(FlowState::class, 'to_state_id');
    }
}

