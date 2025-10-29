<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name','slug','created_by','settings','status'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')->withPivot(['role','permissions','invited_at','accepted_at'])->withTimestamps();
    }
}

