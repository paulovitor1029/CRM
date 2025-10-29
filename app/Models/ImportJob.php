<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id','entity_type','status','file_key','original_filename','mapping','total_rows','valid_rows','invalid_rows','error_report_key','created_by','started_at','finished_at'
    ];

    protected $casts = [
        'mapping' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function errors(): HasMany { return $this->hasMany(ImportJobError::class); }
}
