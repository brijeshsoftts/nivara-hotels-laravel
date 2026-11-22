<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingTask extends Model
{
    protected $table = 'housekeeping_tasks';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'task_type' => TaskType::class,
            'status' => TaskStatus::class,
            'scheduled_date' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedToStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }
}
