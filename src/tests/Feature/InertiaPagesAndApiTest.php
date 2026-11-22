<?php

namespace Tests\Feature;

use App\Enums\UserRole;
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
            ->assertInertia(fn (Assert $page) => $page->component('welcome'));

        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('public/about'));

        $this->get(route('rooms.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('public/rooms'));

        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('public/contact'));

        $this->get(route('rooms.show', ['slug' => 'deluxe-room']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('public/room-details')->where('slug', 'deluxe-room'));

        $this->get(route('checkout'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('public/checkout'));
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
            'dashboard' => 'protect/dashboard/index',
            'dashboard.users' => 'protect/users/index',
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
                ->assertInertia(fn (Assert $page) => $page->component($componentName));
        }
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
