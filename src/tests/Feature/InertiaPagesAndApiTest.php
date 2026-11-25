<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\Category;
use App\Enums\HousekeepingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OccupancyStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\HousekeepingTask;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaPagesAndApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_render_successfully(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page->component('public/Home'));

        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page->component('public/About'));

        $this->get(route('rooms.index'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page->component('public/Rooms'));

        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page->component('public/Contact'));

        $this->get(route('rooms.show', ['slug' => 'deluxe-room']))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('public/RoomDetails')
                ->where('slug', 'deluxe-room')
                ->has('room.availability'));

        $this->get(route('checkout'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page->component('public/Checkout'));

        $this->get(route('rooms.book', ['slug' => 'deluxe-room']))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('public/Checkout')
                ->where('slug', 'deluxe-room')
                ->where('selectedRoom.slug', 'deluxe-room')
                ->has('selectedRoom.images'));
    }

    public function test_public_online_checkout_returns_booking_and_stores_only_token_hash(): void
    {
        $room = Room::where('occupancy_status', OccupancyStatus::VACANT->value)->firstOrFail();
        $checkoutToken = (string) Str::uuid();
        $checkInDate = now()->addDay()->toDateString();
        $checkOutDate = now()->addDays(3)->toDateString();

        $this->from(route('checkout'))->post(route('checkout.store'), [
            'checkoutToken' => $checkoutToken,
            'guestName' => 'Public Checkout Guest',
            'guestEmail' => 'public-checkout@example.com',
            'guestPhone' => '+91 9888877777',
            'roomId' => $room->id,
            'checkInDate' => $checkInDate,
            'checkOutDate' => $checkOutDate,
            'adults' => 2,
            'children' => 0,
            'guests' => [
                ['fullName' => 'Public Checkout Guest'],
                ['fullName' => 'Public Checkout Companion'],
            ],
            'paymentMethod' => 'ONLINE',
        ])->assertRedirect(route('checkout'));

        $booking = Booking::where('public_checkout_token_hash', hash('sha256', $checkoutToken))->firstOrFail();
        $this->assertSame(PaymentMethod::ONLINE, $booking->payment_method);
        $this->assertSame(BookingStatus::PENDING, $booking->status);
        $this->assertNotSame($checkoutToken, $booking->public_checkout_token_hash);
        $this->assertDatabaseHas('invoices', [
            'booking_id' => $booking->id,
            'status' => 'UNPAID',
        ]);

        $this->get(route('checkout'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->where('booking.id', $booking->id)
                ->where('booking.bookingReference', $booking->booking_reference)
                ->where('booking.checkoutToken', $checkoutToken));

        $nextCheckoutToken = (string) Str::uuid();
        $this->postJson(route('checkout.store'), [
            'checkoutToken' => $nextCheckoutToken,
            'guestName' => 'Next Stay Guest',
            'guestEmail' => 'next-stay@example.com',
            'guestPhone' => '+91 9888877777',
            'roomId' => $room->id,
            'checkInDate' => $checkOutDate,
            'checkOutDate' => now()->addDays(4)->toDateString(),
            'adults' => 1,
            'children' => 0,
            'guests' => [['fullName' => 'Next Stay Guest']],
            'paymentMethod' => 'ONLINE',
        ])->assertCreated();
    }

    public function test_public_online_checkout_rejects_same_day_stays(): void
    {
        $room = Room::where('occupancy_status', OccupancyStatus::VACANT->value)->firstOrFail();
        $checkoutToken = (string) Str::uuid();
        $sameDay = now()->addDay()->toDateString();

        $this->postJson(route('checkout.store'), [
            'checkoutToken' => $checkoutToken,
            'guestName' => 'Same Day Guest',
            'guestEmail' => 'same-day@example.com',
            'guestPhone' => '+91 9888877777',
            'roomId' => $room->id,
            'checkInDate' => $sameDay,
            'checkOutDate' => $sameDay,
            'adults' => 1,
            'children' => 0,
            'guests' => [['fullName' => 'Same Day Guest']],
            'paymentMethod' => 'ONLINE',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('checkOutDate');

        $this->assertDatabaseMissing('bookings', [
            'public_checkout_token_hash' => hash('sha256', $checkoutToken),
        ]);
    }

    public function test_public_api_endpoints_return_seeded_data(): void
    {
        $response = $this->getJson('/public/rooms');
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'basePrice', 'availableRooms', 'image', 'amenities'],
                ],
                'meta' => ['total', 'page', 'limit', 'totalPages'],
            ]);

        $this->getJson('/public/amenities')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'icon', 'category'],
                ],
            ]);

        $this->postJson('/public/contact', [
            'name' => 'Test Inquirer',
            'email' => 'test@example.com',
            'phone' => '+91 9999999999',
            'subject' => 'Group Booking',
            'message' => 'Interested in 5 rooms for next weekend.',
        ])->assertOk()
            ->assertJsonFragment(['message' => 'Your message has been sent successfully.']);
    }

    public function test_unauthenticated_user_cannot_access_protected_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('dashboard.users'))->assertRedirect(route('login'));
        $this->get(route('dashboard.rooms'))->assertRedirect(route('login'));
        $this->get(route('dashboard.staff'))->assertRedirect(route('login'));
        $this->get(route('dashboard.customers'))->assertRedirect(route('login'));
        $this->get(route('dashboard.bookings'))->assertRedirect(route('login'));
        $this->get(route('dashboard.tasks'))->assertRedirect(route('login'));
        $this->get(route('dashboard.issues'))->assertRedirect(route('login'));
        $this->get(route('dashboard.invoices'))->assertRedirect(route('login'));
        $this->get(route('dashboard.payments'))->assertRedirect(route('login'));
        $this->get(route('dashboard.inquiries'))->assertRedirect(route('login'));
        $this->get(route('dashboard.audit-logs'))->assertRedirect(route('login'));
        $this->get(route('dashboard.profile'))->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_access_all_dashboard_pages(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();

        $routesToComponents = [
            'dashboard' => 'protect/Dashboard',
            'dashboard.users' => 'protect/users',
            'dashboard.staff' => 'protect/staff/index',
            'dashboard.customers' => 'protect/customers/index',
            'dashboard.rooms' => 'protect/rooms/index',
            'dashboard.room-types' => 'protect/rooms/index',
            'dashboard.amenities' => 'protect/rooms/index',
            'dashboard.bookings' => 'protect/bookings/index',
            'dashboard.bookings.new' => 'protect/bookings/new',
            'dashboard.tasks' => 'protect/tasks/index',
            'dashboard.issues' => 'protect/issues/index',
            'dashboard.invoices' => 'protect/invoices/index',
            'dashboard.payments' => 'protect/payments/index',
            'dashboard.inquiries' => 'protect/inquiries/index',
            'dashboard.audit-logs' => 'protect/audit-logs/index',
            'dashboard.profile' => 'protect/profile/index',
        ];

        foreach ($routesToComponents as $routeName => $componentName) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertInertia(fn(Assert $page) => $page->component(
                    $componentName,
                    $routeName !== 'dashboard.users'
                ));
        }
    }

    public function test_dashboard_revenue_trend_uses_completed_payments_and_chart_shape(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
        $customer = Customer::with('user')->firstOrFail();
        $paidAt = now()->subDays(2)->setTime(15, 0);
        $booking = Booking::create([
            'booking_reference' => 'BK-DASH-REVENUE-1',
            'customer_id' => $customer->user_id,
            'guest_name' => 'Revenue Trend Guest',
            'guest_email' => 'revenue-trend@example.com',
            'guest_phone' => '+91 9876543210',
            'check_in_date' => now(),
            'check_out_date' => now()->addDays(2),
            'total_guests' => 1,
            'total_amount' => 2000,
            'payment_method' => PaymentMethod::CASH->value,
            'status' => BookingStatus::CONFIRMED->value,
            'booked_at' => now(),
        ]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-DASH-REVENUE-1',
            'booking_id' => $booking->id,
            'customer_id' => $customer->user_id,
            'subtotal' => 2000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 2000,
            'status' => 'UNPAID',
            'issued_at' => now(),
        ]);

        foreach (
            [
                ['COMPLETED', 1200],
                ['COMPLETED', 300],
                ['PENDING', 900],
            ] as $index => [$status, $amount]
        ) {
            Payment::create([
                'payment_reference' => 'PAY-DASH-REVENUE-' . $index,
                'invoice_id' => $invoice->id,
                'booking_id' => $booking->id,
                'customer_id' => $customer->user_id,
                'amount' => $amount,
                'currency' => 'INR',
                'payment_method' => PaymentMethod::CASH->value,
                'payment_status' => PaymentStatus::from($status)->value,
                'paid_at' => $paidAt,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->where('revenueTrend.25.date', $paidAt->format('M d'))
                ->where('revenueTrend.25.value', 1500)
                ->missing('revenueTrend.25.revenue'));

        $this->actingAs($admin)
            ->getJson(route('dashboard.revenue-trend', ['range' => '7d']))
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('data.4.value', 1500);
    }

    public function test_dashboard_booking_status_filter_uses_booking_date_range(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
        $customer = Customer::with('user')->firstOrFail();
        $createBooking = function (string $reference, BookingStatus $status, Carbon $bookedAt) use ($customer): Booking {
            return Booking::create([
                'booking_reference' => $reference,
                'customer_id' => $customer->user_id,
                'guest_name' => 'Booking Status Guest',
                'guest_email' => 'booking-status@example.com',
                'guest_phone' => '+91 9876543210',
                'check_in_date' => now(),
                'check_out_date' => now()->addDays(2),
                'total_guests' => 1,
                'total_amount' => 2000,
                'payment_method' => PaymentMethod::CASH->value,
                'status' => $status->value,
                'booked_at' => $bookedAt,
            ]);
        };

        $beforeSevenDays = collect(
            $this->actingAs($admin)
                ->getJson(route('dashboard.booking-status', ['range' => '7d']))
                ->assertOk()
                ->json('data'),
        )->mapWithKeys(fn(array $item): array => [$item['status'] => $item['count']]);
        $beforeAllTime = collect(
            $this->getJson(route('dashboard.booking-status', ['range' => 'all']))
                ->assertOk()
                ->json('data'),
        )->mapWithKeys(fn(array $item): array => [$item['status'] => $item['count']]);

        $createBooking('BK-DASH-STATUS-RECENT-1', BookingStatus::CONFIRMED, now()->subDays(2));
        $createBooking('BK-DASH-STATUS-RECENT-2', BookingStatus::PENDING, now()->subDays(6));
        $createBooking('BK-DASH-STATUS-OLD', BookingStatus::CANCELLED, now()->subDays(8));

        $afterSevenDays = collect(
            $this->getJson(route('dashboard.booking-status', ['range' => '7d']))
                ->assertOk()
                ->json('data'),
        )->mapWithKeys(fn(array $item): array => [$item['status'] => $item['count']]);
        $afterAllTime = collect(
            $this->getJson(route('dashboard.booking-status', ['range' => 'all']))
                ->assertOk()
                ->json('data'),
        )->mapWithKeys(fn(array $item): array => [$item['status'] => $item['count']]);

        $this->assertSame($beforeSevenDays['CONFIRMED'] + 1, $afterSevenDays['CONFIRMED']);
        $this->assertSame($beforeSevenDays['PENDING'] + 1, $afterSevenDays['PENDING']);
        $this->assertSame($beforeSevenDays['CANCELLED'], $afterSevenDays['CANCELLED']);
        $this->assertSame($beforeAllTime['CANCELLED'] + 1, $afterAllTime['CANCELLED']);
    }

    public function test_dashboard_stays_use_real_booking_ids_that_open_in_details(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
        $customer = Customer::with('user')->firstOrFail();
        $today = Carbon::today();
        $createBooking = function (string $reference, BookingStatus $status, Carbon $checkIn, Carbon $checkOut) use ($customer): Booking {
            return Booking::create([
                'booking_reference' => $reference,
                'customer_id' => $customer->user_id,
                'guest_name' => 'Dashboard Stay Guest',
                'guest_email' => 'dashboard-stay@example.com',
                'guest_phone' => '+91 9876543210',
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'total_guests' => 1,
                'total_amount' => 2000,
                'payment_method' => PaymentMethod::CASH->value,
                'status' => $status->value,
                'booked_at' => now(),
            ]);
        };

        $arrival = $createBooking(
            'BK-DASH-ARRIVAL-1',
            BookingStatus::CONFIRMED,
            $today->copy()->setTime(15, 0),
            $today->copy()->addDay()->setTime(11, 0),
        );
        $departure = $createBooking(
            'BK-DASH-DEPARTURE-1',
            BookingStatus::CHECKED_IN,
            $today->copy()->subDays(2)->setTime(15, 0),
            $today->copy()->setTime(11, 0),
        );

        $this->actingAs($admin)
            ->getJson(route('dashboard.stays'))
            ->assertOk()
            ->assertJsonFragment(['bookingId' => $arrival->id])
            ->assertJsonFragment(['bookingId' => $departure->id]);

        $this->getJson("/bookings/{$arrival->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $arrival->id);
        $this->getJson("/bookings/{$departure->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $departure->id);
    }

    public function test_confirmed_paid_booking_can_only_check_in_on_its_start_date(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
        $customer = Customer::with('user')->firstOrFail();
        $room = Room::query()
            ->where('is_active', true)
            ->where('occupancy_status', OccupancyStatus::VACANT->value)
            ->where('housekeeping_status', HousekeepingStatus::CLEAN->value)
            ->whereDoesntHave('housekeepingTasks', fn($query) => $query->whereIn('status', [
                TaskStatus::PENDING->value,
                TaskStatus::IN_PROGRESS->value,
            ]))
            ->firstOrFail();
        $booking = Booking::create([
            'booking_reference' => 'BK-DASH-CHECKIN-1',
            'customer_id' => $customer->user_id,
            'guest_name' => 'Check-in Eligibility Guest',
            'guest_email' => 'checkin-eligibility@example.com',
            'guest_phone' => '+91 9876543210',
            'check_in_date' => Carbon::today(config('app.timezone'))->addDay()->setTime(15, 0),
            'check_out_date' => Carbon::today(config('app.timezone'))->addDays(2)->setTime(11, 0),
            'total_guests' => 1,
            'total_amount' => 2000,
            'payment_method' => PaymentMethod::CASH->value,
            'status' => BookingStatus::CONFIRMED->value,
            'booked_at' => now(),
        ]);
        BookingRoom::create([
            'booking_id' => $booking->id,
            'room_type_id' => $room->room_type_id,
            'assigned_room_id' => $room->id,
            'price_per_night' => 2000,
        ]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-DASH-CHECKIN-1',
            'booking_id' => $booking->id,
            'customer_id' => $customer->user_id,
            'subtotal' => 2000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 2000,
            'status' => InvoiceStatus::PAID->value,
            'issued_at' => now(),
        ]);
        Payment::create([
            'payment_reference' => 'PAY-DASH-CHECKIN-1',
            'invoice_id' => $invoice->id,
            'booking_id' => $booking->id,
            'customer_id' => $customer->user_id,
            'amount' => 2000,
            'currency' => 'INR',
            'payment_method' => PaymentMethod::CASH->value,
            'payment_status' => PaymentStatus::COMPLETED->value,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patchJson("/bookings/{$booking->id}/status", ['status' => BookingStatus::CHECKED_IN->value])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This booking is not eligible for check-in today.');

        $booking->update([
            'check_in_date' => Carbon::today(config('app.timezone'))->setTime(15, 0),
        ]);
        $room->update(['occupancy_status' => OccupancyStatus::OCCUPIED->value]);

        $this->getJson('/bookings?search=' . $booking->booking_reference)
            ->assertOk()
            ->assertJsonPath('data.0.canCheckIn', true);

        $this->patchJson("/bookings/{$booking->id}/status", ['status' => BookingStatus::CHECKED_IN->value])
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::CHECKED_IN->value);

        $this->assertSame(OccupancyStatus::OCCUPIED, $room->fresh()->occupancy_status);
    }

    public function test_users_page_displays_managed_account_stats(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
        $managedRoles = [UserRole::MANAGER->value, UserRole::STAFF->value];
        $baselineTotal = User::whereIn('role', $managedRoles)->whereNull('soft_deleted_at')->count();
        $baselineManagers = User::where('role', UserRole::MANAGER->value)->whereNull('soft_deleted_at')->count();
        $baselineStaff = User::where('role', UserRole::STAFF->value)->whereNull('soft_deleted_at')->count();
        $baselineInactive = User::whereIn('role', $managedRoles)
            ->where('is_active', false)
            ->whereNull('soft_deleted_at')
            ->count();

        User::factory()->create(['role' => UserRole::MANAGER->value]);
        User::factory()->create(['role' => UserRole::STAFF->value, 'is_active' => false]);
        User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        User::factory()->create([
            'role' => UserRole::MANAGER->value,
            'soft_deleted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.users'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('protect/users', false)
                ->has('stats', 4)
                ->where('stats.0.label', 'Total Accounts')
                ->where('stats.0.value', number_format($baselineTotal + 2))
                ->where('stats.1.label', 'Managers')
                ->where('stats.1.value', number_format($baselineManagers + 1))
                ->where('stats.2.label', 'Staff Accounts')
                ->where('stats.2.value', number_format($baselineStaff + 1))
                ->where('stats.3.label', 'Inactive Accounts')
                ->where('stats.3.value', number_format($baselineInactive + 1))
                ->where('users.meta.total', $baselineTotal + 2));
    }

    public function test_staff_page_displays_live_stats_and_excludes_soft_deleted_staff(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
        $visibleStaff = Staff::whereHas('user', fn($query) => $query->whereNull('soft_deleted_at'));
        $baselineTotal = (clone $visibleStaff)->count();
        $baselineActive = (clone $visibleStaff)->whereHas('user', fn($query) => $query->where('is_active', true))->count();
        $baselineInactive = (clone $visibleStaff)->whereHas('user', fn($query) => $query->where('is_active', false))->count();

        $createStaff = function (Category $category, bool $isActive = true, bool $isDeleted = false): void {
            $user = User::factory()->create([
                'role' => UserRole::STAFF->value,
                'is_active' => $isActive,
                'soft_deleted_at' => $isDeleted ? now() : null,
            ]);

            Staff::create([
                'user_id' => $user->id,
                'father_name' => 'Test Father',
                'mother_name' => 'Test Mother',
                'id_proof_number' => 'TEST-' . $user->id,
                'qualification' => 'Test qualification',
                'experience' => '1 year',
                'category' => $category->value,
                'emergency_contact' => '+10000000000',
                'address' => 'Test address',
            ]);
        };

        $createStaff(Category::HOUSEKEEPER);
        $createStaff(Category::HOUSEKEEPER, false);
        $createStaff(Category::SECURITY_GUARD, false, true);
        $housekeeperTotal = Staff::where('category', Category::HOUSEKEEPER->value)
            ->whereHas('user', fn($query) => $query->whereNull('soft_deleted_at'))
            ->count();

        $this->actingAs($admin)
            ->get(route('dashboard.staff'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('protect/staff/index')
                ->has('stats', 4)
                ->where('stats.0.label', 'Total Staff')
                ->where('stats.0.value', number_format($baselineTotal + 2))
                ->where('stats.1.label', 'Active')
                ->where('stats.1.value', number_format($baselineActive + 1))
                ->where('stats.2.label', 'Inactive')
                ->where('stats.2.value', number_format($baselineInactive + 1))
                ->where('stats.3.label', 'Housekeepers')
                ->where('stats.3.value', '3')
                ->where('stats.3.link', '/dashboard/staff?category=HOUSEKEEPER')
                ->where('staff.meta.total', $baselineTotal + 2));

        $this->actingAs($admin)
            ->get(route('dashboard.staff', ['isActive' => '0']))
            ->assertInertia(fn(Assert $page) => $page
                ->where('staff.meta.total', $baselineInactive + 1));

        $this->actingAs($admin)
            ->get(route('dashboard.staff', ['category' => Category::HOUSEKEEPER->value]))
            ->assertInertia(fn(Assert $page) => $page
                ->where('staff.meta.total', $housekeeperTotal));

        $this->actingAs($admin)
            ->getJson('/staff/stats')
            ->assertOk()
            ->assertJsonPath('data.0.value', number_format($baselineTotal + 2))
            ->assertJsonPath('data.1.value', number_format($baselineActive + 1))
            ->assertJsonPath('data.2.value', number_format($baselineInactive + 1))
            ->assertJsonPath('data.3.label', 'Housekeepers')
            ->assertJsonPath('data.3.value', '3');
    }

    public function test_tasks_page_returns_live_stats_and_filterable_task_items(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
        $task = HousekeepingTask::with(['room', 'assignedTo'])->firstOrFail();
        $pending = HousekeepingTask::where('status', TaskStatus::PENDING->value)->count();
        $inProgress = HousekeepingTask::where('status', TaskStatus::IN_PROGRESS->value)->count();
        $completed = HousekeepingTask::where('status', TaskStatus::COMPLETED->value)->count();

        $this->actingAs($admin)
            ->get(route('dashboard.tasks'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('protect/tasks/index')
                ->has('stats', 4)
                ->where('stats.0.value', number_format($pending + $inProgress + $completed))
                ->where('stats.1.value', number_format($pending))
                ->where('stats.2.value', number_format($inProgress))
                ->where('stats.3.value', number_format($completed))
                ->where('stats.1.link', '/dashboard/tasks?status=PENDING')
                ->where('tasks.data.0.id', $task->id)
                ->where('tasks.data.0.room.id', $task->room_id)
                ->where('tasks.data.0.assignee.id', $task->assigned_to)
                ->has('tasks.data.0.startedAt')
                ->has('tasks.data.0.completedAt'));

        $this->actingAs($admin)
            ->getJson('/tasks?status=PENDING&taskType=CLEANING')
            ->assertOk()
            ->assertJsonPath('meta.total', HousekeepingTask::where('status', TaskStatus::PENDING->value)
                ->where('task_type', 'CLEANING')
                ->count())
            ->assertJsonPath('data.0.assignee.id', $task->assigned_to);

        $this->actingAs($admin)
            ->getJson('/tasks/stats')
            ->assertOk()
            ->assertJsonPath('data.0.value', number_format($pending + $inProgress + $completed));
    }

    public function test_customer_stats_filter_table_and_include_complete_customer_rows(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
        $customerUsers = User::where('role', UserRole::CUSTOMER->value)
            ->whereNull('soft_deleted_at');
        $activeBookingStatuses = [
            BookingStatus::PENDING->value,
            BookingStatus::CONFIRMED->value,
            BookingStatus::CHECKED_IN->value,
        ];
        $total = (clone $customerUsers)->count();
        $active = (clone $customerUsers)->where('is_active', true)->count();
        $withActiveBooking = (clone $customerUsers)
            ->whereHas('bookings', fn($query) => $query->whereIn('status', $activeBookingStatuses))
            ->count();
        $newCustomers = (clone $customerUsers)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $this->actingAs($admin)
            ->get(route('dashboard.customers'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('protect/customers/index')
                ->has('stats', 4)
                ->where('stats.0.label', 'Total Customers')
                ->where('stats.0.value', number_format($total))
                ->where('stats.0.link', '/dashboard/customers')
                ->where('stats.1.value', number_format($active))
                ->where('stats.1.link', '/dashboard/customers?isActive=1')
                ->where('stats.2.value', number_format($withActiveBooking))
                ->where('stats.2.link', '/dashboard/customers?hasActiveBooking=1')
                ->where('stats.3.value', number_format($newCustomers))
                ->where('stats.3.link', '/dashboard/customers?createdWithinDays=30')
                ->has('customers.data.0.activeBooking')
                ->has('customers.data.0.totalSpent')
                ->has('customers.data.0.lastLogin')
                ->has('customers.data.0.isActive')
                ->has('customers.data.0.profile'));

        $this->actingAs($admin)
            ->get(route('dashboard.customers', ['isActive' => '1']))
            ->assertInertia(fn(Assert $page) => $page
                ->where('customers.meta.total', Customer::whereHas('user', fn($query) => $query
                    ->where('role', UserRole::CUSTOMER->value)
                    ->whereNull('soft_deleted_at')
                    ->where('is_active', true))
                    ->count()));

        $this->actingAs($admin)
            ->get(route('dashboard.customers', ['hasActiveBooking' => '1']))
            ->assertInertia(fn(Assert $page) => $page
                ->where('customers.meta.total', $withActiveBooking));

        $this->actingAs($admin)
            ->get(route('dashboard.customers', ['createdWithinDays' => '30']))
            ->assertInertia(fn(Assert $page) => $page
                ->where('customers.meta.total', Customer::whereHas('user', fn($query) => $query
                    ->where('role', UserRole::CUSTOMER->value)
                    ->whereNull('soft_deleted_at')
                    ->where('created_at', '>=', now()->subDays(30)))
                    ->count()));
    }

    public function test_dashboard_stats_and_profile_api(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/dashboard/stats')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'value', 'change', 'trend'],
                ],
            ]);

        $this->actingAs($admin)
            ->getJson('/profile/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@nivarahotels.com')
            ->assertJsonPath('data.role', 'ADMIN');
    }
}
