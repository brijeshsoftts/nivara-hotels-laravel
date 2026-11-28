<?php

namespace App\Http\Controllers;

use App\Enums\HousekeepingStatus;
use App\Enums\OccupancyStatus;
use App\Models\Amenity;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $query = Room::with('roomType')->orderBy('room_number');

        if ($request->filled('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }
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
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('room_number', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"));
        }

        $total = $query->count();
        $rooms = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($r) => [
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

        $roomTypesQuery = RoomType::with(['amenities', 'images', 'rooms'])->withCount('rooms')->orderBy('name');
        $roomTypesTotal = $roomTypesQuery->count();
        $roomTypesLimit = 10;
        $roomTypes = $roomTypesQuery->take($roomTypesLimit)->get()->map(function ($rt) {
            $primaryImg = $rt->images->firstWhere('is_primary', true) ?? $rt->images->first();
            $roomsCollection = $rt->rooms;
            $activeRooms = $roomsCollection->where('is_active', true);
            $availableCount = $activeRooms->filter(
                fn($room) => ($room->occupancy_status instanceof \BackedEnum ? $room->occupancy_status->value : (string) $room->occupancy_status) === OccupancyStatus::VACANT->value
                    && ($room->housekeeping_status instanceof \BackedEnum ? $room->housekeeping_status->value : (string) $room->housekeeping_status) === HousekeepingStatus::CLEAN->value
            )->count();
            $occupiedCount = $activeRooms->filter(
                fn($room) => ($room->occupancy_status instanceof \BackedEnum ? $room->occupancy_status->value : (string) $room->occupancy_status) === OccupancyStatus::OCCUPIED->value
            )->count();
            $cleanCount = $activeRooms->filter(
                fn($room) => ($room->housekeeping_status instanceof \BackedEnum ? $room->housekeeping_status->value : (string) $room->housekeeping_status) === HousekeepingStatus::CLEAN->value
            )->count();
            $dirtyCount = $activeRooms->filter(
                fn($room) => ($room->housekeeping_status instanceof \BackedEnum ? $room->housekeeping_status->value : (string) $room->housekeeping_status) === HousekeepingStatus::DIRTY->value
            )->count();
            $cleaningCount = $activeRooms->filter(
                fn($room) => ($room->housekeeping_status instanceof \BackedEnum ? $room->housekeeping_status->value : (string) $room->housekeeping_status) === HousekeepingStatus::CLEANING->value
            )->count();

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
                'smokingAllowed' => (bool) $rt->smoking_allowed,
                'petsAllowed' => (bool) $rt->pets_allowed,
                'isActive' => (bool) $rt->is_active,
                'numberOfRooms' => $rt->rooms_count,
                'roomCount' => $rt->rooms_count,
                'primaryImage' => [
                    'url' => $primaryImg?->url ?? 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304',
                    'altText' => $primaryImg?->alt_text ?? $rt->name,
                ],
                'images' => $rt->images->map(fn($img, $idx) => [
                    'id' => $img->id,
                    'url' => $img->url,
                    'altText' => $img->alt_text ?? $rt->name,
                    'isPrimary' => (bool) $img->is_primary,
                    'sortOrder' => (int) ($img->sort_order ?? $idx),
                ])->values(),
                'amenities' => $rt->amenities->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'icon' => $a->icon,
                ])->values(),
                'roomStatusSummary' => [
                    'total' => $roomsCollection->count(),
                    'active' => $activeRooms->count(),
                    'occupancy' => [
                        'available' => $availableCount,
                        'occupied' => $occupiedCount,
                        'other' => max($activeRooms->count() - $availableCount - $occupiedCount, 0),
                    ],
                    'housekeeping' => [
                        'clean' => $cleanCount,
                        'dirty' => $dirtyCount,
                        'inspected' => 0,
                        'inProgress' => $cleaningCount,
                        'other' => max($activeRooms->count() - $cleanCount - $dirtyCount - $cleaningCount, 0),
                    ],
                ],
                'createdAt' => $rt->created_at?->toIso8601String(),
                'updatedAt' => $rt->updated_at?->toIso8601String(),
            ];
        });

        $amenitiesQuery = Amenity::query()->orderBy('name');
        $amenitiesTotal = $amenitiesQuery->count();
        $amenitiesLimit = 10;
        $amenities = $amenitiesQuery->take($amenitiesLimit)->get()->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'description' => $a->description,
            'icon' => $a->icon,
            'category' => $a->category instanceof \BackedEnum ? $a->category->value : (string) $a->category,
            'isActive' => (bool) $a->is_active,
        ]);

        $stats = $this->getRoomStats();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'data' => $rooms,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ]);
        }

        return Inertia::render('protect/rooms/index', [
            'rooms' => [
                'data' => $rooms,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ],
            'roomTypes' => [
                'data' => $roomTypes,
                'meta' => [
                    'total' => $roomTypesTotal,
                    'page' => 1,
                    'limit' => $roomTypesLimit,
                    'totalPages' => (int) ceil(max($roomTypesTotal, 1) / $roomTypesLimit),
                ],
            ],
            'amenities' => [
                'data' => $amenities,
                'meta' => [
                    'total' => $amenitiesTotal,
                    'page' => 1,
                    'limit' => $amenitiesLimit,
                    'totalPages' => (int) ceil(max($amenitiesTotal, 1) / $amenitiesLimit),
                ],
            ],
            'stats' => $stats,
            'filters' => $request->only(['isActive', 'floor', 'occupancyStatus', 'housekeepingStatus', 'roomTypeId', 'search', 'page', 'limit']),
        ]);
    }

    public function stats()
    {
        return response()->json([
            'data' => $this->getRoomStats(),
        ]);
    }

    private function getRoomStats(): array
    {
        $totalRooms = Room::where('is_active', true)->count();
        $availableRooms = Room::where('is_active', true)
            ->where('occupancy_status', OccupancyStatus::VACANT->value)
            ->where('housekeeping_status', HousekeepingStatus::CLEAN->value)
            ->count();
        $occupiedRooms = Room::where('is_active', true)
            ->where('occupancy_status', OccupancyStatus::OCCUPIED->value)
            ->count();
        $outOfOrderRooms = Room::where('is_active', true)
            ->where('occupancy_status', OccupancyStatus::OUT_OF_ORDER->value)
            ->count();

        return [
            [
                'id' => 'total-rooms',
                'title' => 'Active rooms',
                'label' => 'Active rooms',
                'value' => (string) $totalRooms,
                'description' => 'Rooms currently in service',
                'link' => '/dashboard/rooms?isActive=true',
                'icon' => 'BedDouble',
                'tone' => 'sky',
            ],
            [
                'id' => 'available-rooms',
                'title' => 'Available rooms',
                'label' => 'Available rooms',
                'value' => (string) $availableRooms,
                'description' => 'Vacant, clean rooms ready to book',
                'link' => '/dashboard/rooms?isActive=true&occupancyStatus=VACANT&housekeepingStatus=CLEAN',
                'icon' => 'DoorOpen',
                'tone' => 'emerald',
            ],
            [
                'id' => 'occupied-rooms',
                'title' => 'Occupied rooms',
                'label' => 'Occupied rooms',
                'value' => (string) $occupiedRooms,
                'description' => 'Rooms with guests in house',
                'link' => '/dashboard/rooms?isActive=true&occupancyStatus=OCCUPIED',
                'icon' => 'Users',
                'tone' => 'gold',
            ],
            [
                'id' => 'out-of-order-rooms',
                'title' => 'Out of order',
                'label' => 'Out of order',
                'value' => (string) $outOfOrderRooms,
                'description' => 'Rooms unavailable for booking',
                'link' => '/dashboard/rooms?isActive=true&occupancyStatus=OUT_OF_ORDER',
                'icon' => 'Wrench',
                'tone' => 'rose',
            ],
        ];
    }

    public function store(Request $request)
    {
        if ($request->has('roomNumber') && ! is_array($request->input('roomNumber'))) {
            $request->merge(['roomNumber' => (string) $request->input('roomNumber')]);
        }

        $validated = $request->validate([
            'roomNumber' => ['required', 'string', 'max:50', 'unique:rooms,room_number'],
            'name' => ['nullable', 'string', 'max:100'],
            'floor' => ['required', 'integer', 'min:0', 'max:50'],
            'roomTypeId' => ['required', 'string', 'exists:room_types,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'occupancyStatus' => ['nullable', 'string'],
            'housekeepingStatus' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $room = Room::create([
            'room_number' => $validated['roomNumber'],
            'name' => $validated['name'] ?? ('Room ' . $validated['roomNumber']),
            'floor' => $validated['floor'],
            'room_type_id' => $validated['roomTypeId'],
            'description' => $validated['description'] ?? null,
            'occupancy_status' => $validated['occupancyStatus'] ?? OccupancyStatus::VACANT->value,
            'housekeeping_status' => $validated['housekeepingStatus'] ?? HousekeepingStatus::CLEAN->value,
            'is_active' => $validated['isActive'] ?? true,
        ]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $room->id], 'message' => 'Room created successfully.'], 200);
        }

        return redirect()->back()->with('success', 'Room added successfully.');
    }

    public function show(string $id)
    {
        $r = Room::with('roomType.images')->findOrFail($id);
        $roomType = $r->roomType;
        $primaryImage = $roomType?->images->firstWhere('is_primary', true);

        return response()->json([
            'data' => [
                'id' => $r->id,
                'roomNumber' => $r->room_number,
                'name' => $r->name,
                'roomName' => $r->name,
                'floor' => $r->floor,
                'roomTypeId' => $r->room_type_id,
                'description' => $r->description,
                'occupancyStatus' => $r->occupancy_status instanceof \BackedEnum ? $r->occupancy_status->value : (string) $r->occupancy_status,
                'housekeepingStatus' => $r->housekeeping_status instanceof \BackedEnum ? $r->housekeeping_status->value : (string) $r->housekeeping_status,
                'isActive' => (bool) $r->is_active,
                'createdAt' => $r->created_at?->toISOString(),
                'updatedAt' => $r->updated_at?->toISOString(),
                'roomType' => $roomType ? [
                    'id' => $roomType->id,
                    'name' => $roomType->name,
                    'slug' => $roomType->slug,
                    'description' => $roomType->description,
                    'roomSize' => $roomType->size_sq_ft,
                    'maxGuests' => (int) $roomType->max_guests,
                    'basePrice' => $roomType->base_price,
                    'currency' => $roomType->currency ?? 'INR',
                    'bed' => [
                        'count' => (int) $roomType->bed_count,
                        'type' => $roomType->bed_type instanceof \BackedEnum ? $roomType->bed_type->value : (string) $roomType->bed_type,
                    ],
                    'isActive' => (bool) $roomType->is_active,
                    'image' => $primaryImage ? [
                        'id' => $primaryImage->id,
                        'url' => $primaryImage->url,
                        'altText' => $primaryImage->alt_text,
                    ] : null,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $room = Room::findOrFail($id);
        if ($request->has('roomNumber') && $request->input('roomNumber') !== null && ! is_array($request->input('roomNumber'))) {
            $request->merge(['roomNumber' => (string) $request->input('roomNumber')]);
        }

        $validated = $request->validate([
            'roomNumber' => ['nullable', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'integer'],
            'roomTypeId' => ['nullable', 'string'],
            'occupancyStatus' => ['nullable', 'string'],
            'housekeepingStatus' => ['nullable', 'string'],
        ]);

        if (isset($validated['roomNumber'])) {
            $room->room_number = $validated['roomNumber'];
        }
        if (isset($validated['name'])) {
            $room->name = $validated['name'];
        }
        if (isset($validated['floor'])) {
            $room->floor = $validated['floor'];
        }
        if (isset($validated['roomTypeId'])) {
            $room->room_type_id = $validated['roomTypeId'];
        }
        if (isset($validated['occupancyStatus'])) {
            $room->occupancy_status = $validated['occupancyStatus'];
        }
        if (isset($validated['housekeepingStatus'])) {
            $room->housekeeping_status = $validated['housekeepingStatus'];
        }
        $room->save();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $room->id], 'message' => 'Room updated successfully']);
        }

        return redirect()->back()->with('success', 'Room updated successfully.');
    }

    public function releaseOutOfOrder(Request $request, string $id)
    {
        $room = Room::findOrFail($id);
        $room->update(['occupancy_status' => OccupancyStatus::VACANT->value]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $room->id], 'message' => 'Room released from out-of-order']);
        }

        return redirect()->back()->with('success', 'Room released from out-of-order.');
    }

    public function destroy(Request $request, string $id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => 'Room deleted successfully']);
        }

        return redirect()->back()->with('success', 'Room deleted successfully.');
    }
}
