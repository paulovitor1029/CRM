<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleDefinition extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['organization_id','name','event_key','conditions','enabled'];

    protected $casts = [
        'conditions' => 'array',
        'enabled' => 'boolean',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(RuleAction::class, 'rule_id')->orderBy('position');
    }
}
