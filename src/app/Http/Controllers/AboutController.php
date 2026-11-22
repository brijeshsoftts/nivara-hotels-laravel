<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function index(Request $request): Response
    {
        $amenities = Amenity::where('is_active', true)->get()->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'description' => $a->description,
            'icon' => $a->icon,
            'category' => $a->category instanceof \BackedEnum ? $a->category->value : (string) $a->category,
        ]);

        return Inertia::render('public/about', [
            'amenities' => [
                'data' => $amenities,
            ],
        ]);
    }
}
