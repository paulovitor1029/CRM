<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id', 'key', 'type', 'name', 'description', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class);
    }

    public function states(): HasMany
    {
        return $this->hasMany(CustomerPipelineState::class);
    }
}
