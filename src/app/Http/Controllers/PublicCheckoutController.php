<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OccupancyStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingRoom;
use App\Models\Customer;
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

class PublicCheckoutController extends Controller
{
    public function index(Request $request)
    {
        $roomTypes = RoomType::where('is_active', true)->with(['images', 'amenities'])->get()->map(function ($rt) {
            $primaryImg = $rt->images->firstWhere('is_primary', true) ?? $rt->images->first();
            return [
                'id' => $rt->id,
                'name' => $rt->name,
                'slug' => $rt->slug,
                'basePrice' => $rt->base_price,
                'maxGuests' => $rt->max_guests,
                'image' => [
                    'url' => $primaryImg?->url ?? 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304',
                    'altText' => $primaryImg?->alt_text ?? $rt->name,
                ],
            ];
        });

        return Inertia::render('public/checkout', [
            'roomTypes' => $roomTypes,
        ]);
    }

    public function store(Request $request)
    {
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

            $customer = Customer::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'id_proof_type' => 'PASSPORT',
                    'id_proof_number' => 'ONLINE_' . strtoupper(Str::random(8)),
                    'status' => 'ACTIVE',
                ]
            );

            // Select physical room
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
}
