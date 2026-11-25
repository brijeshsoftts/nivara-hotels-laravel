<?php

namespace Tests\Feature;

use App\Enums\AmenityCategory;
use App\Enums\UserRole;
use App\Models\Amenity;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_audit_log_list_returns_the_fields_expected_by_the_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $log = AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'UPDATE',
            'entity_type' => 'BOOKING',
            'entity_id' => 'booking-123',
            'old_values' => ['status' => 'PENDING'],
            'new_values' => ['status' => 'CONFIRMED'],
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.audit-logs', ['search' => 'booking', 'entityType' => 'BOOKING']))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->component('protect/audit-logs/index')
                ->where('auditLogs.data.0.id', $log->id)
                ->where('auditLogs.data.0.entityType', 'BOOKING')
                ->where('auditLogs.data.0.oldValues.status', 'PENDING')
                ->where('auditLogs.data.0.newValues.status', 'CONFIRMED')
                ->where('auditLogs.data.0.user.fullName', $admin->full_name)
                ->where('auditLogs.meta.total', 1)
                ->where('filters.entityType', 'BOOKING'));
    }

    public function test_non_admin_users_cannot_access_audit_logs(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER->value]);

        $this->actingAs($manager)
            ->get(route('dashboard.audit-logs'))
            ->assertForbidden();
    }

    public function test_business_model_mutations_are_recorded_without_sensitive_values(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $this->assertDatabaseCount('audit_logs', 0);

        $createdAmenityId = $this->actingAs($admin)
            ->postJson(route('amenities.store'), [
                'name' => 'Logged amenity',
                'icon' => 'sparkles',
                'category' => AmenityCategory::COMFORT->value,
                'description' => 'This description should not be audited.',
            ])
            ->assertOk()
            ->json('data.id');

        $createdLog = AuditLog::query()->where('entity_id', $createdAmenityId)->firstOrFail();
        $createdValues = $createdLog->new_values;
        unset($createdValues['id']);
        $this->assertSame([
            'category' => AmenityCategory::COMFORT->value,
            'is_active' => true,
            'name' => 'Logged amenity',
        ], $createdValues);

        $amenity = Amenity::query()->create([
            'name' => 'Original amenity',
            'icon' => 'sparkles',
            'category' => AmenityCategory::COMFORT->value,
            'description' => 'Private description is not audited.',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patchJson(route('amenities.update', $amenity->id), ['name' => 'Updated amenity'])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'UPDATE',
            'entity_type' => 'AMENITY',
            'entity_id' => $amenity->id,
            'old_values' => json_encode(['name' => 'Original amenity']),
            'new_values' => json_encode(['name' => 'Updated amenity']),
        ]);

        $this->actingAs($admin)
            ->deleteJson(route('amenities.destroy', $amenity->id))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'DELETE',
            'entity_type' => 'AMENITY',
            'entity_id' => $amenity->id,
        ]);
    }
}
