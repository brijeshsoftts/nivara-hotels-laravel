<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FortifyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fortify_serves_the_custom_authentication_views(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee(route('password.request'));

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create your account');
    }

    public function test_registration_creates_a_customer_and_redirects_to_the_shared_dashboard(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Taylor Guest',
            'email' => 'taylor@example.com',
            'phone' => '+14155550123',
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!',
            'role' => UserRole::ADMIN->value,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'taylor@example.com')->firstOrFail();
        $this->assertSame('Taylor Guest', $user->full_name);
        $this->assertSame('+14155550123', $user->phone);
        $this->assertSame(UserRole::CUSTOMER->value, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('ValidPassword123!', $user->password));
        $this->assertTrue(Hash::check('ValidPassword123!', $user->password_hash));
    }

    public function test_admin_can_login_through_the_shared_email_login_route(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_malformed_and_oversized_email_addresses(): void
    {
        foreach (['not-an-email', str_repeat('a', 250).'@x.com'] as $email) {
            $this->from(route('login'))->post(route('login.store'), [
                'email' => $email,
                'password' => 'password',
            ])->assertSessionHasErrors('email');
        }

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
