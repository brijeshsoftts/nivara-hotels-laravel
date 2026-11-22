<?php

namespace App\Models;

use App\Enums\AmenityCategory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    protected $table = 'amenities';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'category' => AmenityCategory::class,
            'is_active' => 'boolean',
        ];
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class, 'amenity_room_type', 'amenity_id', 'room_type_id');
    }
}
