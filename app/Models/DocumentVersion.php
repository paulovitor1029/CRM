<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'document_id', 'version', 'content', 'created_by'
    ];

    protected $casts = [
        'version' => 'int',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}

