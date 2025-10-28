<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['customer_id', 'action', 'before', 'after', 'user_id', 'origin'];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

