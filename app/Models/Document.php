<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id', 'title', 'content', 'owner_id', 'sector_id', 'current_version', 'autosave_at', 'meta'
    ];

    protected $casts = [
        'autosave_at' => 'datetime',
        'current_version' => 'int',
        'meta' => 'array',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }
}
