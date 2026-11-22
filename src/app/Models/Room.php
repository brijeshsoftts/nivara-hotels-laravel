<?php

namespace App\Models;

use App\Enums\HousekeepingStatus;
use App\Enums\OccupancyStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $table = 'rooms';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occupancy_status' => OccupancyStatus::class,
            'housekeeping_status' => HousekeepingStatus::class,
            'is_active' => 'boolean',
            'floor' => 'integer',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function bookingRooms(): HasMany
    {
        return $this->hasMany(BookingRoom::class, 'room_id');
    }

    public function housekeepingTasks(): HasMany
    {
        return $this->hasMany(HousekeepingTask::class, 'room_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'room_id');
    }

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(HousekeeperRoomAssignment::class, 'room_id');
    }
}
