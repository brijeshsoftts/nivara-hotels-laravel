<?php

namespace App\Models;

use App\Enums\BedType;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    protected $table = 'room_types';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'bed_type' => BedType::class,
            'smoking_allowed' => 'boolean',
            'pets_allowed' => 'boolean',
            'is_active' => 'boolean',
            'base_price' => 'integer',
            'size_sq_ft' => 'integer',
            'max_guests' => 'integer',
            'adults' => 'integer',
            'children' => 'integer',
            'bed_count' => 'integer',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'room_type_id');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_room_type', 'room_type_id', 'amenity_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class, 'room_type_id')->orderBy('sort_order');
    }
}
