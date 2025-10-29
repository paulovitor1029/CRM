<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileObject extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id', 'user_id', 'disk', 'key', 'size', 'content_type', 'checksum', 'uploaded_at', 'meta'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'size' => 'int',
        'meta' => 'array',
    ];
}

