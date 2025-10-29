<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id','customer_id','subscription_id','status','currency','subtotal_cents','discount_cents','courtesy_cents','total_cents','period_start','period_end','issued_at','due_at','meta'
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'meta' => 'array',
    ];

    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
}

