<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomTypeController extends Controller
{
    public function index()
    {
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

        return response()->json(['data' => $roomTypes]);
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
            'is_active' => true,
        ]);

        if (!empty($validated['amenityIds'])) {
            $rt->amenities()->sync($validated['amenityIds']);
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $rt->id], 'message' => 'Room type created successfully'], 201);
        }

        return redirect()->back()->with('success', 'Room type created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $rt = RoomType::findOrFail($id);
        $validated = $request->validate([
            'name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'basePrice' => ['nullable', 'numeric'],
            'maxGuests' => ['nullable', 'integer'],
            'bedType' => ['nullable', 'string'],
            'bedCount' => ['nullable', 'integer'],
            'amenityIds' => ['nullable', 'array'],
        ]);

        if (isset($validated['name'])) $rt->name = $validated['name'];
        if (isset($validated['description'])) $rt->description = $validated['description'];
        if (isset($validated['basePrice'])) $rt->base_price = $validated['basePrice'];
        if (isset($validated['maxGuests'])) $rt->max_guests = $validated['maxGuests'];
        if (isset($validated['bedType'])) $rt->bed_type = $validated['bedType'];
        if (isset($validated['bedCount'])) $rt->bed_count = $validated['bedCount'];
        $rt->save();

        if (isset($validated['amenityIds'])) {
            $rt->amenities()->sync($validated['amenityIds']);
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $rt->id], 'message' => 'Room type updated successfully']);
        }

        return redirect()->back()->with('success', 'Room type updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $rt = RoomType::findOrFail($id);
        $rt->delete();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Room type deleted successfully']);
        }

        return redirect()->back()->with('success', 'Room type deleted successfully.');
    }
}
