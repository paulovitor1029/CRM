<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantConfig extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['tenant_id','scope','version','data','updated_by'];
    protected $casts = ['data' => 'array'];
}

