<?php

namespace App\Http\Controllers;

use App\Enums\OccupancyStatus;
use App\Models\Amenity;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function index(Request $request): Response
    {
        $featuredRooms = RoomType::where('is_active', true)
            ->with(['amenities', 'images', 'rooms'])
            ->take(6)
            ->get()
            ->map(function ($rt) {
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
            'description' => $a->description,
            'icon' => $a->icon,
            'category' => $a->category instanceof \BackedEnum ? $a->category->value : (string) $a->category,
        ]);

        return Inertia::render('welcome', [
            'featuredRooms' => [
                'data' => $featuredRooms,
                'meta' => [
                    'total' => $featuredRooms->count(),
                    'page' => 1,
                    'limit' => 6,
                    'totalPages' => 1,
                ],
            ],
            'amenities' => [
                'data' => $amenities,
            ],
        ]);
    }
}
