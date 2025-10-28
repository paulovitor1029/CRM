<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerTag extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['customer_id', 'tag'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

