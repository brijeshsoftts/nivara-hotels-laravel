<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_receives_the_complete_profile_details_shape(): void
    {
        $user = User::factory()->create(['full_name' => 'Profile Guest']);

        $this->actingAs($user)
            ->get(route('dashboard.profile'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('protect/profile/index')
                ->where('profile.data.fullName', 'Profile Guest')
                ->where('details.data.fullName', 'Profile Guest')
                ->where('details.data.email', $user->email)
                ->has('details.data.createdAt'));

        $this->getJson('/profile/details')
            ->assertOk()
            ->assertJsonPath('data.fullName', 'Profile Guest')
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_profile_and_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/profile/me', [
            'fullName' => 'Updated Guest',
            'email' => 'updated@example.com',
            'phone' => '+14155550123',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Guest',
            'full_name' => 'Updated Guest',
            'email' => 'updated@example.com',
            'phone' => '+14155550123',
        ]);

        User::factory()->create(['email' => 'taken@example.com']);
        $this->patchJson('/profile/me', [
            'email' => 'TAKEN@example.com',
        ])->assertUnprocessable();

        $this->patchJson('/profile/change-password', [
            'oldPassword' => 'password',
            'newPassword' => 'new-password',
        ])->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertTrue(Hash::check('new-password', $user->password_hash));
    }

    public function test_avatar_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/dashboard/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertRedirect();

        $user->refresh();
        $avatar = $user->avatar;
        $this->assertNotNull($avatar);
        $this->assertDatabaseHas('images', ['id' => $avatar->id]);
        Storage::disk('public')->assertExists('avatars/' . basename(parse_url($avatar->url, PHP_URL_PATH)));

        $this->delete('/dashboard/profile/avatar')->assertRedirect();

        $this->assertNull($user->fresh()->profile_id);
        $this->assertDatabaseMissing('images', ['id' => $avatar->id]);
        Storage::disk('public')->assertMissing('avatars/' . basename(parse_url($avatar->url, PHP_URL_PATH)));
    }
}
