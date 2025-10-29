<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantCustomField extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['organization_id','entity','name','key','type','required','visibility_roles','options','order','active','version','updated_by'];

    protected $casts = [
        'visibility_roles' => 'array',
        'options' => 'array',
        'required' => 'boolean',
        'active' => 'boolean',
    ];
}
