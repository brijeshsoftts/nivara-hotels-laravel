<?php

namespace App\Http\Controllers;

use App\Enums\OccupancyStatus;
use App\Models\Amenity;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicRoomController extends Controller
{
    public function index(Request $request)
    {
        $query = RoomType::query()->where('is_active', true)->with(['amenities', 'images', 'rooms']);

        if ($request->filled('guests')) {
            $query->where('max_guests', '>=', (int) $request->input('guests'));
        }
        if ($request->filled('bedType')) {
            $query->where('bed_type', $request->input('bedType'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where('name', 'like', "%{$s}%");
        }

        $allRoomTypes = $query->get()->map(function ($rt) {
            $primaryImg = $rt->images->firstWhere('is_primary', true) ?? $rt->images->first();
            return [
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
                'availableRooms' => $rt->rooms->where('is_active', true)->where('occupancy_status', OccupancyStatus::VACANT)->count(),
                'image' => [
                    'url' => $primaryImg?->url ?? 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304',
                    'altText' => $primaryImg?->alt_text ?? $rt->name,
                ],
                'amenities' => $rt->amenities->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'icon' => $a->icon,
                ]),
            ];
        });

        $amenities = Amenity::where('is_active', true)->get()->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'icon' => $a->icon,
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => $allRoomTypes,
                'meta' => [
                    'total' => $allRoomTypes->count(),
                    'page' => 1,
                    'limit' => 50,
                    'totalPages' => 1,
                ],
            ]);
        }

        return Inertia::render('public/rooms', [
            'rooms' => [
                'data' => $allRoomTypes,
                'meta' => [
                    'total' => $allRoomTypes->count(),
                    'page' => 1,
                    'limit' => 50,
                    'totalPages' => 1,
                ],
            ],
            'amenities' => [
                'data' => $amenities,
            ],
            'filters' => $request->only(['guests', 'bedType', 'search']),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            $physicalRoom = Room::where('id', $slug)->orWhere('room_number', $slug)->with('roomType')->first();
            if ($physicalRoom) {
                return response()->json([
                    'data' => [
                        'id' => $physicalRoom->id,
                        'roomNumber' => $physicalRoom->room_number,
                        'name' => $physicalRoom->name,
                        'floor' => (string) $physicalRoom->floor,
                        'roomTypeId' => $physicalRoom->room_type_id,
                        'roomType' => [
                            'id' => $physicalRoom->room_type_id,
                            'name' => $physicalRoom->roomType?->name ?? 'Room Type',
                        ],
                        'occupancyStatus' => $physicalRoom->occupancy_status instanceof \BackedEnum ? $physicalRoom->occupancy_status->value : (string) $physicalRoom->occupancy_status,
                        'housekeepingStatus' => $physicalRoom->housekeeping_status instanceof \BackedEnum ? $physicalRoom->housekeeping_status->value : (string) $physicalRoom->housekeeping_status,
                        'isActive' => (bool) $physicalRoom->is_active,
                    ]
                ]);
            }
        }

        $roomType = RoomType::where('slug', $slug)
            ->orWhere('id', $slug)
            ->with(['amenities', 'images', 'rooms'])
            ->first();

        if (!$roomType) {
            $physicalRoom = Room::where('id', $slug)->orWhere('room_number', $slug)->with(['roomType.amenities', 'roomType.images'])->first();
            if ($physicalRoom) {
                $roomType = $physicalRoom->roomType;
            }
        }

        if (!$roomType) {
            abort(404);
        }

        $primaryImg = $roomType->images->firstWhere('is_primary', true) ?? $roomType->images->first();

        $formatted = [
            'id' => $roomType->id,
            'name' => $roomType->name,
            'slug' => $roomType->slug,
            'description' => $roomType->description,
            'sizeSqFt' => $roomType->size_sq_ft,
            'maxGuests' => $roomType->max_guests,
            'adults' => $roomType->adults,
            'children' => $roomType->children,
            'basePrice' => $roomType->base_price,
            'currency' => $roomType->currency ?? 'INR',
            'bedType' => $roomType->bed_type instanceof \BackedEnum ? $roomType->bed_type->value : (string) $roomType->bed_type,
            'bedCount' => $roomType->bed_count,
            'availableRooms' => $roomType->rooms->where('is_active', true)->where('occupancy_status', OccupancyStatus::VACANT)->count(),
            'image' => [
                'url' => $primaryImg?->url ?? 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304',
                'altText' => $primaryImg?->alt_text ?? $roomType->name,
            ],
            'images' => $roomType->images->map(fn($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'altText' => $img->alt_text ?? $roomType->name,
                'isPrimary' => (bool) $img->is_primary,
            ]),
            'amenities' => $roomType->amenities->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'icon' => $a->icon,
            ]),
        ];

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => $formatted]);
        }

        return Inertia::render('public/room-details', [
            'slug' => $roomType->slug ?? $slug,
            'room' => [
                'data' => $formatted,
            ],
        ]);
    }
}
