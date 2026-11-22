<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRoom extends Model
{
    public $timestamps = false;

    protected $table = 'booking_rooms';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price_per_night' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'assigned_room_id');
    }
}
