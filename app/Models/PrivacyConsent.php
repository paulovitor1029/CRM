<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivacyConsent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['tenant_id','subject_type','subject_id','purpose','version','given_at','revoked_at','ip','user_agent','metadata'];

    protected $casts = [
        'given_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];
}

