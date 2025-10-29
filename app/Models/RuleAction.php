<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RuleAction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['rule_id','type','position','params'];

    protected $casts = [
        'params' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(RuleDefinition::class, 'rule_id');
    }
}

