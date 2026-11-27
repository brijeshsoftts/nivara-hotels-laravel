<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\HousekeepingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\NotificationType;
use App\Enums\OccupancyStatus;
use App\Enums\PaymentMethod;
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
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isCustomer = $user?->role === UserRole::CUSTOMER->value;
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $query = Booking::with([
            'customer.avatar',
            'bookingRooms.room.roomType',
            'bookingRooms.roomType',
            'invoices.payments' => fn($query) => $query->where('payment_status', PaymentStatus::COMPLETED->value),
        ])->orderByDesc('booked_at');

        if ($isCustomer) {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'NON_CANCELLED') {
                $query->where('status', '!=', BookingStatus::CANCELLED->value);
            } else {
                $query->where('status', $request->input('status'));
            }
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
                        ->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%"))
                    ->orWhereHas('bookingRooms.room', fn($roomQuery) => $roomQuery->where('room_number', 'like', "%{$s}%"));
            });
        }

        $total = $query->count();
        $today = Carbon::today(config('app.timezone'))->toDateString();
        $bookings = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(function ($booking) use ($today, $isCustomer) {
            $bookingRoom = $booking->bookingRooms->first();
            $room = $bookingRoom?->room;
            $invoice = $booking->invoices->first();
            $status = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
            $invoiceStatus = $invoice?->status instanceof \BackedEnum ? $invoice->status->value : $invoice?->status;

            if ($isCustomer) {
                return [
                    'id' => $booking->id,
                    'bookingNumber' => $booking->booking_reference,
                    'roomType' => $bookingRoom?->roomType?->name ?? 'Unassigned',
                    'roomNumber' => $room?->room_number,
                    'checkIn' => $booking->check_in_date?->toIso8601String(),
                    'checkOut' => $booking->check_out_date?->toIso8601String(),
                    'totalAmount' => (int) $booking->total_amount,
                    'status' => $status,
                    'invoice' => $invoice ? [
                        'id' => $invoice->id,
                        'status' => $invoiceStatus,
                    ] : null,
                ];
            }

            $canCheckIn = $this->canCheckIn($booking, $today);

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
                    'name' => $booking->customer?->name ?? $booking->guest_name ?? 'Guest',
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

        $stats = $this->getStats($user);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
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

    private function canCheckIn(Booking $booking, string $today): bool
    {
        $bookingRoom = $booking->bookingRooms->first();
        $room = $bookingRoom?->room;
        $invoice = $booking->invoices->first();
        $status = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;
        $invoiceStatus = $invoice?->status instanceof \BackedEnum ? $invoice->status->value : $invoice?->status;
        $completedPayments = $invoice?->payments->sum('amount') ?? 0;

        return $status === BookingStatus::CONFIRMED->value
            && $invoiceStatus === InvoiceStatus::PAID->value
            && (int) ($invoice?->total_amount ?? 0) > 0
            && $completedPayments >= (int) ($invoice?->total_amount ?? 0)
            && $booking->check_in_date?->toDateString() === $today
            && $bookingRoom !== null
            && $room?->is_active === true;
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless(in_array($user->role, [
            UserRole::ADMIN->value,
            UserRole::MANAGER->value,
            UserRole::STAFF->value,
            UserRole::CUSTOMER->value,
        ], true), 403);
        abort_unless(
            $user->role !== UserRole::STAFF->value || $user->category !== 'HOUSEKEEPER',
            403,
        );

        $booking = Booking::with([
            'customer.avatar',
            'customer.customer',
            'createdBy.avatar',
            'bookingRooms.room.roomAssignments.user',
            'bookingRooms.roomType',
            'bookingGuests',
            'invoices.payments.receivedBy',
        ])->findOrFail($id);

        if ($user->role === UserRole::CUSTOMER->value) {
            abort_unless($booking->customer_id === $user->id, 404);

            $bookingRoom = $booking->bookingRooms->first();
            $room = $bookingRoom?->room;

            return response()->json(['data' => [
                'id' => $booking->id,
                'bookingNumber' => $booking->booking_reference,
                'status' => $booking->status->value,
                'roomType' => $bookingRoom?->roomType?->name ?? '',
                'roomNumber' => $room?->room_number,
                'checkIn' => $booking->check_in_date?->toIso8601String(),
                'checkOut' => $booking->check_out_date?->toIso8601String(),
                'duration' => max(0, $booking->check_in_date->diffInDays($booking->check_out_date)),
                'adults' => $booking->bookingGuests->filter(fn($guest) => $guest->age === null || $guest->age >= 18)->count(),
                'children' => $booking->bookingGuests->filter(fn($guest) => $guest->age !== null && $guest->age < 18)->count(),
                'bookedOn' => $booking->booked_at?->toIso8601String(),
            ]]);
        }

        $bookingRoom = $booking->bookingRooms->first();
        $room = $bookingRoom?->room;
        $roomType = $bookingRoom?->roomType;
        $customer = $booking->customer;
        $customerProfile = $customer?->customer;
        $creator = $booking->createdBy;
        $invoice = $booking->invoices->first();
        $enumValue = fn($value) => $value instanceof \BackedEnum ? $value->value : $value;

        if ($user->role === UserRole::CUSTOMER->value) {
            $nights = $booking->check_in_date && $booking->check_out_date
                ? max(1, (int) $booking->check_in_date->diffInDays($booking->check_out_date))
                : 1;

            return response()->json(['data' => [
                'id' => $booking->id,
                'bookingNumber' => $booking->booking_reference,
                'status' => $enumValue($booking->status),
                'roomType' => $roomType?->name ?? 'Unassigned',
                'roomNumber' => $room?->room_number,
                'checkIn' => $booking->check_in_date?->toIso8601String(),
                'checkOut' => $booking->check_out_date?->toIso8601String(),
                'adults' => max(1, (int) $booking->total_guests),
                'children' => 0,
                'duration' => $nights,
                'bookedOn' => ($booking->booked_at ?? $booking->created_at)?->toIso8601String(),
                'totalAmount' => (int) $booking->total_amount,
                'invoice' => $invoice ? [
                    'id' => $invoice->id,
                    'status' => $enumValue($invoice->status),
                ] : null,
            ]]);
        }

        return response()->json(['data' => [
            'id' => $booking->id,
            'bookingReference' => $booking->booking_reference,
            'customerId' => $booking->customer_id ?? '',
            'checkInDate' => $booking->check_in_date?->toIso8601String(),
            'checkOutDate' => $booking->check_out_date?->toIso8601String(),
            'totalGuests' => (int) $booking->total_guests,
            'totalAmount' => (int) $booking->total_amount,
            'paymentMethod' => $enumValue($booking->payment_method),
            'specialRequest' => $booking->special_request,
            'status' => $enumValue($booking->status),
            'bookedAt' => $booking->booked_at?->toIso8601String(),
            'checkedInAt' => $booking->checked_in_at?->toIso8601String(),
            'checkedOutAt' => $booking->checked_out_at?->toIso8601String(),
            'cancelledAt' => $booking->cancelled_at?->toIso8601String(),
            'createdBy' => $booking->created_by ?? '',
            'createdAt' => $booking->created_at?->toIso8601String(),
            'updatedAt' => $booking->updated_at?->toIso8601String(),
            'customer' => [
                'id' => $customer?->id ?? $booking->id,
                'name' => $customer?->name ?? $booking->guest_name ?? 'Guest',
                'email' => $customer?->email ?? $booking->guest_email ?? '',
                'phone' => $customer?->phone ?? $booking->guest_phone ?? '',
                'profileImage' => $customer?->avatar ? [
                    'url' => $customer->avatar->url,
                    'altText' => $customer->avatar->alt_text,
                ] : null,
                'profile' => $customerProfile ? [
                    'idProofNumber' => $customerProfile->id_proof_number ?? '',
                    'address' => $customerProfile->address ?? '',
                ] : null,
            ],
            'creator' => [
                'id' => $creator?->id ?? '',
                'name' => $creator?->name ?? 'Guest checkout',
                'email' => $creator?->email ?? '',
                'phone' => $creator?->phone ?? '',
                'profileImage' => $creator?->avatar ? [
                    'url' => $creator->avatar->url,
                    'altText' => $creator->avatar->alt_text,
                ] : null,
            ],
            'bookingRoom' => $bookingRoom ? [
                'id' => $bookingRoom->id,
                'assignedRoom' => $room ? [
                    'id' => $room->id,
                    'roomNumber' => $room->room_number,
                    'name' => $room->name,
                    'floor' => (int) $room->floor,
                    'housekeepers' => $room->roomAssignments
                        ->filter(fn($assignment) => $assignment->is_active)
                        ->map(fn($assignment) => $assignment->user)
                        ->filter()
                        ->map(fn($housekeeper) => [
                            'id' => $housekeeper->id,
                            'name' => $housekeeper->name,
                            'email' => $housekeeper->email ?? '',
                            'phone' => $housekeeper->phone ?? '',
                        ])->values(),
                ] : null,
                'roomType' => $roomType ? [
                    'id' => $roomType->id,
                    'name' => $roomType->name,
                    'slug' => $roomType->slug,
                    'maxGuests' => (int) $roomType->max_guests,
                    'basePrice' => (int) $roomType->base_price,
                    'currency' => $roomType->currency ?? 'INR',
                    'bedType' => $enumValue($roomType->bed_type),
                    'bedCount' => (int) $roomType->bed_count,
                ] : null,
                'pricePerNight' => (int) $bookingRoom->price_per_night,
            ] : null,
            'bookingGuests' => $booking->bookingGuests->map(fn($guest) => [
                'id' => $guest->id,
                'name' => $guest->full_name,
                'age' => $guest->age,
                'gender' => $enumValue($guest->gender),
                'idProofNumber' => $guest->id_proof_number,
            ])->values(),
            'invoice' => $invoice ? [
                'id' => $invoice->id,
                'invoiceNumber' => $invoice->invoice_number,
                'subtotal' => (int) $invoice->subtotal,
                'taxAmount' => (int) $invoice->tax_amount,
                'discountAmount' => (int) $invoice->discount_amount,
                'totalAmount' => (int) $invoice->total_amount,
                'paymentMethod' => $enumValue($booking->payment_method),
                'status' => $enumValue($invoice->status),
                'issuedAt' => $invoice->issued_at?->toIso8601String(),
                'payments' => $invoice->payments->map(fn($payment) => [
                    'id' => $payment->id,
                    'paymentReference' => $payment->payment_reference,
                    'transactionId' => $payment->transaction_id,
                    'amount' => (int) $payment->amount,
                    'paymentMethod' => $enumValue($payment->payment_method),
                    'paymentStatus' => $enumValue($payment->payment_status),
                    'paidAt' => $payment->paid_at?->toIso8601String(),
                    'createdAt' => $payment->created_at?->toIso8601String(),
                    'recordedBy' => $payment->receivedBy ? [
                        'id' => $payment->receivedBy->id,
                        'name' => $payment->receivedBy->name,
                    ] : null,
                ])->values(),
            ] : null,
        ]]);
    }

    private function getStats(?User $user = null): array
    {
        $isCustomer = $user?->role === UserRole::CUSTOMER->value;
        $baseQuery = Booking::query();
        if ($isCustomer) {
            $baseQuery->where('customer_id', $user->id);
        }

        $total = (clone $baseQuery)->count();
        $confirmed = (clone $baseQuery)->where('status', BookingStatus::CONFIRMED->value)->count();
        $pending = (clone $baseQuery)->where('status', BookingStatus::PENDING->value)->count();
        $revenue = (clone $baseQuery)->where('status', '!=', BookingStatus::CANCELLED->value)->sum('total_amount');

        return [
            [
                'id' => 'total-bookings',
                'title' => 'Total Bookings',
                'label' => 'Total Bookings',
                'value' => number_format($total),
                'description' => $isCustomer ? 'Your reservations' : 'All-time reservations',
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
                'title' => $isCustomer ? 'Total Spent' : 'Booking Revenue',
                'label' => $isCustomer ? 'Total Spent' : 'Booking Revenue',
                'value' => '₹' . number_format($revenue),
                'description' => 'Confirmed & active bookings',
                'icon' => 'IndianRupee',
                'link' => '/dashboard/bookings?status=NON_CANCELLED',
                'tone' => 'teal',
            ],
        ];
    }

    public function create()
    {
        $roomTypes = RoomType::where('is_active', true)->with(['amenities', 'images'])->get();
        $availableRooms = $this->availableRoomQuery()->get()->map(
            fn(Room $room) => $this->mapAvailableRoom($room)
        );

        $customers = Customer::with('user')->take(20)->get()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->user?->name ?? 'Guest',
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

    public function stats(Request $request)
    {
        return response()->json(['data' => $this->getStats($request->user())]);
    }

    public function availableRooms(Request $request)
    {
        $validated = $request->validate([
            'checkInDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'checkOutDate' => ['required', 'date_format:Y-m-d', 'after:checkInDate'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['required', 'integer', 'min:0', 'max:20'],
        ]);
        $checkIn = Carbon::parse($validated['checkInDate']);
        $checkOut = Carbon::parse($validated['checkOutDate']);
        $totalGuests = $validated['adults'] + $validated['children'];
        $rooms = $this->availableRoomQuery($totalGuests, $checkIn, $checkOut)
            ->get()
            ->map(fn(Room $room) => $this->mapAvailableRoom($room));

        return response()->json(['data' => $rooms]);
    }

    private function availableRoomQuery(
        int $totalGuests = 0,
        ?Carbon $checkIn = null,
        ?Carbon $checkOut = null,
    ) {
        $query = Room::query()
            ->with(['roomType.amenities', 'roomType.images'])
            ->where('is_active', true)
            ->where(function ($statuses) use ($checkIn) {
                $statuses->whereIn('occupancy_status', [
                    OccupancyStatus::VACANT->value,
                    OccupancyStatus::RESERVED->value,
                ]);

                if ($checkIn) {
                    $statuses->orWhere(function ($occupied) use ($checkIn) {
                        $occupied
                            ->where('occupancy_status', OccupancyStatus::OCCUPIED->value)
                            ->whereHas('bookingRooms.booking', fn($bookings) => $bookings
                                ->whereNotIn('status', [
                                    BookingStatus::CANCELLED->value,
                                    BookingStatus::NO_SHOW->value,
                                    BookingStatus::CHECKED_OUT->value,
                                ])
                                ->where('check_out_date', '<=', $checkIn));
                    });
                }
            })
            ->whereHas('roomType', fn($roomTypes) => $roomTypes
                ->where('is_active', true)
                ->where('max_guests', '>=', $totalGuests));

        if ($checkIn && $checkOut) {
            $overlappingRoomIds = BookingRoom::query()
                ->whereNotNull('assigned_room_id')
                ->whereHas('booking', fn($bookings) => $bookings
                    ->whereNotIn('status', [
                        BookingStatus::CANCELLED->value,
                        BookingStatus::NO_SHOW->value,
                        BookingStatus::CHECKED_OUT->value,
                    ])
                    ->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn))
                ->select('assigned_room_id');

            $query->whereNotIn('id', $overlappingRoomIds);
        }

        return $query;
    }

    private function mapAvailableRoom(Room $room): array
    {
        $roomType = $room->roomType;
        $image = $roomType?->images->first();

        return [
            'id' => $room->id,
            'roomNumber' => $room->room_number,
            'floor' => (int) $room->floor,
            'roomName' => $room->name,
            'roomType' => [
                'id' => $roomType?->id ?? $room->room_type_id,
                'name' => $roomType?->name ?? 'Room Type',
                'maxGuests' => (int) ($roomType?->max_guests ?? 1),
                'basePrice' => (int) ($roomType?->base_price ?? 2500),
                'currency' => 'INR',
                'bedType' => $roomType?->bed_type?->value ?? (string) ($roomType?->bed_type ?? ''),
                'bedCount' => (int) ($roomType?->bed_count ?? 1),
                'sizeSqFt' => $roomType?->size_sq_ft,
                'image' => $image ? [
                    'url' => $image->url,
                    'altText' => $image->alt_text,
                ] : null,
                'amenities' => $roomType?->amenities->map(fn($amenity) => [
                    'id' => $amenity->id,
                    'name' => $amenity->name,
                    'icon' => $amenity->icon,
                ])->values() ?? collect(),
            ],
        ];
    }

    public function store(Request $request)
    {
        $legacyRooms = $request->input('rooms', []);
        $legacyRoom = is_array($legacyRooms) ? ($legacyRooms[0] ?? null) : null;
        $legacyRoomId = is_array($legacyRoom)
            ? ($legacyRoom['roomId'] ?? $legacyRoom['id'] ?? null)
            : $legacyRoom;
        $hasGuestDetails = $request->has('guests');

        $request->merge([
            'checkInDate' => $request->input('checkInDate', $request->input('checkIn', Carbon::today()->toDateString())),
            'checkOutDate' => $request->input('checkOutDate', $request->input('checkOut', Carbon::today()->addDays(2)->toDateString())),
            'adults' => $request->input('adults', $request->input('totalGuests', 1)),
            'children' => $request->input('children', 0),
            'roomId' => $request->input('roomId', $legacyRoomId),
            'guests' => $request->input('guests', []),
            'paymentMethod' => $request->input('paymentMethod', PaymentMethod::CASH->value),
        ]);

        $validated = $request->validate([
            'customerId' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }
                    $isUserCustomer = User::query()
                        ->where('id', $value)
                        ->where('role', UserRole::CUSTOMER->value)
                        ->whereNull('soft_deleted_at')
                        ->exists();
                    $isCustomerProfile = Customer::query()
                        ->where('id', $value)
                        ->whereHas('user', fn($q) => $q->whereNull('soft_deleted_at'))
                        ->exists();

                    if (! $isUserCustomer && ! $isCustomerProfile) {
                        $fail('The selected customer id is invalid.');
                    }
                },
            ],
            'checkInDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'checkOutDate' => ['required', 'date_format:Y-m-d', 'after:checkInDate'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['required', 'integer', 'min:0', 'max:20'],
            'roomId' => ['required', 'string', 'exists:rooms,id'],
            'guests' => ['sometimes', 'array'],
            'guests.*.name' => ['required', 'string', 'max:255'],
            'guests.*.age' => ['required', 'integer', 'min:1', 'max:120'],
            'guests.*.gender' => ['required', Rule::in(['MALE', 'FEMALE', 'OTHER'])],
            'guests.*.idProofNumber' => ['nullable', 'string', 'max:255'],
            'specialRequest' => ['nullable', 'string', 'max:1000'],
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
        ]);
        $guestDetails = $validated['guests'] ?? [];
        if ($hasGuestDetails) {
            abort_unless(
                count($guestDetails) === $validated['adults'] + $validated['children'],
                422,
                'Guest count must match the requested adults and children count.'
            );
        }
        $paymentMethod = $validated['paymentMethod'];

        return DB::transaction(function () use ($request, $validated, $guestDetails, $hasGuestDetails, $paymentMethod) {
            $ref = 'BK-' . strtoupper(Str::random(8));
            $checkIn = Carbon::parse($validated['checkInDate']);
            $checkOut = Carbon::parse($validated['checkOutDate']);
            $days = max(1, (int) $checkIn->diffInDays($checkOut));
            $customerUser = null;
            if (! empty($validated['customerId'])) {
                $customerUser = User::query()
                    ->where('id', $validated['customerId'])
                    ->where('role', UserRole::CUSTOMER->value)
                    ->whereNull('soft_deleted_at')
                    ->first();

                if (! $customerUser) {
                    $customerProfile = Customer::with('user')
                        ->whereHas('user', fn($q) => $q->whereNull('soft_deleted_at'))
                        ->findOrFail($validated['customerId']);
                    $customerUser = $customerProfile->user;
                }
            }
            if (! $customerUser && $request->filled('customer.email')) {
                $customerData = $request->input('customer');
                $customerUser = User::firstOrCreate(
                    ['email' => $customerData['email']],
                    [
                        'name' => $customerData['name'] ?? 'Guest',
                        'phone' => $customerData['phone'] ?? null,
                        'role' => UserRole::CUSTOMER->value,
                        'password' => Hash::make(Str::random(16)),
                        'is_active' => true,
                    ]
                );
                Customer::firstOrCreate(
                    ['user_id' => $customerUser->id],
                    [
                        'id_proof_number' => 'ID_' . strtoupper(Str::random(8)),
                        'address' => 'Nivara Residence',
                    ]
                );
            }
            $room = Room::with('roomType')->lockForUpdate()->findOrFail($validated['roomId']);
            $totalGuests = $request->has('totalGuests') && ! $request->has('adults')
                ? (int) $request->input('totalGuests')
                : $validated['adults'] + $validated['children'];

            abort_unless(
                $room->is_active
                    && (in_array($room->occupancy_status->value, [OccupancyStatus::VACANT->value, OccupancyStatus::RESERVED->value], true)
                        || ($room->occupancy_status === OccupancyStatus::OCCUPIED
                            && $this->roomHasPriorBookingCheckout($room->id, $checkIn)))
                    && $room->roomType?->is_active
                    && $room->roomType->max_guests >= $totalGuests
                    && ! $this->roomHasOverlappingBooking($room->id, $checkIn, $checkOut),
                422,
                'The selected room is no longer available for these dates.'
            );

            $price = (int) $room->roomType->base_price;
            $subtotal = $price * $days;
            $taxAmount = (int) round($subtotal * 0.18);
            $userCustomerId = $customerUser?->id;

            $booking = Booking::create([
                'booking_reference' => $ref,
                'customer_id' => $userCustomerId,
                'guest_name' => $guestDetails[0]['name'] ?? $customerUser?->name ?? $request->input('customer.name', 'Walk-in Guest'),
                'guest_email' => $customerUser?->email ?? $request->input('customer.email'),
                'guest_phone' => $customerUser?->phone ?? $request->input('customer.phone'),
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'total_guests' => $totalGuests,
                'total_amount' => $subtotal,
                'payment_method' => $paymentMethod,
                'status' => $paymentMethod === PaymentMethod::ONLINE->value
                    ? BookingStatus::PENDING->value
                    : BookingStatus::CONFIRMED->value,
                'special_request' => $validated['specialRequest'] ?? null,
                'created_by' => $request->user()?->id,
                'booked_at' => Carbon::now(),
            ]);

            BookingRoom::create([
                'booking_id' => $booking->id,
                'room_type_id' => $room->room_type_id,
                'assigned_room_id' => $room->id,
                'price_per_night' => $price,
            ]);

            foreach ($guestDetails as $guest) {
                BookingGuest::create([
                    'booking_id' => $booking->id,
                    'full_name' => $guest['name'],
                    'age' => $guest['age'],
                    'gender' => $guest['gender'],
                    'id_proof_number' => $guest['idProofNumber'] ?? null,
                ]);
            }

            $room->occupancy_status = $hasGuestDetails
                ? OccupancyStatus::RESERVED->value
                : OccupancyStatus::OCCUPIED->value;
            $room->save();

            $inv = Invoice::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'booking_id' => $booking->id,
                'customer_id' => $userCustomerId,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'total_amount' => $subtotal + $taxAmount,
                'status' => InvoiceStatus::UNPAID->value,
                'issued_at' => Carbon::now(),
            ]);

            if ($booking->status === BookingStatus::CONFIRMED && $userCustomerId) {
                $this->notificationService->createForUsers(
                    [(string) $userCustomerId],
                    NotificationType::BOOKING_CONFIRMED,
                    'Booking confirmed',
                    "Booking {$booking->booking_reference} is confirmed for {$checkIn->toDateString()} to {$checkOut->toDateString()}. Guests: {$booking->total_guests}.",
                    'BOOKING',
                    $booking->id,
                );
            }

            if ($booking->status === BookingStatus::CONFIRMED) {
                $this->notificationService->createForRoles(
                    [UserRole::ADMIN, UserRole::MANAGER],
                    NotificationType::BOOKING_CONFIRMED,
                    'New booking confirmed',
                    "Booking {$booking->booking_reference} is confirmed for {$checkIn->toDateString()} to {$checkOut->toDateString()}. Guests: {$booking->total_guests}.",
                    'BOOKING',
                    $booking->id,
                    $request->user()?->id ? [(string) $request->user()->id] : [],
                );
            }

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
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

    private function roomHasOverlappingBooking(string $roomId, Carbon $checkIn, Carbon $checkOut): bool
    {
        return BookingRoom::query()
            ->where('assigned_room_id', $roomId)
            ->whereHas('booking', fn($bookings) => $bookings
                ->whereNotIn('status', [
                    BookingStatus::CANCELLED->value,
                    BookingStatus::NO_SHOW->value,
                    BookingStatus::CHECKED_OUT->value,
                ])
                ->where('check_in_date', '<', $checkOut)
                ->where('check_out_date', '>', $checkIn))
            ->exists();
    }

    private function roomHasPriorBookingCheckout(string $roomId, Carbon $checkIn): bool
    {
        return BookingRoom::query()
            ->where('assigned_room_id', $roomId)
            ->whereHas('booking', fn($bookings) => $bookings
                ->whereNotIn('status', [
                    BookingStatus::CANCELLED->value,
                    BookingStatus::NO_SHOW->value,
                    BookingStatus::CHECKED_OUT->value,
                ])
                ->where('check_out_date', '<=', $checkIn))
            ->exists();
    }

    public function updateStatus(Request $request, string $id)
    {
        $booking = Booking::with([
            'bookingRooms.room',
            'invoices.payments' => fn($query) => $query->where('payment_status', PaymentStatus::COMPLETED->value),
        ])->findOrFail($id);
        $previousStatus = $booking->status;
        $newStatus = $request->input('status');

        if ($newStatus === BookingStatus::CHECKED_IN->value) {
            $today = Carbon::today(config('app.timezone'))->toDateString();
            abort_unless($this->canCheckIn($booking, $today), 422, 'This booking is not eligible for check-in today.');
        }

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

        if (
            $booking->customer_id
            && $previousStatus !== $booking->status
            && in_array($newStatus, [BookingStatus::CONFIRMED->value, BookingStatus::CANCELLED->value], true)
        ) {
            $isCancellation = $newStatus === BookingStatus::CANCELLED->value;
            $this->notificationService->createForUsers(
                [(string) $booking->customer_id],
                $isCancellation ? NotificationType::BOOKING_CANCELLED : NotificationType::BOOKING_CONFIRMED,
                $isCancellation ? 'Booking cancelled' : 'Booking confirmed',
                $isCancellation
                    ? "Booking {$booking->booking_reference} was cancelled. Stay dates: {$booking->check_in_date->toDateString()} to {$booking->check_out_date->toDateString()}."
                    : "Booking {$booking->booking_reference} is confirmed for {$booking->check_in_date->toDateString()} to {$booking->check_out_date->toDateString()}. Guests: {$booking->total_guests}.",
                'BOOKING',
                $booking->id,
            );
            $this->notificationService->createForRoles(
                [UserRole::ADMIN, UserRole::MANAGER],
                $isCancellation ? NotificationType::BOOKING_CANCELLED : NotificationType::BOOKING_CONFIRMED,
                $isCancellation ? 'Booking cancelled' : 'Booking confirmed',
                $isCancellation
                    ? "Booking {$booking->booking_reference} was cancelled. Stay dates: {$booking->check_in_date->toDateString()} to {$booking->check_out_date->toDateString()}."
                    : "Booking {$booking->booking_reference} is confirmed for {$booking->check_in_date->toDateString()} to {$booking->check_out_date->toDateString()}. Guests: {$booking->total_guests}.",
                'BOOKING',
                $booking->id,
                $request->user()?->id ? [(string) $request->user()->id] : [],
            );
        }

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $booking->id, 'status' => $newStatus], 'message' => 'Booking status updated']);
        }

        return redirect()->back()->with('success', 'Booking status updated successfully.');
    }
}
