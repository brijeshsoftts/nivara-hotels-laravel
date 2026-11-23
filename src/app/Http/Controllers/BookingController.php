<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\HousekeepingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OccupancyStatus;
use App\Enums\PaymentStatus;
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
        $query = Booking::with([
            'customer.avatar',
            'bookingRooms.room.roomType',
            'bookingRooms.room.housekeepingTasks' => fn($query) => $query->whereIn('status', [
                TaskStatus::PENDING->value,
                TaskStatus::IN_PROGRESS->value,
            ]),
            'bookingRooms.roomType',
            'invoices.payments' => fn($query) => $query->where('payment_status', PaymentStatus::COMPLETED->value),
        ])->orderByDesc('booked_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('invoiceStatus')) {
            $query->whereHas('invoices', fn($invoiceQuery) => $invoiceQuery->where('status', $request->input('invoiceStatus')));
        }
        if ($request->filled('paymentMethod')) {
            $query->where('payment_method', $request->input('paymentMethod'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('booking_reference', 'like', "%{$s}%")
                    ->orWhere('guest_name', 'like', "%{$s}%")
                    ->orWhere('guest_email', 'like', "%{$s}%")
                    ->orWhere('guest_phone', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn($customerQuery) => $customerQuery
                        ->where('full_name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%"))
                    ->orWhereHas('bookingRooms.room', fn($roomQuery) => $roomQuery->where('room_number', 'like', "%{$s}%"));
            });
        }

        $total = $query->count();
        $today = Carbon::today(config('app.timezone'))->toDateString();
        $bookings = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(function ($booking) use ($today) {
            $bookingRoom = $booking->bookingRooms->first();
            $room = $bookingRoom?->room;
            $invoice = $booking->invoices->first();
            $completedPayments = $invoice?->payments->sum('amount') ?? 0;
            $status = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
            $roomOccupancy = $room?->occupancy_status instanceof \BackedEnum ? $room->occupancy_status->value : $room?->occupancy_status;
            $housekeepingStatus = $room?->housekeeping_status instanceof \BackedEnum ? $room->housekeeping_status->value : $room?->housekeeping_status;
            $invoiceStatus = $invoice?->status instanceof \BackedEnum ? $invoice->status->value : $invoice?->status;
            $canCheckIn = $status === BookingStatus::CONFIRMED->value
                && $invoiceStatus === InvoiceStatus::PAID->value
                && (int) ($invoice?->total_amount ?? 0) > 0
                && $completedPayments >= (int) ($invoice?->total_amount ?? 0)
                && $booking->check_in_date?->toDateString() === $today
                && $room?->is_active === true
                && in_array($roomOccupancy, [OccupancyStatus::VACANT->value, OccupancyStatus::RESERVED->value], true)
                && $housekeepingStatus === HousekeepingStatus::CLEAN->value
                && ($room?->housekeepingTasks->isEmpty() ?? false);

            return [
                'id' => $booking->id,
                'bookingReference' => $booking->booking_reference,
                'status' => $status,
                'paymentMethod' => $booking->payment_method instanceof \BackedEnum ? $booking->payment_method->value : $booking->payment_method,
                'checkInDate' => $booking->check_in_date?->toIso8601String(),
                'checkOutDate' => $booking->check_out_date?->toIso8601String(),
                'totalGuests' => (int) $booking->total_guests,
                'totalAmount' => (int) $booking->total_amount,
                'bookedAt' => $booking->booked_at?->toIso8601String(),
                'customer' => [
                    'id' => $booking->customer?->id ?? $booking->id,
                    'fullName' => $booking->customer?->full_name ?? $booking->guest_name ?? 'Guest',
                    'email' => $booking->customer?->email ?? $booking->guest_email ?? '',
                    'phone' => $booking->customer?->phone ?? $booking->guest_phone ?? '',
                ],
                'bookingRoom' => $bookingRoom ? [
                    'id' => $bookingRoom->id,
                    'assignedRoom' => $room ? ['roomNumber' => $room->room_number] : null,
                    'roomType' => ['name' => $bookingRoom->roomType?->name ?? ''],
                ] : null,
                'invoice' => $invoice ? [
                    'id' => $invoice->id,
                    'status' => $invoiceStatus,
                ] : null,
                'canCheckIn' => $canCheckIn,
                'canMarkNoShow' => $status === BookingStatus::CONFIRMED->value
                    && $booking->check_in_date?->toDateString() <= $today,
            ];
        });

        $stats = $this->getStats();

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
            'filters' => $request->only(['status', 'invoiceStatus', 'paymentMethod', 'search', 'page', 'limit']),
        ]);
    }

    private function getStats(): array
    {
        $total = Booking::count();
        $confirmed = Booking::where('status', BookingStatus::CONFIRMED->value)->count();
        $pending = Booking::where('status', BookingStatus::PENDING->value)->count();
        $revenue = Booking::where('status', '!=', BookingStatus::CANCELLED->value)->sum('total_amount');

        return [
            [
                'id' => 'total-bookings',
                'title' => 'Total Bookings',
                'label' => 'Total Bookings',
                'value' => number_format($total),
                'description' => 'All-time reservations',
                'icon' => 'CalendarCheck',
                'link' => '/dashboard/bookings',
                'tone' => 'sky',
            ],
            [
                'id' => 'confirmed-bookings',
                'title' => 'Confirmed',
                'label' => 'Confirmed',
                'value' => number_format($confirmed),
                'description' => 'Ready for arrival',
                'icon' => 'CheckCircle',
                'link' => '/dashboard/bookings?status=' . BookingStatus::CONFIRMED->value,
                'tone' => 'emerald',
            ],
            [
                'id' => 'pending-bookings',
                'title' => 'Pending',
                'label' => 'Pending',
                'value' => number_format($pending),
                'description' => 'Awaiting confirmation',
                'icon' => 'Clock3',
                'link' => '/dashboard/bookings?status=' . BookingStatus::PENDING->value,
                'tone' => 'gold',
            ],
            [
                'id' => 'booking-revenue',
                'title' => 'Booking Revenue',
                'label' => 'Booking Revenue',
                'value' => '₹' . number_format($revenue),
                'description' => 'Confirmed & active bookings',
                'icon' => 'IndianRupee',
                'link' => '/dashboard/bookings',
                'tone' => 'teal',
            ],
        ];
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
        return response()->json(['data' => $this->getStats()]);
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
