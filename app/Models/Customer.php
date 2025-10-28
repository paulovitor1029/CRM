<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id', 'external_id', 'name', 'email', 'phone', 'status', 'funnel_stage', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(CustomerTag::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(CustomerHistory::class);
    }
}

