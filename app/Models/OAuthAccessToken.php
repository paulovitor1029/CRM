<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OAuthAccessToken extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'oauth_access_tokens';

    protected $fillable = ['client_id','token','scopes','expires_at'];

    protected $casts = [
        'scopes' => 'array',
        'expires_at' => 'datetime',
    ];
}

