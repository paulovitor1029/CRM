<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['organization_id','subject_type','subject_id','actor_type','actor_id','action','resource','resource_id','fields','ip','user_agent','occurred_at'];

    public $timestamps = true;

    protected $casts = [
        'fields' => 'array',
        'occurred_at' => 'datetime',
    ];
}
