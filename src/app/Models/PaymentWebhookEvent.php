<?php

namespace App\Models;

class PaymentWebhookEvent extends Model
{
    public $timestamps = false;

    protected $table = 'payment_webhook_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
