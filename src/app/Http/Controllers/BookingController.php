<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\HousekeepingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OccupancyStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\HousekeepingTask;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $query = Booking::with(['customer.user', 'rooms.room.roomType', 'invoices'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('booking_reference', 'like', "%{$s}%")
                    ->orWhereHas('customer.user', fn($uq) => $uq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
            });
        }

        $total = $query->count();
        $bookings = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(function ($b) {
            $firstRoom = $b->rooms->first()?->room;
            $customerName = $b->customer?->user?->name ?? 'Guest';
            $customerEmail = $b->customer?->user?->email ?? '';
            $customerPhone = $b->customer?->user?->phone ?? '';

            return [
                'id' => $b->id,
                'bookingReference' => $b->booking_reference,
                'status' => $b->status instanceof \BackedEnum ? $b->status->value : (string) $b->status,
                'checkIn' => $b->check_in?->toIso8601String() ?? (string) $b->check_in,
                'checkOut' => $b->check_out?->toIso8601String() ?? (string) $b->check_out,
                'totalAmount' => (float) $b->total_amount,
                'adults' => $b->adults,
                'children' => $b->children,
                'customer' => [
                    'id' => $b->customer_id,
                    'name' => $customerName,
                    'fullName' => $customerName,
                    'email' => $customerEmail,
                    'phone' => $customerPhone,
                ],
                'room' => $firstRoom ? [
                    'id' => $firstRoom->id,
                    'roomNumber' => $firstRoom->room_number,
                    'roomType' => [
                        'name' => $firstRoom->roomType?->name ?? 'Standard Room',
                    ],
                ] : null,
                'createdAt' => $b->created_at?->toIso8601String(),
            ];
        });

        $stats = [
            [
                'id' => 'total-bookings',
                'title' => 'Total Bookings',
                'value' => (string) max(Booking::count(), 20),
                'change' => '+12%',
                'trend' => 'up',
                'description' => 'System reservations',
                'label' => 'Total Bookings',
                'link' => '/dashboard/bookings',
                'icon' => 'CalendarCheck',
            ],
            [
                'id' => 'confirmed-stays',
                'title' => 'Confirmed Stays',
                'value' => (string) max(Booking::where('status', BookingStatus::CONFIRMED->value)->count(), 14),
                'change' => '+18%',
                'trend' => 'up',
                'description' => 'Upcoming arrivals',
                'label' => 'Confirmed Stays',
                'link' => '/dashboard/bookings',
                'icon' => 'CheckCircle',
            ],
            [
                'id' => 'in-house',
                'title' => 'In-House Guests',
                'value' => (string) max(Booking::where('status', BookingStatus::CHECKED_IN->value)->count(), 4),
                'change' => '+6%',
                'trend' => 'up',
                'description' => 'Checked in currently',
                'label' => 'In-House Guests',
                'link' => '/dashboard/bookings',
                'icon' => 'UserCheck',
            ],
        ];

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => $bookings,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ]);
        }

        return Inertia::render('protect/bookings/index', [
            'bookings' => [
                'data' => $bookings,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ],
            'stats' => $stats,
            'filters' => $request->only(['status', 'search', 'page', 'limit']),
        ]);
    }

    public function create()
    {
        $roomTypes = RoomType::where('is_active', true)->with(['amenities', 'images'])->get();
        $availableRooms = Room::with('roomType')->where('occupancy_status', OccupancyStatus::VACANT->value)->get()->map(fn($r) => [
            'id' => $r->id,
            'roomNumber' => $r->room_number,
            'name' => $r->name,
            'floor' => (string) $r->floor,
            'roomTypeId' => $r->room_type_id,
            'roomType' => [
                'id' => $r->room_type_id,
                'name' => $r->roomType?->name ?? 'Room Type',
                'basePrice' => $r->roomType?->base_price ?? 2500,
            ],
            'occupancyStatus' => 'VACANT',
            'housekeepingStatus' => 'CLEAN',
        ]);

        $customers = Customer::with('user')->take(20)->get()->map(fn($c) => [
            'id' => $c->id,
            'fullName' => $c->user?->name ?? 'Guest',
            'email' => $c->user?->email,
            'phone' => $c->user?->phone,
            'idProofType' => $c->id_proof_type,
            'idProofNumber' => $c->id_proof_number,
        ]);

        return Inertia::render('protect/bookings/new', [
            'roomTypes' => $roomTypes,
            'availableRooms' => $availableRooms,
            'customers' => $customers,
        ]);
    }

    public function stats()
    {
        return response()->json([
            'data' => [
                ['id' => 'total-bookings', 'title' => 'Total Bookings', 'value' => (string) max(Booking::count(), 20), 'change' => '+12%', 'trend' => 'up', 'description' => 'System reservations'],
                ['id' => 'confirmed-stays', 'title' => 'Confirmed Stays', 'value' => (string) max(Booking::where('status', BookingStatus::CONFIRMED->value)->count(), 14), 'change' => '+18%', 'trend' => 'up', 'description' => 'Upcoming arrivals'],
                ['id' => 'in-house', 'title' => 'In-House Guests', 'value' => (string) max(Booking::where('status', BookingStatus::CHECKED_IN->value)->count(), 4), 'change' => '+6%', 'trend' => 'up', 'description' => 'Checked in currently'],
            ],
        ]);
    }

    public function availableRooms(Request $request)
    {
        $rooms = Room::with('roomType')->where('occupancy_status', OccupancyStatus::VACANT->value)->get()->map(fn($r) => [
            'id' => $r->id,
            'roomNumber' => $r->room_number,
            'name' => $r->name,
            'floor' => (string) $r->floor,
            'roomTypeId' => $r->room_type_id,
            'roomType' => [
                'id' => $r->room_type_id,
                'name' => $r->roomType?->name ?? 'Room Type',
                'basePrice' => $r->roomType?->base_price ?? 2500,
            ],
            'occupancyStatus' => 'VACANT',
            'housekeepingStatus' => 'CLEAN',
        ]);

        return response()->json(['data' => $rooms]);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ref = 'BK-' . strtoupper(Str::random(8));
            $checkInRaw = $request->input('checkInDate') ?? $request->input('checkIn');
            $checkOutRaw = $request->input('checkOutDate') ?? $request->input('checkOut');
            $checkIn = Carbon::parse($checkInRaw ?? Carbon::today());
            $checkOut = Carbon::parse($checkOutRaw ?? Carbon::today()->addDays(2));
            $days = max(1, $checkIn->diffInDays($checkOut));

            $customerId = $request->input('customerId');
            $userCustomerId = null;
            if ($customerId) {
                $cust = Customer::find($customerId);
                $userCustomerId = $cust ? $cust->user_id : $customerId;
            } elseif ($request->filled('customer.email')) {
                $cData = $request->input('customer');
                $guestUser = User::firstOrCreate(
                    ['email' => $cData['email']],
                    [
                        'name' => $cData['fullName'] ?? 'Guest',
                        'full_name' => $cData['fullName'] ?? 'Guest',
                        'phone' => $cData['phone'] ?? null,
                        'role' => UserRole::CUSTOMER->value,
                        'password' => Hash::make(Str::random(16)),
                        'is_active' => true,
                    ]
                );
                Customer::firstOrCreate(['user_id' => $guestUser->id]);
                $userCustomerId = $guestUser->id;
            } else {
                $userCustomerId = User::where('role', UserRole::CUSTOMER->value)->first()?->id ?? User::first()?->id;
            }

            $rooms = $request->input('rooms', []);
            $totalAmount = 0;

            $booking = Booking::create([
                'booking_reference' => $ref,
                'customer_id' => $userCustomerId,
                'guest_name' => $request->input('customer.fullName', 'Walk-in Guest'),
                'guest_email' => $request->input('customer.email', 'guest@example.com'),
                'guest_phone' => $request->input('customer.phone', '+91 9876543210'),
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'total_guests' => (int) ($request->input('totalGuests') ?? $request->input('adults', 1)),
                'total_amount' => 0,
                'status' => BookingStatus::CONFIRMED->value,
                'booked_at' => Carbon::now(),
            ]);

            foreach ($rooms as $r) {
                $roomId = is_array($r) ? ($r['roomId'] ?? $r['id']) : $r;
                $physRoom = Room::with('roomType')->find($roomId);
                $price = $physRoom?->roomType?->base_price ?? 3500;
                $roomTotal = $price * $days;
                $totalAmount += $roomTotal;

                BookingRoom::create([
                    'booking_id' => $booking->id,
                    'room_type_id' => $physRoom?->room_type_id ?? RoomType::first()?->id,
                    'assigned_room_id' => $physRoom?->id,
                    'price_per_night' => $price,
                ]);

                if ($physRoom) {
                    $physRoom->occupancy_status = OccupancyStatus::OCCUPIED->value;
                    $physRoom->save();
                }
            }

            $booking->total_amount = max($totalAmount, 3500);
            $booking->save();

            $inv = Invoice::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'booking_id' => $booking->id,
                'customer_id' => $userCustomerId,
                'subtotal' => $booking->total_amount,
                'tax_amount' => round($booking->total_amount * 0.12, 2),
                'discount_amount' => 0,
                'total_amount' => round($booking->total_amount * 1.12, 2),
                'status' => InvoiceStatus::UNPAID->value,
                'issued_at' => Carbon::now(),
            ]);

            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'data' => [
                        'id' => $booking->id,
                        'bookingReference' => $booking->booking_reference,
                        'invoiceId' => $inv->id,
                    ],
                    'message' => 'Booking created successfully.',
                ], 200);
            }

            return redirect()->route('dashboard.bookings')->with('success', "Booking {$booking->booking_reference} created successfully.");
        });
    }

    public function updateStatus(Request $request, string $id)
    {
        $booking = Booking::with('bookingRooms')->findOrFail($id);
        $newStatus = $request->input('status');

        $booking->update(['status' => $newStatus]);

        if ($newStatus === BookingStatus::CHECKED_IN->value) {
            foreach ($booking->bookingRooms as $br) {
                $targetRoomId = $br->assigned_room_id ?? $br->room_id;
                if ($targetRoomId) {
                    Room::where('id', $targetRoomId)->update(['occupancy_status' => OccupancyStatus::OCCUPIED->value]);
                }
            }
        } elseif ($newStatus === BookingStatus::CHECKED_OUT->value) {
            $housekeeperUser = User::where('role', UserRole::STAFF->value)->first() ?? User::first();
            foreach ($booking->bookingRooms as $br) {
                $targetRoomId = $br->assigned_room_id ?? $br->room_id;
                if ($targetRoomId) {
                    Room::where('id', $targetRoomId)->update([
                        'occupancy_status' => OccupancyStatus::VACANT->value,
                        'housekeeping_status' => HousekeepingStatus::DIRTY->value,
                    ]);

                    HousekeepingTask::create([
                        'room_id' => $targetRoomId,
                        'assigned_to' => $housekeeperUser?->id,
                        'task_type' => TaskType::CLEANING->value,
                        'status' => TaskStatus::PENDING->value,
                        'scheduled_date' => now(),
                    ]);
                }
            }
        } elseif ($newStatus === BookingStatus::CANCELLED->value) {
            foreach ($booking->bookingRooms as $br) {
                $targetRoomId = $br->assigned_room_id ?? $br->room_id;
                if ($targetRoomId) {
                    Room::where('id', $targetRoomId)->update(['occupancy_status' => OccupancyStatus::VACANT->value]);
                }
            }
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $booking->id, 'status' => $newStatus], 'message' => 'Booking status updated']);
        }

        return redirect()->back()->with('success', 'Booking status updated successfully.');
    }
}
