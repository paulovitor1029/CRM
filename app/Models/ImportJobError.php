<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJobError extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['import_job_id','row_number','errors','row_data'];

    protected $casts = [
        'errors' => 'array',
        'row_data' => 'array',
    ];

    public function job(): BelongsTo { return $this->belongsTo(ImportJob::class, 'import_job_id'); }
}

