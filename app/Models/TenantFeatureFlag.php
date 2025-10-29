<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantFeatureFlag extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['tenant_id','flag_key','enabled','version','updated_by'];
}

