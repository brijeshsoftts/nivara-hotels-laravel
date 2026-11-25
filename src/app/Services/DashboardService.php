<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\Category;
use App\Enums\HousekeepingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\OccupancyStatus;
use App\Enums\PaymentStatus;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\HousekeepingTask;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function stats(User $user): array
    {
        $role = $this->enumValue($user->role);

        if (in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value], true)) {
            return $this->managementStats();
        }

        if ($role === UserRole::STAFF->value && $user->category === Category::RECEPTIONIST->value) {
            return $this->receptionistStats();
        }

        if ($role === UserRole::STAFF->value && $user->category === Category::HOUSEKEEPER->value) {
            return $this->housekeeperStats($user);
        }

        if ($role === UserRole::CUSTOMER->value) {
            return $this->customerStats($user);
        }

        throw new AuthorizationException('Access denied');
    }

    public function nextBooking(User $user): ?array
    {
        $today = Carbon::today(config('app.timezone'));
        $booking = Booking::query()
            ->where('customer_id', $user->id)
            ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])
            ->where('check_in_date', '>=', $today)
            ->with('bookingRooms.roomType')
            ->orderBy('check_in_date')
            ->orderByDesc('created_at')
            ->first();

        if (! $booking) {
            return null;
        }

        return [
            'bookingReference' => $booking->booking_reference,
            'status' => $this->enumValue($booking->status),
            'checkInDate' => $booking->check_in_date?->toISOString(),
            'checkOutDate' => $booking->check_out_date?->toISOString(),
            'roomTypeName' => $booking->bookingRooms->first()?->roomType?->name,
        ];
    }

    public function revenueTrend(string $range): array
    {
        $days = (int) rtrim($range, 'd');
        $today = Carbon::today(config('app.timezone'));
        $startDate = $today->copy()->subDays($days - 1);
        $endDate = $today->copy()->addDay();
        $revenueByDate = Payment::query()
            ->where('payment_status', PaymentStatus::COMPLETED->value)
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $startDate)
            ->where('paid_at', '<', $endDate)
            ->selectRaw('DATE(paid_at) as paid_date, SUM(amount) as total')
            ->groupBy('paid_date')
            ->pluck('total', 'paid_date');

        $trend = [];
        for ($offset = 0; $offset < $days; $offset++) {
            $date = $startDate->copy()->addDays($offset);
            $trend[] = [
                'date' => (string) $date->day,
                'value' => (int) ($revenueByDate[$date->toDateString()] ?? 0),
            ];
        }

        return $trend;
    }

    public function stays(string $date = 'today', int $limit = 5): array
    {
        $selectedDate = $date === 'today'
            ? Carbon::today(config('app.timezone'))
            : Carbon::parse($date, config('app.timezone'))->startOfDay();
        $startDate = $selectedDate->copy()->startOfDay();
        $endDate = $startDate->copy()->addDay();
        $relations = ['customer', 'bookingRooms.room', 'bookingRooms.roomType', 'invoices.payments'];
        $arrivalQuery = Booking::query()
            ->where('check_in_date', '>=', $startDate)
            ->where('check_in_date', '<', $endDate)
            ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value]);
        $departureQuery = Booking::query()
            ->where('check_out_date', '>=', $startDate)
            ->where('check_out_date', '<', $endDate)
            ->where('status', BookingStatus::CHECKED_IN->value);
        $arrivalsTotal = (clone $arrivalQuery)->count();
        $departuresTotal = (clone $departureQuery)->count();
        $arrivals = (clone $arrivalQuery)
            ->with($relations)
            ->orderBy('check_in_date')
            ->limit($limit)
            ->get();
        $departures = (clone $departureQuery)
            ->with($relations)
            ->orderBy('check_out_date')
            ->limit($limit)
            ->get();

        return [
            'date' => $startDate->toDateString(),
            'arrivals' => [
                'items' => $arrivals->map(function (Booking $booking): array {
                    $bookingRoom = $booking->bookingRooms->first();

                    return [
                        'bookingId' => $booking->id,
                        'bookingReference' => $booking->booking_reference,
                        'customerName' => $booking->customer?->full_name ?? $booking->guest_name ?? 'Guest',
                        'roomTypeName' => $bookingRoom?->roomType?->name ?? 'Unassigned',
                        'roomNumber' => $bookingRoom?->room?->room_number,
                        'checkIn' => $booking->check_in_date?->toISOString() ?? '',
                        'bookingStatus' => $this->enumValue($booking->status),
                    ];
                })->values()->all(),
                'total' => $arrivalsTotal,
            ],
            'departures' => [
                'items' => $departures->map(function (Booking $booking): array {
                    $bookingRoom = $booking->bookingRooms->first();
                    $invoice = $booking->invoices->first();
                    $payments = $invoice?->payments ?? collect();
                    $latestPayment = $payments->sortByDesc('created_at')->first();
                    $paidAmount = $payments
                        ->filter(fn(Payment $payment): bool => $this->enumValue($payment->payment_status) === PaymentStatus::COMPLETED->value)
                        ->sum('amount');
                    $invoiceAmount = (int) ($invoice?->total_amount ?? $booking->total_amount);
                    $outstandingBalance = $invoice && in_array(
                        $this->enumValue($invoice->status),
                        [InvoiceStatus::UNPAID->value, InvoiceStatus::PARTIALLY_PAID->value],
                        true,
                    ) ? max($invoiceAmount - $paidAmount, 0) : 0;

                    return [
                        'bookingId' => $booking->id,
                        'bookingReference' => $booking->booking_reference,
                        'customerName' => $booking->customer?->full_name ?? $booking->guest_name ?? 'Guest',
                        'roomTypeName' => $bookingRoom?->roomType?->name ?? 'Unassigned',
                        'roomNumber' => $bookingRoom?->room?->room_number,
                        'checkOut' => $booking->check_out_date?->toISOString() ?? '',
                        'paymentStatus' => $latestPayment
                            ? $this->enumValue($latestPayment->payment_status)
                            : PaymentStatus::PENDING->value,
                        'outstandingBalance' => $outstandingBalance,
                    ];
                })->values()->all(),
                'total' => $departuresTotal,
            ],
        ];
    }

    public function bookingStatus(string $range = 'all'): array
    {
        $query = Booking::query();

        if ($range !== 'all') {
            $days = (int) rtrim($range, 'd');
            $today = Carbon::today(config('app.timezone'));
            $query->where('created_at', '>=', $today->copy()->subDays($days - 1))
                ->where('created_at', '<', $today->copy()->addDay());
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

    public function roomStatus(): array
    {
        $counts = Room::query()
            ->where('is_active', true)
            ->selectRaw('occupancy_status, COUNT(*) as count')
            ->groupBy('occupancy_status')
            ->pluck('count', 'occupancy_status');

        return array_map(
            fn(OccupancyStatus $status): array => [
                'status' => $status->value,
                'count' => (int) ($counts[$status->value] ?? 0),
            ],
            OccupancyStatus::cases(),
        );
    }

    public function tasks(User $user): array
    {
        if ($user->category !== Category::HOUSEKEEPER->value) {
            throw new AuthorizationException('Access denied');
        }

        $today = Carbon::today(config('app.timezone'));
        $tomorrow = $today->copy()->addDay();
        $tasks = HousekeepingTask::query()
            ->where('assigned_to', $user->id)
            ->where('scheduled_date', '>=', $today)
            ->where('scheduled_date', '<', $tomorrow)
            ->with(['room.issues' => fn($query) => $query
                ->whereIn('status', [IssueStatus::OPEN->value, IssueStatus::IN_PROGRESS->value])])
            ->orderBy('scheduled_date')
            ->get();
        $priorityOrder = array_flip(array_map(
            fn(IssuePriority $priority): string => $priority->value,
            IssuePriority::cases(),
        ));

        return $tasks->map(function (HousekeepingTask $task) use ($priorityOrder): array {
            $priority = $task->room?->issues
                ->sortByDesc(fn($issue): int => $priorityOrder[$this->enumValue($issue->priority)] ?? -1)
                ->first()?->priority;

            return [
                'taskId' => $task->id,
                'taskType' => $this->enumValue($task->task_type),
                'roomNumber' => $task->room?->room_number ?? '',
                'roomName' => $task->room?->name,
                'roomId' => $task->room?->id,
                'priority' => $priority ? $this->enumValue($priority) : null,
                'due' => $task->scheduled_date?->toISOString() ?? '',
                'status' => $this->enumValue($task->status),
            ];
        })->all();
    }

    public function paymentsRequiringAttention(User $user, array $statuses, int $limit = 5): array
    {
        if ($user->category !== Category::RECEPTIONIST->value) {
            throw new AuthorizationException('Access denied');
        }

        $query = Invoice::query()->whereIn('status', $statuses);
        $total = (clone $query)->count();
        $invoices = (clone $query)
            ->with([
                'booking.customer',
                'payments' => fn($paymentQuery) => $paymentQuery
                    ->where('payment_status', PaymentStatus::COMPLETED->value),
            ])
            ->orderBy('issued_at')
            ->limit($limit)
            ->get();

        return [
            'items' => $invoices->map(function (Invoice $invoice): array {
                $booking = $invoice->booking;
                $paidAmount = $invoice->payments->sum('amount');

                return [
                    'invoiceId' => $invoice->id,
                    'bookingId' => $booking?->id,
                    'bookingReference' => $booking?->booking_reference ?? '',
                    'guestName' => $booking?->customer?->full_name ?? $booking?->guest_name ?? 'Guest',
                    'total' => (int) $invoice->total_amount,
                    'due' => max(0, (int) $invoice->total_amount - (int) $paidAmount),
                    'status' => $this->enumValue($invoice->status),
                ];
            })->all(),
            'total' => $total,
        ];
    }

    private function managementStats(): array
    {
        $now = Carbon::now(config('app.timezone'));
        $monthStart = $now->copy()->startOfMonth();
        $nextMonth = $monthStart->copy()->addMonth();
        $monthlyRevenue = Payment::query()
            ->where('payment_status', PaymentStatus::COMPLETED->value)
            ->where('paid_at', '>=', $monthStart)
            ->where('paid_at', '<', $nextMonth)
            ->sum('amount');

        return [
            ['label' => 'Total rooms', 'value' => Room::count(), 'link' => '/dashboard/rooms', 'icon' => 'BedDouble'],
            ['label' => 'Total customers', 'value' => User::query()->where('role', UserRole::CUSTOMER->value)->whereNull('soft_deleted_at')->count(), 'link' => '/dashboard/customers', 'icon' => 'Users'],
            ['label' => 'Total staff', 'value' => Staff::query()->whereHas('user', fn(Builder $query) => $query->whereNull('soft_deleted_at'))->count(), 'link' => '/dashboard/staff', 'icon' => 'UserCheck'],
            ['label' => 'Revenue', 'value' => (int) $monthlyRevenue, 'link' => '/dashboard/payments', 'icon' => 'IndianRupee'],
        ];
    }

    private function receptionistStats(): array
    {
        $today = Carbon::today(config('app.timezone'));
        $tomorrow = $today->copy()->addDay();
        $arrivals = Booking::query()
            ->where('check_in_date', '>=', $today)
            ->where('check_in_date', '<', $tomorrow)
            ->whereIn('status', [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])
            ->count();
        $departures = Booking::query()
            ->where('check_out_date', '>=', $today)
            ->where('check_out_date', '<', $tomorrow)
            ->where('status', BookingStatus::CHECKED_IN->value)
            ->count();
        $availableRooms = Room::query()
            ->where('is_active', true)
            ->where('occupancy_status', OccupancyStatus::VACANT->value)
            ->where('housekeeping_status', HousekeepingStatus::CLEAN->value)
            ->count();
        $pendingPayments = Booking::query()
            ->whereHas('invoices', fn(Builder $query) => $query->whereIn('status', [
                InvoiceStatus::UNPAID->value,
                InvoiceStatus::PARTIALLY_PAID->value,
            ]))
            ->count();

        return [
            ['label' => 'Expected arrivals', 'value' => $arrivals, 'link' => '/dashboard/bookings', 'icon' => 'LogIn'],
            ['label' => 'Expected departures', 'value' => $departures, 'link' => '/dashboard/bookings', 'icon' => 'LogOut'],
            ['label' => 'Available rooms', 'value' => $availableRooms, 'link' => '/dashboard/rooms?occupancyStatus=VACANT&housekeepingStatus=CLEAN&isActive=true', 'icon' => 'BedDouble'],
            ['label' => 'Pending payments', 'value' => $pendingPayments, 'link' => '/dashboard/payments', 'icon' => 'CreditCard'],
        ];
    }

    private function housekeeperStats(User $user): array
    {
        $today = Carbon::today(config('app.timezone'));
        $tomorrow = $today->copy()->addDay();
        $pendingTasks = HousekeepingTask::query()
            ->where('assigned_to', $user->id)
            ->where('status', TaskStatus::PENDING->value)
            ->count();
        $inProgressTasks = HousekeepingTask::query()
            ->where('assigned_to', $user->id)
            ->where('status', TaskStatus::IN_PROGRESS->value)
            ->count();
        $completedToday = HousekeepingTask::query()
            ->where('assigned_to', $user->id)
            ->where('status', TaskStatus::COMPLETED->value)
            ->where('completed_at', '>=', $today)
            ->where('completed_at', '<', $tomorrow)
            ->count();
        $overdueTasks = HousekeepingTask::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value])
            ->where('scheduled_date', '<', $today)
            ->count();

        return [
            ['label' => 'My pending tasks', 'value' => $pendingTasks, 'link' => '/dashboard/tasks?status=PENDING', 'icon' => 'ClipboardList'],
            ['label' => 'In progress', 'value' => $inProgressTasks, 'link' => '/dashboard/tasks?status=IN_PROGRESS', 'icon' => 'LoaderCircle'],
            ['label' => 'Completed today', 'value' => $completedToday, 'link' => '/dashboard/tasks?status=COMPLETED', 'icon' => 'CircleCheck'],
            ['label' => 'Overdue tasks', 'value' => $overdueTasks, 'link' => '/dashboard/tasks?status=PENDING', 'icon' => 'CalendarOff'],
        ];
    }

    private function customerStats(User $user): array
    {
        $today = Carbon::today(config('app.timezone'));
        $outstandingStatuses = [InvoiceStatus::UNPAID->value, InvoiceStatus::PARTIALLY_PAID->value];
        $upcomingBookings = Booking::query()
            ->where('customer_id', $user->id)
            ->where('status', BookingStatus::CONFIRMED->value)
            ->where('check_in_date', '>=', $today)
            ->count();
        $currentStay = Booking::query()
            ->where('customer_id', $user->id)
            ->where('status', BookingStatus::CHECKED_IN->value)
            ->orderByDesc('checked_in_at')
            ->exists();
        $outstandingAmount = (int) Invoice::query()
            ->where('customer_id', $user->id)
            ->whereIn('status', $outstandingStatuses)
            ->sum('total_amount');
        $completedPayments = (int) Payment::query()
            ->where('customer_id', $user->id)
            ->where('payment_status', PaymentStatus::COMPLETED->value)
            ->whereHas('invoice', fn(Builder $query) => $query->whereIn('status', $outstandingStatuses))
            ->sum('amount');
        $pastStays = Booking::query()
            ->where('customer_id', $user->id)
            ->where('status', BookingStatus::CHECKED_OUT->value)
            ->count();

        return [
            ['label' => 'Upcoming bookings', 'value' => $upcomingBookings, 'link' => '/dashboard/bookings', 'icon' => 'CalendarCheck'],
            ['label' => 'Active stay', 'value' => $currentStay ? 1 : 0, 'link' => '/dashboard/bookings', 'icon' => 'KeyRound'],
            ['label' => 'Pending balance (INR)', 'value' => max(0, $outstandingAmount - $completedPayments), 'link' => '/dashboard/invoices', 'icon' => 'IndianRupee'],
            ['label' => 'Past bookings', 'value' => $pastStays, 'link' => '/dashboard/bookings', 'icon' => 'CalendarCheck'],
        ];
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? $value->value : (string) $value;
    }
}
