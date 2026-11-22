<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    public function index()
    {
        $amenities = Amenity::all()->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'description' => $a->description,
            'icon' => $a->icon,
            'category' => $a->category instanceof \BackedEnum ? $a->category->value : (string) $a->category,
            'isActive' => (bool) $a->is_active,
        ]);

        return response()->json(['data' => $amenities]);
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
            return response()->json(['data' => ['id' => $amenity->id], 'message' => 'Amenity created successfully'], 201);
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
        ]);

        if (isset($validated['name'])) $amenity->name = $validated['name'];
        if (isset($validated['description'])) $amenity->description = $validated['description'];
        if (isset($validated['icon'])) $amenity->icon = $validated['icon'];
        if (isset($validated['category'])) $amenity->category = $validated['category'];
        $amenity->save();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $amenity->id], 'message' => 'Amenity updated successfully']);
        }

        return redirect()->back()->with('success', 'Amenity updated successfully.');
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
