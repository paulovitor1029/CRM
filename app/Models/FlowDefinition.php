<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlowDefinition extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id', 'key', 'version', 'name', 'description', 'published_at', 'frozen',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'frozen' => 'boolean',
    ];

    public function states(): HasMany
    {
        return $this->hasMany(FlowState::class);
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(FlowTransition::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FlowLog::class);
    }
}
