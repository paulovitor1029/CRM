<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportExport extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id', 'report_key', 'format', 'params', 'status', 'file_key', 'error', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'params' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

