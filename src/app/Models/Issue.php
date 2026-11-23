<?php

namespace App\Models;

use App\Enums\IssueCategory;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    protected $table = 'issues';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'category' => IssueCategory::class,
            'priority' => IssuePriority::class,
            'status' => IssueStatus::class,
            'reported_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    public function reportedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reported_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'image_id');
    }

    public function assignedToStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }
}
