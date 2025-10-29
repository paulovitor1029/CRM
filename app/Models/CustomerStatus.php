<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerStatus extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'label', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
