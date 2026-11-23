<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeeperRoomAssignment extends Model
{
    protected $table = 'housekeeper_room_assignments';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'shift_start' => 'datetime',
            'shift_end' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'housekeeper_id');
    }
}
