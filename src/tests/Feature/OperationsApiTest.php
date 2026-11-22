<?php

namespace Tests\Feature;

use App\Enums\HousekeepingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OccupancyStatus;
use App\Enums\UserRole;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\HousekeepingTask;
use App\Models\Invoice;
use App\Models\Issue;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
    }

    public function test_users_crud_operations(): void
    {
        // 1. List
        $res = $this->actingAs($this->admin)->getJson('/users');
        $res->assertOk()->assertJsonStructure(['data', 'meta' => ['total', 'page', 'limit']]);

        // 2. Create
        $createRes = $this->actingAs($this->admin)->postJson('/users', [
            'fullName' => 'New Hotel Staff',
            'email' => 'newstaff@nivara.test',
            'phone' => '+91 9123456789',
            'role' => 'STAFF',
            'password' => 'SecretPass123!',
        ]);
        $createRes->assertOk()->assertJsonFragment(['message' => 'User created successfully.']);
        $newUserId = $createRes->json('data.id');

        // 3. Find one
        $this->actingAs($this->admin)->getJson("/users/{$newUserId}")
            ->assertOk()
            ->assertJsonPath('data.email', 'newstaff@nivara.test');

        // 4. Update
        $this->actingAs($this->admin)->patchJson("/users/{$newUserId}", [
            'fullName' => 'Updated Staff Name',
            'phone' => '+91 9999900000',
        ])->assertOk();

        // 5. Toggle status
        $this->actingAs($this->admin)->patchJson("/users/{$newUserId}/status", [
            'isActive' => false,
        ])->assertOk();
        $this->assertFalse(User::find($newUserId)->is_active);

        // 6. Delete
        $this->actingAs($this->admin)->deleteJson("/users/{$newUserId}")->assertOk();
        $this->assertNull(User::find($newUserId));
    }

    public function test_staff_crud_operations(): void
    {
        $this->actingAs($this->admin)->getJson('/staff')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $res = $this->actingAs($this->admin)->postJson('/staff', [
            'fullName' => 'Front Desk Executive',
            'email' => 'frontdesk@nivara.test',
            'phone' => '+91 9888877777',
            'category' => 'RECEPTIONIST',
            'qualification' => 'Hotel Management Degree',
            'experience' => '3 years',
            'emergencyContact' => '+91 9888800000',
            'address' => 'Nivara Staff Quarters',
            'idProofNumber' => 'ABCDE1234F',
            'fatherName' => 'Father Executive',
            'motherName' => 'Mother Executive',
        ]);
        $res->assertOk();
        $staffId = $res->json('data.id');

        $this->actingAs($this->admin)->getJson("/staff/{$staffId}")
            ->assertOk()
            ->assertJsonPath('data.category', 'RECEPTIONIST');

        $this->actingAs($this->admin)->patchJson("/staff/{$staffId}", [
            'experience' => '4 years',
        ])->assertOk();

        $this->actingAs($this->admin)->deleteJson("/staff/{$staffId}")->assertOk();
    }

    public function test_customers_search_and_crud(): void
    {
        $cust = Customer::with('user')->firstOrFail();

        // Search
        $this->actingAs($this->admin)->getJson('/customers/search?q=' . substr($cust->user->email, 0, 5))
            ->assertOk()
            ->assertJsonFragment(['email' => $cust->user->email]);

        // List
        $this->actingAs($this->admin)->getJson('/customers')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        // Create
        $res = $this->actingAs($this->admin)->postJson('/customers', [
            'fullName' => 'VIP Traveler',
            'email' => 'vip.guest@example.com',
            'phone' => '+91 9876543210',
            'address' => 'Mumbai, India',
            'idProofNumber' => 'PASSPORT-X123',
        ]);
        $res->assertOk();
        $customerId = $res->json('data.id');

        // Find details
        $this->actingAs($this->admin)->getJson("/customers/{$customerId}")
            ->assertOk()
            ->assertJsonPath('data.fullName', 'VIP Traveler');
    }

    public function test_physical_rooms_and_out_of_order_lifecycle(): void
    {
        $roomType = RoomType::firstOrFail();

        // Create
        $res = $this->actingAs($this->admin)->postJson('/rooms', [
            'roomNumber' => '501',
            'name' => 'Presidential Penthouse',
            'floor' => 5,
            'roomTypeId' => $roomType->id,
            'description' => 'Top floor suite',
        ]);
        $res->assertOk();
        $roomId = $res->json('data.id');

        // Find
        $this->actingAs($this->admin)->getJson("/rooms/{$roomId}")
            ->assertOk()
            ->assertJsonPath('data.roomNumber', '501');

        // Put Out of Order
        $this->actingAs($this->admin)->patchJson("/rooms/{$roomId}", [
            'occupancyStatus' => OccupancyStatus::OUT_OF_ORDER->value,
        ])->assertOk();
        $this->assertEquals(OccupancyStatus::OUT_OF_ORDER, Room::find($roomId)->occupancy_status);

        // Release Out of Order
        $this->actingAs($this->admin)->patchJson("/rooms/{$roomId}/release-out-of-order")->assertOk();
        $this->assertEquals(OccupancyStatus::VACANT, Room::find($roomId)->occupancy_status);

        // Delete
        $this->actingAs($this->admin)->deleteJson("/rooms/{$roomId}")->assertOk();
        $this->assertNull(Room::find($roomId));
    }

    public function test_room_types_and_amenities_crud(): void
    {
        // Amenity Create
        $amRes = $this->actingAs($this->admin)->postJson('/amenities', [
            'name' => 'High Speed Fiber WiFi',
            'icon' => 'Wifi',
            'category' => 'CONNECTIVITY',
            'description' => '1 Gbps internet',
        ]);
        $amRes->assertOk();
        $amenityId = $amRes->json('data.id');

        // Room Type Create
        $rtRes = $this->actingAs($this->admin)->postJson('/room-types', [
            'name' => 'Royal Heritage Villa',
            'description' => 'Sprawling private garden villa',
            'basePrice' => 12500,
            'maxGuests' => 4,
            'bedType' => 'KING',
            'bedCount' => 2,
            'sizeSqFt' => 750,
            'amenities' => [$amenityId],
        ]);
        $rtRes->assertOk();
        $roomTypeId = $rtRes->json('data.id');

        // Details
        $this->actingAs($this->admin)->getJson("/room-types/{$roomTypeId}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Royal Heritage Villa');
    }

    public function test_in_person_and_public_booking_flow(): void
    {
        $room = Room::where('occupancy_status', OccupancyStatus::VACANT->value)->firstOrFail();
        $customer = Customer::firstOrFail();

        // 1. In-person staff booking
        $bookRes = $this->actingAs($this->admin)->postJson('/bookings', [
            'checkInDate' => now()->toDateString(),
            'checkOutDate' => now()->addDays(2)->toDateString(),
            'customerId' => $customer->id,
            'rooms' => [$room->id],
            'totalGuests' => 2,
        ]);
        $bookRes->assertOk()->assertJsonStructure(['data' => ['id', 'bookingReference', 'invoiceId']]);
        $bookingId = $bookRes->json('data.id');

        // Verify room became occupied
        $this->assertEquals(OccupancyStatus::OCCUPIED, Room::find($room->id)->occupancy_status);

        // 2. Status transition: Check Out
        $this->actingAs($this->admin)->patchJson("/bookings/{$bookingId}/status", [
            'status' => 'CHECKED_OUT',
        ])->assertOk();

        // Room should now be VACANT and DIRTY, with housekeeping task generated
        $room->refresh();
        $this->assertEquals(OccupancyStatus::VACANT, $room->occupancy_status);
        $this->assertEquals(HousekeepingStatus::DIRTY, $room->housekeeping_status);

        $task = HousekeepingTask::where('room_id', $room->id)->latest()->first();
        $this->assertNotNull($task);

        // Complete the task and verify room cleanliness syncs to CLEAN
        $this->actingAs($this->admin)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'COMPLETED',
        ])->assertOk();

        $room->refresh();
        $this->assertEquals(HousekeepingStatus::CLEAN, $room->housekeeping_status);
    }

    public function test_invoicing_and_payments(): void
    {
        $customer = Customer::with('user')->firstOrFail();
        $room = Room::firstOrFail();

        $booking = Booking::create([
            'booking_reference' => 'BK-TEST-INV-1',
            'customer_id' => $customer->user_id,
            'guest_name' => 'Invoice Test Guest',
            'guest_email' => 'invguest@example.com',
            'guest_phone' => '+91 9876543210',
            'check_in_date' => now(),
            'check_out_date' => now()->addDays(2),
            'total_guests' => 2,
            'total_amount' => 5000,
            'status' => 'CONFIRMED',
            'booked_at' => now(),
        ]);

        $inv = Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'booking_id' => $booking->id,
            'customer_id' => $customer->user_id,
            'subtotal' => 5000,
            'tax_amount' => 600,
            'discount_amount' => 0,
            'total_amount' => 5600,
            'status' => 'UNPAID',
            'issued_at' => now(),
        ]);

        // Cash payment recording
        $payRes = $this->actingAs($this->admin)->postJson('/payments/cash', [
            'invoiceId' => $inv->id,
            'amount' => 5600,
        ]);
        $payRes->assertOk()->assertJsonPath('data.status', 'COMPLETED');

        // Invoice should now be PAID
        $this->assertEquals(InvoiceStatus::PAID, Invoice::find($inv->id)->status);

        // Payment stats
        $this->actingAs($this->admin)->getJson('/payments/stats')->assertOk();
    }

    public function test_dashboard_and_analytics_metrics(): void
    {
        $this->actingAs($this->admin)->getJson('/dashboard/stats')->assertOk();
        $this->actingAs($this->admin)->getJson('/dashboard/room-status')->assertOk();
        $this->actingAs($this->admin)->getJson('/dashboard/revenue-trend')->assertOk();
        $this->actingAs($this->admin)->getJson('/dashboard/booking-status')->assertOk();
        $this->actingAs($this->admin)->getJson('/dashboard/stays')->assertOk();
        $this->actingAs($this->admin)->getJson('/dashboard/tasks')->assertOk();
    }
}
