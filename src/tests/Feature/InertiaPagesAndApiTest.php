<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\Category;
use App\Enums\BookingStatus;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertInertia(fn(Assert $page) => $page->component('public/room-details')->where('slug', 'deluxe-room'));

        $this->get(route('checkout'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page->component('public/checkout'));
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
