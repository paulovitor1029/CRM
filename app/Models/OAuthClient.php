<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OAuthClient extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'oauth_clients';

    protected $fillable = ['name','secret','scopes','active'];

    protected $casts = [
        'scopes' => 'array',
        'active' => 'boolean',
    ];
}

