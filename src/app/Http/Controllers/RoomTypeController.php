<?php

namespace App\Http\Controllers;

use App\Enums\HousekeepingStatus;
use App\Enums\OccupancyStatus;
use App\Models\Image;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RoomTypeController extends Controller
{
    public function index(Request $request)
    {
        $page = max((int) $request->input('page', 1), 1);
        $limit = max((int) $request->input('limit', 10), 1);

        $query = RoomType::with(['amenities', 'images', 'rooms'])
            ->withCount('rooms')
            ->orderBy('name');

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('slug', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        $total = $query->count();
        $totalPages = (int) ceil(max($total, 1) / $limit);
        $page = min($page, $totalPages);

        $roomTypes = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(function ($rt) {
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
                'roomsCount' => $rt->rooms_count,
                'roomCount' => $rt->rooms_count,
                'primaryImage' => [
                    'url' => $primaryImg?->url ?? 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304',
                    'altText' => $primaryImg?->alt_text ?? $rt->name,
                ],
                'image' => [
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

        return response()->json([
            'data' => $roomTypes,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'basePrice' => ['required', 'numeric'],
            'maxGuests' => ['required', 'integer'],
            'bedType' => ['required', 'string'],
            'bedCount' => ['required', 'integer'],
            'sizeSqFt' => ['nullable', 'numeric'],
            'amenities' => ['nullable', 'array'],
            'amenityIds' => ['nullable', 'array'],
        ]);

        $slug = Str::slug($validated['name']) . '-' . strtolower(Str::random(4));

        $rt = RoomType::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['basePrice'],
            'max_guests' => $validated['maxGuests'],
            'adults' => $validated['maxGuests'],
            'children' => 0,
            'bed_type' => $validated['bedType'],
            'bed_count' => $validated['bedCount'],
            'size_sq_ft' => $validated['sizeSqFt'] ?? 400,
            'is_active' => true,
        ]);

        $amenityList = $validated['amenities'] ?? $validated['amenityIds'] ?? [];
        if (!empty($amenityList)) {
            $rt->amenities()->sync($amenityList);
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $rt->id], 'message' => 'Room type created successfully.'], 200);
        }

        return redirect()->back()->with('success', 'Room type created successfully.');
    }

    public function show(string $id)
    {
        $rt = RoomType::with(['amenities', 'images', 'rooms'])
            ->withCount('rooms')
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

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

        return response()->json([
            'data' => [
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
                'roomCount' => $rt->rooms_count,
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
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $rt = RoomType::with('images')->findOrFail($id);
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sizeSqFt' => ['nullable', 'numeric', 'min:1'],
            'maxGuests' => ['nullable', 'integer', 'min:1'],
            'adults' => ['nullable', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'basePrice' => ['nullable', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', 'max:3'],
            'bedType' => ['nullable', 'string'],
            'bedCount' => ['nullable', 'integer', 'min:1'],
            'smokingAllowed' => ['nullable'],
            'petsAllowed' => ['nullable'],
            'isActive' => ['nullable'],
            'amenities' => ['nullable', 'array'],
            'amenityIds' => ['nullable', 'array'],
            'retainedImageIds' => ['nullable', 'array'],
            'primaryImage' => ['nullable', 'string'],
        ]);

        $uploadedFiles = [];
        foreach ($request->file('images', []) as $item) {
            $file = is_array($item) ? ($item['image'] ?? null) : $item;
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $file;
            }
        }

        $storedPaths = [];
        $removedImages = collect();

        try {
            DB::transaction(function () use ($rt, $validated, $request, $uploadedFiles, &$storedPaths, &$removedImages): void {
                if (isset($validated['name'])) {
                    $rt->name = trim($validated['name']);
                    $rt->slug = Str::slug($rt->name) . '-' . strtolower(Str::random(4));
                }
                if (array_key_exists('description', $validated)) {
                    $rt->description = $validated['description'];
                }
                if (array_key_exists('sizeSqFt', $validated)) {
                    $rt->size_sq_ft = $validated['sizeSqFt'];
                }
                if (isset($validated['maxGuests'])) {
                    $rt->max_guests = (int) $validated['maxGuests'];
                }
                if (isset($validated['adults'])) {
                    $rt->adults = (int) $validated['adults'];
                }
                if (isset($validated['children'])) {
                    $rt->children = (int) $validated['children'];
                }
                if (isset($validated['basePrice'])) {
                    $rt->base_price = (int) $validated['basePrice'];
                }
                if (isset($validated['currency'])) {
                    $rt->currency = strtoupper($validated['currency']);
                }
                if (isset($validated['bedType'])) {
                    $rt->bed_type = $validated['bedType'];
                }
                if (isset($validated['bedCount'])) {
                    $rt->bed_count = (int) $validated['bedCount'];
                }
                if ($request->has('smokingAllowed')) {
                    $rt->smoking_allowed = $request->boolean('smokingAllowed');
                }
                if ($request->has('petsAllowed')) {
                    $rt->pets_allowed = $request->boolean('petsAllowed');
                }
                if ($request->has('isActive')) {
                    $rt->is_active = $request->boolean('isActive');
                }
                $rt->save();

                $amenityList = $validated['amenities'] ?? $validated['amenityIds'] ?? null;
                if ($amenityList !== null) {
                    $rt->amenities()->sync($amenityList);
                }

                if ($request->has('retainedImageIds') || count($uploadedFiles) > 0) {
                    $retainedIds = collect($validated['retainedImageIds'] ?? [])->map(fn($val) => (string) $val);
                    $retainedImages = $rt->images->filter(fn($img) => $retainedIds->contains((string) $img->id))->values();
                    $removedImages = $rt->images->filter(fn($img) => ! $retainedIds->contains((string) $img->id))->values();

                    [$primaryType, $primaryValue] = array_pad(explode(':', (string) ($validated['primaryImage'] ?? ''), 2), 2, '');

                    if ($removedImages->isNotEmpty()) {
                        Image::where('room_type_id', $rt->id)
                            ->whereIn('id', $removedImages->pluck('id'))
                            ->delete();
                    }

                    Image::where('room_type_id', $rt->id)->update(['is_primary' => false]);

                    foreach ($retainedImages as $index => $image) {
                        $image->update([
                            'is_primary' => $primaryType === 'existing' && (string) $image->id === $primaryValue,
                            'sort_order' => $index,
                        ]);
                    }

                    /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
                    $publicDisk = Storage::disk('public');
                    foreach ($uploadedFiles as $index => $file) {
                        $path = $file->storePublicly('room-types', 'public');
                        $storedPaths[] = $path;
                        Image::create([
                            'room_type_id' => $rt->id,
                            'url' => $publicDisk->url($path),
                            'alt_text' => $rt->name,
                            'is_primary' => $primaryType === 'new' && (int) $primaryValue === $index,
                            'sort_order' => $retainedImages->count() + $index,
                        ]);
                    }

                    if (! Image::where('room_type_id', $rt->id)->where('is_primary', true)->exists()) {
                        Image::where('room_type_id', $rt->id)->orderBy('sort_order')->limit(1)->update(['is_primary' => true]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        foreach ($removedImages as $oldImage) {
            $path = parse_url($oldImage->url, PHP_URL_PATH);
            if (is_string($path) && str_starts_with($path, '/storage/')) {
                Storage::disk('public')->delete(substr($path, strlen('/storage/')));
            }
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $rt->id], 'message' => 'Room type updated successfully']);
        }

        return redirect()->back()->with('success', 'Room type updated successfully.');
    }

    public function toggleStatus(Request $request, string $id)
    {
        $rt = RoomType::findOrFail($id);
        $rt->update(['is_active' => ! $rt->is_active]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => [
                    'id' => $rt->id,
                    'isActive' => (bool) $rt->is_active,
                ],
                'message' => 'Room type status updated successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Room type status updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $rt = RoomType::with('images')->findOrFail($id);

        if ($rt->rooms()->exists()) {
            $message = 'Cannot delete room type with existing rooms. Reassign or delete those rooms first.';
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->back()->withErrors(['roomType' => $message]);
        }

        $images = $rt->images;

        DB::transaction(function () use ($rt): void {
            $rt->amenities()->detach();
            $rt->images()->delete();
            $rt->delete();
        });

        foreach ($images as $image) {
            $path = parse_url($image->url, PHP_URL_PATH);
            if (is_string($path) && str_starts_with($path, '/storage/')) {
                Storage::disk('public')->delete(substr($path, strlen('/storage/')));
            }
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Room type deleted successfully']);
        }

        return redirect()->back()->with('success', 'Room type deleted successfully.');
    }
}

