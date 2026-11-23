<?php

namespace Tests\Feature;

use App\Enums\HousekeepingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OccupancyStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Amenity;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\HousekeepingTask;
use App\Models\Invoice;
use App\Models\Issue;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
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
            ->assertJsonPath('data.category', 'RECEPTIONIST')
            ->assertJsonPath('data.user.fullName', 'Front Desk Executive')
            ->assertJsonPath('data.user.profileImage', null)
            ->assertJsonPath('data.fatherName', 'Father Executive')
            ->assertJsonPath('data.assignedTasks', []);

        $this->actingAs($this->admin)->patchJson("/staff/{$staffId}", [
            'experience' => '4 years',
        ])->assertOk();

        $this->actingAs($this->admin)->deleteJson("/staff/{$staffId}")->assertOk();
    }

    public function test_task_status_transitions_update_timestamps_and_room_state(): void
    {
        $task = HousekeepingTask::with('room')->firstOrFail();
        $room = $task->room;

        $this->actingAs($this->admin)
            ->patchJson("/dashboard/tasks/{$task->id}/status", [
                'status' => 'IN_PROGRESS',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'IN_PROGRESS');

        $this->assertNotNull($task->fresh()->started_at);
        $this->assertSame(HousekeepingStatus::CLEANING, $room->fresh()->housekeeping_status);

        $this->actingAs($this->admin)
            ->patchJson("/dashboard/tasks/{$task->id}/status", [
                'status' => 'COMPLETED',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'COMPLETED');

        $this->assertNotNull($task->fresh()->completed_at);
        $this->assertSame(HousekeepingStatus::CLEAN, $room->fresh()->housekeeping_status);
        $this->actingAs($this->admin)
            ->patchJson("/dashboard/tasks/{$task->id}/status", [
                'status' => 'PENDING',
            ])
            ->assertStatus(422);
    }

    public function test_issue_api_returns_reference_list_and_detail_contracts(): void
    {
        $room = Room::firstOrFail();
        $response = $this->actingAs($this->admin)->postJson('/issues', [
            'title' => 'Bathroom tap is leaking',
            'description' => 'Water continues to drip from the sink tap.',
            'category' => 'PLUMBING',
            'priority' => 'HIGH',
            'roomId' => $room->id,
        ]);

        $response->assertCreated()->assertJsonStructure([
            'data' => [
                'id',
                'reference',
                'title',
                'category',
                'priority',
                'status',
                'room' => ['id', 'roomNumber'],
                'reporter' => ['id', 'fullName'],
                'reportedAt',
            ],
            'message',
        ]);

        $issueId = $response->json('data.id');
        $this->actingAs($this->admin)->getJson('/issues')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => [
                    'id',
                    'reference',
                    'title',
                    'category',
                    'priority',
                    'status',
                    'room' => ['id', 'roomNumber'],
                    'reporter' => ['id', 'fullName'],
                    'assignee',
                    'reportedAt',
                ]],
                'meta' => ['total', 'page', 'limit', 'totalPages'],
            ])
            ->assertJsonPath('data.0.id', $issueId);

        $this->actingAs($this->admin)->getJson("/issues/{$issueId}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'reference',
                    'description',
                    'reportedAt',
                    'startedAt',
                    'completedAt',
                    'resolvedAt',
                    'createdAt',
                    'updatedAt',
                    'room' => ['id', 'roomNumber', 'name', 'floor'],
                    'reporter' => ['id', 'fullName'],
                    'assignee',
                    'image',
                ],
            ])
            ->assertJsonPath('data.id', $issueId);

        $updateResponse = $this->actingAs($this->admin)->patchJson("/issues/{$issueId}", [
            'title' => 'Updated leaking bathroom tap',
            'status' => 'IN_PROGRESS',
        ]);
        $updateResponse->assertOk()
            ->assertJsonPath('data.title', 'Updated leaking bathroom tap')
            ->assertJsonPath('data.status', 'IN_PROGRESS');
        $this->assertNotNull($updateResponse->json('data.startedAt'));

        $this->actingAs($this->admin)->getJson('/issues/stats')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.value', number_format(Issue::count()));
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

    public function test_booking_wizard_searches_rooms_and_persists_guest_details(): void
    {
        $room = Room::with('roomType')
            ->where('occupancy_status', OccupancyStatus::VACANT->value)
            ->whereHas('roomType', fn($query) => $query->where('max_guests', '>=', 1))
            ->firstOrFail();
        $customer = Customer::firstOrFail();

        $this->actingAs($this->admin)
            ->getJson('/customers/search?q=' . urlencode($customer->user->full_name))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $customer->id,
                'fullName' => $customer->user->full_name,
            ]);

        $checkIn = now()->addYears(2)->toDateString();
        $checkOut = now()->addYears(2)->addDays(2)->toDateString();
        $availabilityParams = [
            'checkInDate' => $checkIn,
            'checkOutDate' => $checkOut,
            'adults' => 1,
            'children' => 0,
        ];

        $this->getJson('/public/bookings/available-rooms?' . http_build_query($availabilityParams))
            ->assertOk()
            ->assertJsonFragment(['id' => $room->id]);

        $this->actingAs($this->admin)
            ->getJson('/bookings/available-rooms?' . http_build_query($availabilityParams))
            ->assertOk()
            ->assertJsonStructure(['data' => ['*' => [
                'id',
                'roomNumber',
                'roomType' => [
                    'maxGuests',
                    'basePrice',
                    'image',
                    'amenities',
                ],
            ]]])
            ->assertJsonFragment(['id' => $room->id]);

        $response = $this->actingAs($this->admin)->postJson('/dashboard/bookings', [
            ...$availabilityParams,
            'customerId' => $customer->id,
            'roomId' => $room->id,
            'guests' => [[
                'fullName' => 'Wizard Primary Guest',
                'age' => 30,
                'gender' => 'MALE',
                'idProofNumber' => 'ID-TEST-1',
            ]],
            'specialRequest' => 'Late arrival',
            'paymentMethod' => PaymentMethod::CASH->value,
        ]);

        $response->assertOk()->assertJsonStructure(['data' => ['id', 'bookingReference', 'invoiceId']]);
        $booking = Booking::with(['bookingRooms', 'invoices'])->findOrFail($response->json('data.id'));

        $this->assertSame('Wizard Primary Guest', $booking->guest_name);
        $this->assertSame('Late arrival', $booking->special_request);
        $this->assertSame(1, BookingGuest::where('booking_id', $booking->id)->count());
        $this->assertSame($room->id, $booking->bookingRooms->first()->assigned_room_id);
        $this->assertSame(OccupancyStatus::RESERVED, $room->fresh()->occupancy_status);
        $this->assertSame((int) round($booking->invoices->first()->subtotal * 0.18), $booking->invoices->first()->tax_amount);

        $this->actingAs($this->admin)
            ->getJson("/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.bookingReference', $booking->booking_reference)
            ->assertJsonPath('data.bookingGuests.0.fullName', 'Wizard Primary Guest')
            ->assertJsonPath('data.invoice.totalAmount', $booking->invoices->first()->total_amount);

        $remainingRoomIds = collect($this->actingAs($this->admin)
            ->getJson('/bookings/available-rooms?' . http_build_query($availabilityParams))
            ->assertOk()
            ->json('data'))->pluck('id');
        $this->assertFalse($remainingRoomIds->contains($room->id));
    }

    public function test_in_person_and_public_booking_flow(): void
    {
        $room = Room::where('occupancy_status', OccupancyStatus::VACANT->value)
            ->whereDoesntHave('housekeepingTasks')
            ->firstOrFail();
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

        $this->actingAs($this->admin)
            ->getJson('/bookings?status=CONFIRMED&invoiceStatus=UNPAID')
            ->assertOk()
            ->assertJsonPath('data.0.id', $bookingId)
            ->assertJsonPath('data.0.status', 'CONFIRMED')
            ->assertJsonPath('data.0.customer.fullName', $customer->user->full_name)
            ->assertJsonPath('data.0.bookingRoom.roomType.name', $room->roomType->name)
            ->assertJsonPath('data.0.invoice.status', 'UNPAID')
            ->assertJsonPath('data.0.canCheckIn', false)
            ->assertJsonStructure(['data' => ['*' => [
                'paymentMethod',
                'checkInDate',
                'checkOutDate',
                'totalGuests',
                'bookedAt',
                'bookingRoom',
                'invoice',
                'canMarkNoShow',
            ]]]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.bookings'))
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page
                ->has('stats', 4)
                ->where('stats.0.value', number_format(Booking::count()))
                ->where('stats.1.value', number_format(Booking::where('status', 'CONFIRMED')->count()))
                ->where('stats.2.value', number_format(Booking::where('status', 'PENDING')->count()))
                ->where('stats.3.value', '₹' . number_format(Booking::where('status', '!=', 'CANCELLED')->sum('total_amount')))
                ->where('stats.1.link', '/dashboard/bookings?status=CONFIRMED'));

        $this->actingAs($this->admin)
            ->getJson('/bookings?paymentMethod=ONLINE')
            ->assertOk()
            ->assertJsonPath('meta.total', Booking::where('payment_method', 'ONLINE')->count());

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

        // Start and complete the task to verify room status synchronization.
        $this->actingAs($this->admin)->patchJson("/tasks/{$task->id}/status", [
            'status' => 'IN_PROGRESS',
        ])->assertOk();

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
            'payment_method' => PaymentMethod::CASH->value,
            'status' => 'CONFIRMED',
            'booked_at' => now(),
        ]);
        BookingRoom::create([
            'booking_id' => $booking->id,
            'room_type_id' => $room->room_type_id,
            'assigned_room_id' => $room->id,
            'price_per_night' => 2500,
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
        ]);
        $payRes->assertCreated()
            ->assertJsonPath('data.payment.paymentStatus', 'COMPLETED')
            ->assertJsonPath('data.payment.paymentMethod', 'CASH')
            ->assertJsonPath('data.booking.balance', 0);

        // Invoice should now be PAID
        $this->assertEquals(InvoiceStatus::PAID, Invoice::find($inv->id)->status);

        $this->actingAs($this->admin)->getJson('/payments')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => [
                    'id',
                    'bookingId',
                    'paymentReference',
                    'transactionId',
                    'invoiceNumber',
                    'bookingReference',
                    'customerName',
                    'paymentMethod',
                    'paymentStatus',
                    'recordedByName',
                ]],
                'meta' => ['total', 'page', 'limit', 'totalPages'],
            ]);
        $this->actingAs($this->admin)->getJson('/payments/stats')
            ->assertOk()
            ->assertJsonCount(4, 'data');

        $qrBooking = Booking::create([
            'booking_reference' => 'BK-TEST-QR-1',
            'customer_id' => $customer->user_id,
            'guest_name' => 'QR Test Guest',
            'guest_email' => 'qrguest@example.com',
            'guest_phone' => '+91 9876543210',
            'check_in_date' => now(),
            'check_out_date' => now()->addDays(2),
            'total_guests' => 2,
            'total_amount' => 3000,
            'payment_method' => PaymentMethod::ONLINE->value,
            'status' => 'CONFIRMED',
            'booked_at' => now(),
        ]);
        BookingRoom::create([
            'booking_id' => $qrBooking->id,
            'room_type_id' => $room->room_type_id,
            'assigned_room_id' => $room->id,
            'price_per_night' => 1500,
        ]);
        $qrInvoice = Invoice::create([
            'invoice_number' => 'INV-TEST-QR-1',
            'booking_id' => $qrBooking->id,
            'customer_id' => $customer->user_id,
            'subtotal' => 3000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 3000,
            'status' => 'UNPAID',
            'issued_at' => now(),
        ]);
        $qrResponse = $this->actingAs($this->admin)->postJson('/payments/qr', [
            'invoiceId' => $qrInvoice->id,
            'transactionId' => 'UPI-TEST-REFERENCE-001',
            'amount' => 3000,
        ]);
        $qrResponse->assertCreated()
            ->assertJsonPath('data.payment.paymentStatus', 'COMPLETED')
            ->assertJsonPath('data.payment.paymentMethod', 'ONLINE')
            ->assertJsonPath('data.payment.amount', 3000);
        $this->assertEquals(InvoiceStatus::PAID, $qrInvoice->fresh()->status);
    }

    public function test_online_payment_requires_gateway_verification_before_settlement(): void
    {
        $customer = Customer::with('user')->firstOrFail();
        $room = Room::firstOrFail();
        $booking = Booking::create([
            'booking_reference' => 'BK-TEST-ONLINE-1',
            'customer_id' => $customer->user_id,
            'guest_name' => 'Online Test Guest',
            'guest_email' => 'onlineguest@example.com',
            'guest_phone' => '+91 9876543210',
            'check_in_date' => now(),
            'check_out_date' => now()->addDays(2),
            'total_guests' => 2,
            'total_amount' => 3500,
            'payment_method' => PaymentMethod::ONLINE->value,
            'status' => 'CONFIRMED',
            'booked_at' => now(),
        ]);
        BookingRoom::create([
            'booking_id' => $booking->id,
            'room_type_id' => $room->room_type_id,
            'assigned_room_id' => $room->id,
            'price_per_night' => 1750,
        ]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-ONLINE-1',
            'booking_id' => $booking->id,
            'customer_id' => $customer->user_id,
            'subtotal' => 3500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 3500,
            'status' => 'UNPAID',
            'issued_at' => now(),
        ]);

        config([
            'services.razorpay.key_id' => 'rzp_test_key',
            'services.razorpay.key_secret' => 'test-secret',
            'services.razorpay.webhook_secret' => 'webhook-secret',
        ]);
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_test_001',
                'amount' => 350000,
                'currency' => 'INR',
            ]),
            'https://api.razorpay.com/v1/payments/pay_test_001' => Http::response([
                'id' => 'pay_test_001',
                'order_id' => 'order_test_001',
                'amount' => 350000,
                'currency' => 'INR',
                'status' => 'captured',
                'captured' => true,
                'created_at' => now()->timestamp,
            ]),
        ]);

        $order = $this->actingAs($this->admin)->postJson('/payments/orders', [
            'bookingId' => $booking->id,
        ]);
        $order->assertCreated()
            ->assertJsonPath('data.orderId', 'order_test_001')
            ->assertJsonPath('data.amount', 350000)
            ->assertJsonPath('data.currency', 'INR');

        $paymentId = $order->json('data.paymentId');
        $signature = hash_hmac('sha256', 'order_test_001|pay_test_001', 'test-secret');
        $this->actingAs($this->admin)->postJson('/payments/verify', [
            'paymentId' => $paymentId,
            'razorpayPaymentId' => 'pay_test_001',
            'razorpayOrderId' => 'order_test_001',
            'razorpaySignature' => $signature,
        ])->assertOk()->assertJsonPath('data.paymentStatus', 'COMPLETED');

        $this->assertSame(PaymentStatus::COMPLETED, Payment::findOrFail($paymentId)->payment_status);
        $this->assertSame(InvoiceStatus::PAID, $invoice->fresh()->status);

        $webhookBody = json_encode([
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test_001',
                        'order_id' => 'order_test_001',
                        'amount' => 350000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'captured' => true,
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
        $this->call('POST', '/payments/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $webhookBody, 'webhook-secret'),
            'HTTP_X_RAZORPAY_EVENT_ID' => 'evt_test_001',
        ], $webhookBody)->assertOk();
        $this->assertDatabaseHas('payment_webhook_events', ['event_id' => 'evt_test_001']);
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
