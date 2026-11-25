<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shares_only_the_users_unread_notifications_allowed_for_their_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $otherUser = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $unread = $this->createNotification($user, NotificationType::BOOKING_CONFIRMED);
        $this->createNotification($user, NotificationType::ISSUE_ASSIGNED);
        $this->createNotification($user, NotificationType::BOOKING_CANCELLED, true);
        $this->createNotification($otherUser, NotificationType::BOOKING_CONFIRMED);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('protect/Dashboard')
                ->has('unreadNotifications', 1)
                ->where('unreadNotifications.0.id', $unread->id)
                ->etc());
    }

    public function test_read_actions_only_update_the_authenticated_users_notifications(): void
    {
        $user = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $otherUser = User::factory()->create(['role' => UserRole::CUSTOMER->value]);
        $notification = $this->createNotification($user, NotificationType::BOOKING_CONFIRMED);
        $otherNotification = $this->createNotification($otherUser, NotificationType::BOOKING_CONFIRMED);

        $this->actingAs($user)
            ->from('/dashboard')
            ->patch("/dashboard/notifications/{$notification->id}/read")
            ->assertRedirect('/dashboard');

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertFalse($otherNotification->fresh()->is_read);

        $secondNotification = $this->createNotification($user, NotificationType::PAYMENT_RECEIVED);
        $this->actingAs($user)
            ->from('/dashboard')
            ->patch('/dashboard/notifications/read-all')
            ->assertRedirect('/dashboard');

        $this->assertTrue($secondNotification->fresh()->is_read);
        $this->assertFalse($otherNotification->fresh()->is_read);
    }

    public function test_captured_public_booking_payment_notifies_customer_and_dashboard_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $room = Room::where('occupancy_status', 'VACANT')->firstOrFail();
        $checkoutToken = (string) Str::uuid();

        $this->postJson('/checkout', [
            'checkoutToken' => $checkoutToken,
            'guestName' => 'Notification Guest',
            'guestEmail' => 'notification-guest@example.com',
            'guestPhone' => '+91 9888877777',
            'roomId' => $room->id,
            'checkInDate' => now()->addDay()->toDateString(),
            'checkOutDate' => now()->addDays(3)->toDateString(),
            'adults' => 1,
            'children' => 0,
            'guests' => [['fullName' => 'Notification Guest']],
            'paymentMethod' => 'ONLINE',
        ])->assertCreated();

        $booking = Booking::where('public_checkout_token_hash', hash('sha256', $checkoutToken))->firstOrFail();
        $invoice = Invoice::where('booking_id', $booking->id)->firstOrFail();
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
        $amount = (int) $invoice->total_amount;
        $orderId = 'order_notification_test';
        $payment = Payment::query()->create([
            'payment_reference' => 'PAY-NOTIFICATION-TEST',
            'invoice_id' => $invoice->id,
            'booking_id' => $booking->id,
            'customer_id' => null,
            'amount' => $amount,
            'amount_paise' => $amount * 100,
            'currency' => 'INR',
            'payment_method' => PaymentMethod::ONLINE->value,
            'payment_status' => PaymentStatus::PENDING->value,
            'order_id' => $orderId,
        ]);

        $payload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_notification_test',
                        'order_id' => $orderId,
                        'amount' => $amount * 100,
                        'currency' => 'INR',
                        'captured' => true,
                        'status' => 'captured',
                    ],
                ],
            ],
        ];
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $webhookSecret = 'notification-test-secret';
        config(['services.razorpay.webhook_secret' => $webhookSecret]);

        $response = $this->call('POST', '/payments/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $rawPayload, $webhookSecret),
            'HTTP_X_RAZORPAY_EVENT_ID' => 'event_notification_test',
        ], $rawPayload);

        $response->assertOk();
        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $booking->customer_id,
            'type' => NotificationType::PAYMENT_RECEIVED->value,
            'reference_id' => $payment->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $booking->customer_id,
            'type' => NotificationType::BOOKING_CONFIRMED->value,
            'reference_id' => $booking->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => NotificationType::BOOKING_CONFIRMED->value,
            'reference_id' => $booking->id,
        ]);
    }

    private function createNotification(User $user, NotificationType $type, bool $isRead = false): Notification
    {
        return Notification::query()->create([
            'user_id' => $user->id,
            'type' => $type->value,
            'title' => 'Test notification',
            'message' => 'Notification test message',
            'is_read' => $isRead,
        ]);
    }
}
