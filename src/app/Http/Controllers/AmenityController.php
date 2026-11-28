<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    public function index(Request $request)
    {
        $page = max((int) $request->input('page', 1), 1);
        $limit = max((int) $request->input('limit', 10), 1);

        $query = Amenity::query()->orderBy('name');

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('category', 'like', "%{$s}%");
            });
        }

        $total = $query->count();
        $totalPages = (int) ceil(max($total, 1) / $limit);
        $page = min($page, $totalPages);

        $amenities = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'description' => $a->description,
            'icon' => $a->icon,
            'category' => $a->category instanceof \BackedEnum ? $a->category->value : (string) $a->category,
            'isActive' => (bool) $a->is_active,
        ]);

        return response()->json([
            'data' => $amenities,
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
            'icon' => ['required', 'string'],
            'category' => ['required', 'string'],
        ]);

        $amenity = Amenity::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'],
            'category' => $validated['category'],
            'is_active' => true,
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $amenity->id], 'message' => 'Amenity created successfully.'], 200);
        }

        return redirect()->back()->with('success', 'Amenity created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $amenity = Amenity::findOrFail($id);
        $validated = $request->validate([
            'name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['name'])) $amenity->name = $validated['name'];
        if (array_key_exists('description', $validated)) $amenity->description = $validated['description'];
        if (isset($validated['icon'])) $amenity->icon = $validated['icon'];
        if (isset($validated['category'])) $amenity->category = $validated['category'];
        if (array_key_exists('isActive', $validated) && $validated['isActive'] !== null) {
            $amenity->is_active = (bool) $validated['isActive'];
        }
        $amenity->save();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $amenity->id], 'message' => 'Amenity updated successfully']);
        }

        return redirect()->back()->with('success', 'Amenity updated successfully.');
    }

    public function toggleStatus(Request $request, string $id)
    {
        $amenity = Amenity::findOrFail($id);
        $amenity->update(['is_active' => ! $amenity->is_active]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => [
                    'id' => $amenity->id,
                    'isActive' => (bool) $amenity->is_active,
                ],
                'message' => 'Amenity status updated successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Amenity status updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $amenity = Amenity::findOrFail($id);
        $amenity->delete();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Amenity deleted successfully']);
        }

        return redirect()->back()->with('success', 'Amenity deleted successfully.');
    }
}

