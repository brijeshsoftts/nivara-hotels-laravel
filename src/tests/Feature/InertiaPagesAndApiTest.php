<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\RoomType;
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
    }

    public function test_authenticated_admin_can_access_dashboard_and_pages(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('protect/dashboard/index'));

        $this->actingAs($admin)
            ->get(route('dashboard.users'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('protect/users/index'));

        $this->actingAs($admin)
            ->get(route('dashboard.rooms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('protect/rooms/index'));

        $this->actingAs($admin)
            ->get(route('dashboard.bookings'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('protect/bookings/index'));
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
