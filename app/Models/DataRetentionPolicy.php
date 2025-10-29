<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataRetentionPolicy extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['organization_id','entity','retention_days','action','conditions','active'];

    protected $casts = [
        'retention_days' => 'int',
        'conditions' => 'array',
        'active' => 'boolean',
    ];
}
