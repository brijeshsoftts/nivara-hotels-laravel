<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'payment_method' => PaymentMethod::class,
            'check_in_date' => 'datetime',
            'check_out_date' => 'datetime',
            'booked_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'checkout_expires_at' => 'datetime',
            'total_guests' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bookingRooms(): HasMany
    {
        return $this->hasMany(BookingRoom::class, 'booking_id');
    }

    public function bookingGuests(): HasMany
    {
        return $this->hasMany(BookingGuest::class, 'booking_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'booking_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'booking_id');
    }
}
