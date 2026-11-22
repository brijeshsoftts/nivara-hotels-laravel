<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    const UPDATED_AT = null;

    protected $table = 'invoices';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'base_amount' => 'integer',
            'extra_charges' => 'integer',
            'tax_amount' => 'integer',
            'discount_amount' => 'integer',
            'total_amount' => 'integer',
            'paid_amount' => 'integer',
            'due_amount' => 'integer',
            'issued_at' => 'datetime',
            'due_date' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }
}
