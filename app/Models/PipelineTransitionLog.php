<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineTransitionLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'customer_id', 'pipeline_id', 'from_stage_id', 'to_stage_id', 'justification', 'user_id', 'origin',
    ];
}

