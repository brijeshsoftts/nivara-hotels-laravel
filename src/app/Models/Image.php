<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends UuidModel
{
    protected $table = 'images';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}
