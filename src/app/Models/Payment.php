<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    const UPDATED_AT = null;

    protected $table = 'payments';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'amount' => 'integer',
            'amount_paise' => 'integer',
            'refunded_amount' => 'integer',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'order_creation_started_at' => 'datetime',
            'gateway_response' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
