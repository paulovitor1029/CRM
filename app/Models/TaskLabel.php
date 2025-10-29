<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskLabel extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['organization_id', 'name', 'color'];
}
