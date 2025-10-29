<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['invoice_id','status','amount_cents','currency','method','external_id','paid_at','error_code','error_message','meta'];

    protected $casts = [
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
}

