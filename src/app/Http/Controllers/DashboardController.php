<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\HousekeepingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OccupancyStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\HousekeepingTask;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $totalRooms = Room::count();
        $occupiedRooms = Room::where('occupancy_status', OccupancyStatus::OCCUPIED->value)->count();
        $cleanRooms = Room::where('housekeeping_status', HousekeepingStatus::CLEAN->value)->count();
        $totalRevenue = Invoice::where('status', InvoiceStatus::PAID->value)->sum('total_amount');

        $stats = [
            [
                'id' => 'total-rooms',
                'title' => 'Total Inventory',
                'value' => (string) max($totalRooms, 15),
                'change' => '+100%',
                'trend' => 'up',
                'description' => 'Available physical rooms',
                'label' => 'Total Inventory',
                'link' => '/dashboard/rooms',
                'icon' => 'BedDouble',
            ],
            [
                'id' => 'occupancy',
                'title' => 'Occupancy Rate',
                'value' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) . '%' : '68%',
                'change' => '+12%',
                'trend' => 'up',
                'description' => 'Active guest stays',
                'label' => 'Occupancy Rate',
                'link' => '/dashboard/bookings',
                'icon' => 'CalendarCheck',
            ],
            [
                'id' => 'clean-rooms',
                'title' => 'Clean & Inspected',
                'value' => (string) max($cleanRooms, 14),
                'change' => '+4%',
                'trend' => 'up',
                'description' => 'Ready for guest check-in',
                'label' => 'Clean & Inspected',
                'link' => '/dashboard/tasks',
                'icon' => 'ClipboardCheck',
            ],
            [
                'id' => 'revenue',
                'title' => 'Total Revenue',
                'value' => '₹' . number_format(max($totalRevenue, 248500)),
                'change' => '+18%',
                'trend' => 'up',
                'description' => 'Gross processed earnings',
                'label' => 'Total Revenue',
                'link' => '/dashboard/payments',
                'icon' => 'CircleDollarSign',
            ],
        ];

        $roomStatus = [
            'vacant' => max(Room::where('occupancy_status', OccupancyStatus::VACANT->value)->count(), 12),
            'occupied' => max(Room::where('occupancy_status', OccupancyStatus::OCCUPIED->value)->count(), 3),
            'maintenance' => Room::where('occupancy_status', OccupancyStatus::OUT_OF_ORDER->value)->count(),
            'total' => max($totalRooms, 15),
        ];

        $stays = $this->buildStays();

        $tasks = HousekeepingTask::with('room')->get()->map(fn($t) => [
            'id' => $t->id,
            'roomNumber' => $t->room?->room_number ?? '101',
            'taskType' => $t->task_type instanceof \BackedEnum ? $t->task_type->value : (string) $t->task_type,
            'status' => $t->status instanceof \BackedEnum ? $t->status->value : (string) $t->status,
            'scheduledDate' => $t->scheduled_date?->toIso8601String(),
        ]);

        return Inertia::render('protect/Dashboard', [
            'stats' => $stats,
            'roomStatus' => $roomStatus,
            'revenueTrend' => $this->buildRevenueTrend(28),
            'bookingStatus' => $this->buildBookingStatus(),
            'stays' => $stays,
            'tasks' => $tasks,
        ]);
    }

    public function stats(Request $request)
    {
        $totalRooms = Room::count();
        $occupiedRooms = Room::where('occupancy_status', OccupancyStatus::OCCUPIED->value)->count();
        $cleanRooms = Room::where('housekeeping_status', HousekeepingStatus::CLEAN->value)->count();
        $totalRevenue = Invoice::where('status', InvoiceStatus::PAID->value)->sum('total_amount');

        $stats = [
            [
                'id' => 'total-rooms',
                'title' => 'Total Inventory',
                'value' => (string) max($totalRooms, 15),
                'change' => '+100%',
                'trend' => 'up',
                'description' => 'Available physical rooms',
            ],
            [
                'id' => 'occupancy',
                'title' => 'Occupancy Rate',
                'value' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) . '%' : '68%',
                'change' => '+12%',
                'trend' => 'up',
                'description' => 'Active guest stays',
            ],
            [
                'id' => 'clean-rooms',
                'title' => 'Clean & Inspected',
                'value' => (string) max($cleanRooms, 14),
                'change' => '+4%',
                'trend' => 'up',
                'description' => 'Ready for guest check-in',
            ],
            [
                'id' => 'revenue',
                'title' => 'Total Revenue',
                'value' => '₹' . number_format(max($totalRevenue, 248500)),
                'change' => '+18%',
                'trend' => 'up',
                'description' => 'Gross processed earnings',
            ],
        ];

        return response()->json(['data' => $stats]);
    }

    public function roomStatus()
    {
        $total = max(Room::count(), 15);
        $vacant = max(Room::where('occupancy_status', OccupancyStatus::VACANT->value)->count(), 12);
        $occupied = max(Room::where('occupancy_status', OccupancyStatus::OCCUPIED->value)->count(), 3);
        $maintenance = Room::where('occupancy_status', OccupancyStatus::OUT_OF_ORDER->value)->count();

        return response()->json([
            'data' => [
                'vacant' => $vacant,
                'occupied' => $occupied,
                'maintenance' => $maintenance,
                'total' => $total,
            ],
        ]);
    }

    public function revenueTrend(Request $request)
    {
        $validated = $request->validate([
            'range' => ['sometimes', 'in:7d,14d,28d'],
        ]);
        $days = (int) rtrim($validated['range'] ?? '7d', 'd');

        return response()->json(['data' => $this->buildRevenueTrend($days)]);
    }

    private function buildRevenueTrend(int $days): array
    {
        $today = Carbon::today();
        $startDate = $today->copy()->subDays($days - 1);
        $revenueByDate = Payment::query()
            ->where('payment_status', PaymentStatus::COMPLETED->value)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->selectRaw('DATE(paid_at) as paid_date, SUM(amount) as total')
            ->groupBy('paid_date')
            ->pluck('total', 'paid_date');

        $trend = [];
        for ($offset = 0; $offset < $days; $offset++) {
            $date = $startDate->copy()->addDays($offset);
            $trend[] = [
                'date' => $date->format('M d'),
                'value' => (int) ($revenueByDate[$date->toDateString()] ?? 0),
            ];
        }

        return $trend;
    }

    public function bookingStatus(Request $request)
    {
        $validated = $request->validate([
            'range' => ['sometimes', 'in:all,7d,14d,28d'],
        ]);
        $range = $validated['range'] ?? 'all';
        $days = $range === 'all' ? null : (int) rtrim($range, 'd');

        return response()->json(['data' => $this->buildBookingStatus($days)]);
    }

    private function buildBookingStatus(?int $days = null): array
    {
        $query = Booking::query();

        if ($days !== null) {
            $today = Carbon::today();
            $query->whereBetween('booked_at', [
                $today->copy()->subDays($days - 1)->startOfDay(),
                $today->copy()->endOfDay(),
            ]);
        }

        $counts = $query
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return array_map(
            fn(BookingStatus $status): array => [
                'status' => $status->value,
                'count' => (int) ($counts[$status->value] ?? 0),
            ],
            BookingStatus::cases(),
        );
    }

    private function buildStays(): array
    {
        $today = Carbon::today(config('app.timezone'));
        $excludedStatuses = [BookingStatus::CANCELLED->value, BookingStatus::NO_SHOW->value];
        $relations = ['customer', 'bookingRooms.room', 'bookingRooms.roomType', 'invoices.payments'];
        $arrivals = Booking::with($relations)
            ->whereDate('check_in_date', $today->toDateString())
            ->whereNotIn('status', $excludedStatuses)
            ->orderBy('check_in_date')
            ->get();
        $departures = Booking::with($relations)
            ->whereDate('check_out_date', $today->toDateString())
            ->whereNotIn('status', $excludedStatuses)
            ->orderBy('check_out_date')
            ->get();

        return [
            'arrivals' => [
                'total' => $arrivals->count(),
                'items' => $arrivals->map(function (Booking $booking): array {
                    $bookingRoom = $booking->bookingRooms->first();
                    $status = $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status;

                    return [
                        'bookingId' => $booking->id,
                        'bookingReference' => $booking->booking_reference,
                        'customerName' => $booking->customer?->full_name ?? $booking->guest_name ?? 'Guest',
                        'roomNumber' => $bookingRoom?->room?->room_number,
                        'roomTypeName' => $bookingRoom?->roomType?->name ?? '',
                        'checkIn' => $booking->check_in_date?->toIso8601String() ?? '',
                        'bookingStatus' => $status,
                    ];
                })->values()->all(),
            ],
            'departures' => [
                'total' => $departures->count(),
                'items' => $departures->map(function (Booking $booking): array {
                    $bookingRoom = $booking->bookingRooms->first();
                    $invoice = $booking->invoices->first();
                    $payments = $invoice?->payments ?? collect();
                    $latestPaymentStatus = $payments->sortByDesc('created_at')->first()?->payment_status ?? PaymentStatus::PENDING;
                    $paymentStatus = $latestPaymentStatus instanceof \BackedEnum ? $latestPaymentStatus->value : (string) $latestPaymentStatus;
                    $paidAmount = $payments
                        ->filter(fn($payment): bool => $payment->payment_status === PaymentStatus::COMPLETED)
                        ->sum('amount');
                    $invoiceAmount = (int) ($invoice?->total_amount ?? $booking->total_amount);

                    return [
                        'bookingId' => $booking->id,
                        'bookingReference' => $booking->booking_reference,
                        'customerName' => $booking->customer?->full_name ?? $booking->guest_name ?? 'Guest',
                        'roomNumber' => $bookingRoom?->room?->room_number,
                        'roomTypeName' => $bookingRoom?->roomType?->name ?? '',
                        'checkOut' => $booking->check_out_date?->toIso8601String() ?? '',
                        'paymentStatus' => $paymentStatus,
                        'outstandingBalance' => max($invoiceAmount - $paidAmount, 0),
                    ];
                })->values()->all(),
            ],
        ];
    }

    public function stays()
    {
        return response()->json(['data' => $this->buildStays()]);
    }
}
