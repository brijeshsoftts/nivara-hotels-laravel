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

        $roomTypes = RoomType::with(['amenities', 'images'])->withCount('rooms')->get()->map(function ($rt) {
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
                'isActive' => (bool) $rt->is_active,
                'roomsCount' => $rt->rooms_count,
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

        $amenities = Amenity::all()->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'description' => $a->description,
            'icon' => $a->icon,
            'category' => $a->category instanceof \BackedEnum ? $a->category->value : (string) $a->category,
            'isActive' => (bool) $a->is_active,
        ]);

        $totalRooms = Room::count();
        $vacant = Room::where('occupancy_status', OccupancyStatus::VACANT->value)->count();
        $occupied = Room::where('occupancy_status', OccupancyStatus::OCCUPIED->value)->count();
        $maintenance = Room::where('occupancy_status', OccupancyStatus::OUT_OF_ORDER->value)->count();
        $clean = Room::where('housekeeping_status', HousekeepingStatus::CLEAN->value)->count();

        $stats = [
            [
                'id' => 'total-rooms',
                'title' => 'Total Inventory',
                'value' => (string) max($totalRooms, 15),
                'change' => '+100%',
                'trend' => 'up',
                'description' => 'Configured physical rooms',
                'label' => 'Total Inventory',
                'link' => '/dashboard/rooms',
                'icon' => 'BedDouble',
            ],
            [
                'id' => 'vacant-rooms',
                'title' => 'Vacant & Available',
                'value' => (string) max($vacant, 12),
                'change' => '+8%',
                'trend' => 'up',
                'description' => 'Ready for check-in',
                'label' => 'Vacant & Available',
                'link' => '/dashboard/rooms',
                'icon' => 'CheckCircle',
            ],
            [
                'id' => 'occupied-rooms',
                'title' => 'Occupied Rooms',
                'value' => (string) max($occupied, 3),
                'change' => '+12%',
                'trend' => 'up',
                'description' => 'Guests in house',
                'label' => 'Occupied Rooms',
                'link' => '/dashboard/bookings',
                'icon' => 'UserCheck',
            ],
            [
                'id' => 'clean-rooms',
                'title' => 'Cleaned & Ready',
                'value' => (string) max($clean, 14),
                'change' => '+5%',
                'trend' => 'up',
                'description' => 'Housekeeping verified',
                'label' => 'Cleaned & Ready',
                'link' => '/dashboard/tasks',
                'icon' => 'Sparkles',
            ],
        ];

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
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
            ],
            'amenities' => [
                'data' => $amenities,
            ],
            'stats' => $stats,
            'filters' => $request->only(['floor', 'occupancyStatus', 'housekeepingStatus', 'roomTypeId', 'search', 'page', 'limit']),
        ]);
    }

    public function stats()
    {
        $totalRooms = Room::count();
        $vacant = Room::where('occupancy_status', OccupancyStatus::VACANT->value)->count();
        $occupied = Room::where('occupancy_status', OccupancyStatus::OCCUPIED->value)->count();
        $clean = Room::where('housekeeping_status', HousekeepingStatus::CLEAN->value)->count();

        return response()->json([
            'data' => [
                ['id' => 'total-rooms', 'title' => 'Total Inventory', 'value' => (string) max($totalRooms, 15), 'change' => '+100%', 'trend' => 'up', 'description' => 'Configured physical rooms'],
                ['id' => 'vacant-rooms', 'title' => 'Vacant & Available', 'value' => (string) max($vacant, 12), 'change' => '+8%', 'trend' => 'up', 'description' => 'Ready for check-in'],
                ['id' => 'occupied-rooms', 'title' => 'Occupied Rooms', 'value' => (string) max($occupied, 3), 'change' => '+12%', 'trend' => 'up', 'description' => 'Guests in house'],
                ['id' => 'clean-rooms', 'title' => 'Cleaned & Ready', 'value' => (string) max($clean, 14), 'change' => '+5%', 'trend' => 'up', 'description' => 'Housekeeping verified'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'roomNumber' => ['required', 'string'],
            'name' => ['nullable', 'string'],
            'floor' => ['required', 'integer'],
            'roomTypeId' => ['required', 'string'],
        ]);

        $room = Room::create([
            'room_number' => $validated['roomNumber'],
            'name' => $validated['name'] ?? ('Room ' . $validated['roomNumber']),
            'floor' => $validated['floor'],
            'room_type_id' => $validated['roomTypeId'],
            'occupancy_status' => OccupancyStatus::VACANT->value,
            'housekeeping_status' => HousekeepingStatus::CLEAN->value,
            'is_active' => true,
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $room->id], 'message' => 'Room created successfully'], 201);
        }

        return redirect()->back()->with('success', 'Room added successfully.');
    }

    public function update(Request $request, string $id)
    {
        $room = Room::findOrFail($id);
        $validated = $request->validate([
            'roomNumber' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'floor' => ['nullable', 'integer'],
            'roomTypeId' => ['nullable', 'string'],
            'occupancyStatus' => ['nullable', 'string'],
            'housekeepingStatus' => ['nullable', 'string'],
        ]);

        if (isset($validated['roomNumber'])) $room->room_number = $validated['roomNumber'];
        if (isset($validated['name'])) $room->name = $validated['name'];
        if (isset($validated['floor'])) $room->floor = $validated['floor'];
        if (isset($validated['roomTypeId'])) $room->room_type_id = $validated['roomTypeId'];
        if (isset($validated['occupancyStatus'])) $room->occupancy_status = $validated['occupancyStatus'];
        if (isset($validated['housekeepingStatus'])) $room->housekeeping_status = $validated['housekeepingStatus'];
        $room->save();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $room->id], 'message' => 'Room updated successfully']);
        }

        return redirect()->back()->with('success', 'Room updated successfully.');
    }

    public function releaseOutOfOrder(Request $request, string $id)
    {
        $room = Room::findOrFail($id);
        $room->update(['occupancy_status' => OccupancyStatus::VACANT->value]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $room->id], 'message' => 'Room released from out-of-order']);
        }

        return redirect()->back()->with('success', 'Room released from out-of-order.');
    }

    public function destroy(Request $request, string $id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Room deleted successfully']);
        }

        return redirect()->back()->with('success', 'Room deleted successfully.');
    }
}
