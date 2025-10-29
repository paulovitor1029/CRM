<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['invoice_id','type','reference_id','description','quantity','unit_price_cents','total_cents'];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
}

