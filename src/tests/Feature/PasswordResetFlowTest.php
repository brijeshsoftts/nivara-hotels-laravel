<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_forgot_password_view_renders(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertOk();
        $response->assertSee('Forgot password?');
        $response->assertSee('Send password reset link');
    }

    public function test_reset_password_view_renders_with_token(): void
    {
        $user = User::firstOrFail();
        $token = Password::broker()->createToken($user);

        $response = $this->get("/reset-password/{$token}?email=" . urlencode($user->email));
        $response->assertOk();
        $response->assertSee('Create new password');
        $response->assertSee('Reset password');
    }
}

