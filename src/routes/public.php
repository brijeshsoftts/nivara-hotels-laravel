<?php

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/about', function () {
    return Inertia::render('public/about');
})->name('about');

Route::get('/rooms', function (Request $request) {
    if ($request->wantsJson() && !$request->header('X-Inertia')) {
        $query = Room::with('roomType');

        if ($request->filled('floor')) {
            $query->where('floor', (int) $request->input('floor'));
        }
        if ($request->filled('occupancyStatus')) {
            $query->where('occupancy_status', $request->input('occupancyStatus'));
        }
        if ($request->filled('housekeepingStatus')) {
            $query->where('housekeeping_status', $request->input('housekeepingStatus'));
        }
        if ($request->filled('roomTypeId')) {
            $query->where('room_type_id', $request->input('roomTypeId'));
        }

        $rooms = $query->get()->map(fn($r) => [
            'id' => $r->id,
            'roomNumber' => $r->room_number,
            'name' => $r->name,
            'floor' => (string) $r->floor,
            'roomTypeId' => $r->room_type_id,
            'roomType' => [
                'id' => $r->room_type_id,
                'name' => $r->roomType?->name ?? 'Room Type',
            ],
            'occupancyStatus' => $r->occupancy_status instanceof \BackedEnum ? $r->occupancy_status->value : (string) $r->occupancy_status,
            'housekeepingStatus' => $r->housekeeping_status instanceof \BackedEnum ? $r->housekeeping_status->value : (string) $r->housekeeping_status,
            'isActive' => (bool) $r->is_active,
        ]);

        return apiList($rooms);
    }

    return Inertia::render('public/rooms');
})->name('rooms.index');

Route::get('/rooms/{slug}', function (Request $request, string $slug) {
    if ($request->wantsJson() && !$request->header('X-Inertia')) {
        // Physical room lookup (for roomsService.findOne)
        $room = Room::with('roomType.images')->find($slug);
        if ($room) {
            $rt = $room->roomType;
            $primaryImg = $rt?->images->firstWhere('is_primary', true) ?? $rt?->images->first();
            return apiItem([
                'id' => $room->id,
                'name' => $room->name,
                'roomNumber' => $room->room_number,
                'roomTypeId' => $room->room_type_id,
                'floor' => $room->floor,
                'description' => $room->description,
                'occupancyStatus' => $room->occupancy_status instanceof \BackedEnum ? $room->occupancy_status->value : (string) $room->occupancy_status,
                'housekeepingStatus' => $room->housekeeping_status instanceof \BackedEnum ? $room->housekeeping_status->value : (string) $room->housekeeping_status,
                'isActive' => (bool) $room->is_active,
                'createdAt' => $room->created_at?->toIso8601String(),
                'updatedAt' => $room->updated_at?->toIso8601String(),
                'roomType' => [
                    'id' => $rt?->id,
                    'name' => $rt?->name,
                    'slug' => $rt?->slug,
                    'basePrice' => $rt?->base_price,
                    'currency' => $rt?->currency ?? 'INR',
                    'isActive' => (bool) $rt?->is_active,
                    'image' => $primaryImg ? [
                        'id' => $primaryImg->id,
                        'url' => $primaryImg->url,
                        'altText' => $primaryImg->alt_text,
                    ] : null,
                ],
            ]);
        }

        // Room type lookup by slug or id
        $rt = RoomType::where('slug', $slug)->orWhere('id', $slug)->with(['amenities', 'images', 'rooms'])->first();
        if ($rt) {
            $primaryImg = $rt->images->firstWhere('is_primary', true) ?? $rt->images->first();
            return apiItem([
                'id' => $rt->id,
                'name' => $rt->name,
                'slug' => $rt->slug,
                'description' => $rt->description,
                'sizeSqFt' => $rt->size_sq_ft,
                'maxGuests' => $rt->max_guests,
                'adults' => $rt->adults,
                'children' => $rt->children,
                'basePrice' => $rt->base_price,
                'currency' => $rt->currency ?? 'INR',
                'bedType' => $rt->bed_type instanceof \BackedEnum ? $rt->bed_type->value : (string) $rt->bed_type,
                'bedCount' => $rt->bed_count,
                'images' => $rt->images->map(fn($img) => ['id' => $img->id, 'url' => $img->url, 'altText' => $img->alt_text, 'isPrimary' => $img->is_primary]),
                'amenities' => $rt->amenities->map(fn($a) => ['id' => $a->id, 'name' => $a->name, 'icon' => $a->icon]),
            ]);
        }
    }

    return Inertia::render('public/room-details', ['slug' => $slug]);
})->name('rooms.show');

Route::get('/rooms/{slug}/book', function (string $slug) {
    return Inertia::render('public/checkout', ['slug' => $slug]);
})->name('rooms.book');

Route::get('/checkout', function () {
    return Inertia::render('public/checkout');
})->name('checkout');

Route::get('/contact', function () {
    return Inertia::render('public/contact');
})->name('contact');
