<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OccupancyStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    public function home(Request $request): Response
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

        return Inertia::render('public/Home', [
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

    public function about(Request $request): Response
    {
        $amenities = Amenity::where('is_active', true)->get()->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'description' => $a->description,
            'icon' => $a->icon,
            'category' => $a->category instanceof \BackedEnum ? $a->category->value : (string) $a->category,
        ]);

        return Inertia::render('public/About', [
            'amenities' => [
                'data' => $amenities,
            ],
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('public/Contact');
    }

    public function storeContact(Request $request)
    {
        $validated = $request->validate([
            'fullName' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $name = $validated['fullName'] ?? $validated['name'] ?? 'Website Visitor';

        Inquiry::create([
            'full_name' => $name,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'] ?? 'Website Inquiry',
            'message' => $validated['message'],
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Your message has been sent successfully.']);
        }

        return redirect()->back()->with('success', 'Your message has been sent successfully.');
    }

    public function rooms(Request $request)
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

        return Inertia::render('public/Rooms', [
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

    public function roomDetails(Request $request, string $slug)
    {
        $roomType = RoomType::where('slug', $slug)
            ->orWhere('id', $slug)
            ->with(['amenities', 'images', 'rooms'])
            ->first();

        if (!$roomType) {
            if ($request->wantsJson()) {
                $physicalRoom = Room::find($slug);
                if ($physicalRoom) {
                    return app(RoomController::class)->show($slug);
                }
            }
            abort(404);
        }

        $similarRooms = RoomType::where('is_active', true)
            ->where('id', '!=', $roomType->id)
            ->with(['amenities', 'images', 'rooms'])
            ->take(4)
            ->get()
            ->map(function ($rt) {
                $pImg = $rt->images->firstWhere('is_primary', true) ?? $rt->images->first();
                return [
                    'id' => $rt->id,
                    'name' => $rt->name,
                    'slug' => $rt->slug,
                    'basePrice' => $rt->base_price,
                    'image' => [
                        'url' => $pImg?->url ?? 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304',
                        'altText' => $pImg?->alt_text ?? $rt->name,
                    ],
                ];
            });

        $details = [
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

        return Inertia::render('public/room-details', [
            'room' => $details,
            'slug' => $slug,
            'similarRooms' => $similarRooms,
        ]);
    }

    public function checkout(Request $request)
    {
        $roomTypes = RoomType::where('is_active', true)->with(['amenities', 'images'])->get()->map(function ($rt) {
            $primaryImg = $rt->images->firstWhere('is_primary', true) ?? $rt->images->first();
            return [
                'id' => $rt->id,
                'name' => $rt->name,
                'slug' => $rt->slug,
                'basePrice' => $rt->base_price,
                'currency' => $rt->currency ?? 'INR',
                'maxGuests' => $rt->max_guests,
                'image' => [
                    'url' => $primaryImg?->url ?? 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304',
                    'altText' => $primaryImg?->alt_text ?? $rt->name,
                ],
            ];
        });

        $slug = $request->query('slug') ?? $request->route('slug');
        $selectedRoom = null;
        if ($slug) {
            $selectedRoom = $roomTypes->firstWhere('slug', $slug) ?? $roomTypes->firstWhere('id', $slug);
        }

        return Inertia::render('public/checkout', [
            'selectedRoom' => $selectedRoom,
            'roomTypes' => $roomTypes,
        ]);
    }

    public function storeCheckout(Request $request)
    {
        if ($request->filled('checkoutToken')) {
            return $this->storePublicPaymentBooking($request);
        }

        $validated = $request->validate([
            'roomTypeId' => ['nullable', 'string'],
            'roomId' => ['nullable', 'string'],
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'checkIn' => ['required', 'date'],
            'checkOut' => ['required', 'date', 'after:checkIn'],
            'adults' => ['nullable', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $user = User::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['fullName'],
                    'full_name' => $validated['fullName'],
                    'phone' => $validated['phone'],
                    'role' => UserRole::CUSTOMER->value,
                    'password' => Hash::make(Str::random(16)),
                    'is_active' => true,
                ]
            );

            Customer::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'id_proof_type' => 'PASSPORT',
                    'id_proof_number' => 'ONLINE_' . strtoupper(Str::random(8)),
                    'status' => 'ACTIVE',
                ]
            );

            $room = null;
            if (!empty($validated['roomId'])) {
                $room = Room::find($validated['roomId']);
            } elseif (!empty($validated['roomTypeId'])) {
                $room = Room::where('room_type_id', $validated['roomTypeId'])
                    ->where('occupancy_status', OccupancyStatus::VACANT)
                    ->where('is_active', true)
                    ->first();
            }

            if (!$room) {
                $room = Room::where('occupancy_status', OccupancyStatus::VACANT)->where('is_active', true)->first();
            }

            $roomType = $room ? $room->roomType : RoomType::first();
            $checkIn = Carbon::parse($validated['checkIn']);
            $checkOut = Carbon::parse($validated['checkOut']);
            $nights = max(1, $checkIn->diffInDays($checkOut));
            $pricePerNight = $roomType ? $roomType->base_price : 2500;
            $subtotal = $pricePerNight * $nights;
            $tax = round($subtotal * 0.12, 2);
            $totalAmount = $subtotal + $tax;

            $booking = Booking::create([
                'booking_reference' => 'BK-' . strtoupper(Str::random(8)),
                'customer_id' => $user->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'status' => BookingStatus::CONFIRMED->value,
                'adults' => (int) ($validated['adults'] ?? 1),
                'children' => (int) ($validated['children'] ?? 0),
                'special_requests' => $validated['notes'] ?? null,
                'source' => 'ONLINE',
                'total_amount' => $totalAmount,
            ]);

            if ($room) {
                BookingRoom::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'price_per_night' => $pricePerNight,
                ]);
                $room->update(['occupancy_status' => OccupancyStatus::OCCUPIED]);
            }

            BookingGuest::create([
                'booking_id' => $booking->id,
                'full_name' => $validated['fullName'],
                'age' => 30,
                'gender' => 'OTHER',
            ]);

            Invoice::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'booking_id' => $booking->id,
                'customer_id' => $user->id,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'status' => InvoiceStatus::UNPAID->value,
                'due_date' => Carbon::now()->addDays(2)->toDateString(),
            ]);

            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'bookingId' => $booking->id,
                    'bookingReference' => $booking->booking_reference,
                    'totalAmount' => $totalAmount,
                ]);
            }

            return redirect()->route('rooms')->with('success', "Booking {$booking->booking_reference} confirmed successfully!");
        });
    }

    private function storePublicPaymentBooking(Request $request)
    {
        $validated = $request->validate([
            'checkoutToken' => ['required', 'uuid'],
            'guestName' => ['required', 'string', 'max:255'],
            'guestEmail' => ['required', 'email', 'max:255'],
            'guestPhone' => ['required', 'string', 'max:50'],
            'roomId' => ['required', 'string'],
            'checkInDate' => ['required', 'date', 'after_or_equal:today'],
            'checkOutDate' => ['required', 'date', 'after:checkInDate'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['required', 'integer', 'min:0'],
            'guests' => ['required', 'array', 'min:1'],
            'guests.*.fullName' => ['required', 'string', 'max:255'],
            'specialRequest' => ['nullable', 'string', 'max:2000'],
            'paymentMethod' => ['required', 'in:ONLINE'],
        ]);

        $bookingResult = DB::transaction(function () use ($validated) {
            $room = Room::query()
                ->with('roomType')
                ->lockForUpdate()
                ->where('is_active', true)
                ->where('occupancy_status', OccupancyStatus::VACANT->value)
                ->findOrFail($validated['roomId']);
            $roomType = $room->roomType;
            abort_unless($roomType, 422, 'The selected room type is unavailable.');

            $guest = User::firstOrCreate(
                ['email' => $validated['guestEmail']],
                [
                    'name' => $validated['guestName'],
                    'full_name' => $validated['guestName'],
                    'phone' => $validated['guestPhone'],
                    'role' => UserRole::CUSTOMER->value,
                    'password' => Hash::make(Str::random(32)),
                    'is_active' => true,
                ]
            );
            abort_unless($guest->role === UserRole::CUSTOMER->value, 422, 'This email address cannot be used for a guest booking.');

            Customer::firstOrCreate(
                ['user_id' => $guest->id],
                [
                    'id_proof_number' => 'ONLINE_' . strtoupper(Str::random(8)),
                    'address' => '',
                ]
            );

            $checkIn = Carbon::parse($validated['checkInDate'])->startOfDay();
            $checkOut = Carbon::parse($validated['checkOutDate'])->startOfDay();
            $nights = max(1, $checkIn->diffInDays($checkOut));
            $subtotal = (int) $roomType->base_price * $nights;
            $tax = (int) round($subtotal * 0.12);
            $totalAmount = $subtotal + $tax;
            $checkoutExpiresAt = now()->addMinutes(30);

            $booking = Booking::create([
                'booking_reference' => 'BK-' . strtoupper(Str::random(8)),
                'customer_id' => $guest->id,
                'guest_name' => $validated['guestName'],
                'guest_email' => $validated['guestEmail'],
                'guest_phone' => $validated['guestPhone'],
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'total_guests' => $validated['adults'] + $validated['children'],
                'total_amount' => $totalAmount,
                'payment_method' => PaymentMethod::ONLINE->value,
                'special_request' => $validated['specialRequest'] ?? null,
                'status' => BookingStatus::PENDING->value,
                'booked_at' => now(),
                'public_checkout_token_hash' => hash('sha256', $validated['checkoutToken']),
                'checkout_expires_at' => $checkoutExpiresAt,
            ]);

            BookingRoom::create([
                'booking_id' => $booking->id,
                'room_type_id' => $roomType->id,
                'assigned_room_id' => $room->id,
                'price_per_night' => (int) $roomType->base_price,
            ]);

            foreach ($validated['guests'] as $index => $bookingGuest) {
                BookingGuest::create([
                    'booking_id' => $booking->id,
                    'full_name' => $bookingGuest['fullName'],
                    'age' => null,
                    'gender' => null,
                    'id_proof_number' => null,
                ]);
            }

            Invoice::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'booking_id' => $booking->id,
                'customer_id' => $guest->id,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'status' => InvoiceStatus::UNPAID->value,
                'issued_at' => now(),
            ]);

            $room->update(['occupancy_status' => OccupancyStatus::OCCUPIED->value]);

            return [
                'id' => $booking->id,
                'bookingReference' => $booking->booking_reference,
                'totalAmount' => $totalAmount,
                'checkoutExpiresAt' => $checkoutExpiresAt->toIso8601String(),
                'checkoutToken' => $validated['checkoutToken'],
            ];
        });

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => $bookingResult], 201);
        }

        return redirect()->back()
            ->with('booking', $bookingResult)
            ->with('success', 'Reservation created. Complete payment to confirm your booking.');
    }

    public function roomsApi(Request $request)
    {
        return $this->rooms($request);
    }

    public function amenitiesApi(Request $request)
    {
        $amenities = Amenity::where('is_active', true)->get()->map(fn($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'icon' => $a->icon,
            'category' => $a->category instanceof \BackedEnum ? $a->category->value : (string) $a->category,
        ]);

        return response()->json([
            'data' => $amenities,
        ]);
    }
}
