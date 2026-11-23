<?php

namespace Tests\Feature;

use App\Enums\OccupancyStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_list_view_update_and_cancel_an_unpaid_invoice(): void
    {
        $admin = User::where('role', UserRole::ADMIN->value)->firstOrFail();
        $room = Room::where('occupancy_status', OccupancyStatus::VACANT->value)->firstOrFail();
        $checkoutToken = (string) Str::uuid();

        $this->from(route('checkout'))->post(route('checkout.store'), [
            'checkoutToken' => $checkoutToken,
            'guestName' => 'Invoice Test Guest',
            'guestEmail' => 'invoice-test@example.com',
            'guestPhone' => '+91 9888877777',
            'roomId' => $room->id,
            'checkInDate' => now()->addDay()->toDateString(),
            'checkOutDate' => now()->addDays(3)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'guests' => [['fullName' => 'Invoice Test Guest']],
            'paymentMethod' => 'ONLINE',
        ])->assertRedirect(route('checkout'));

        $booking = Booking::where('public_checkout_token_hash', hash('sha256', $checkoutToken))->firstOrFail();
        $invoice = Invoice::where('booking_id', $booking->id)->firstOrFail();

        $this->actingAs($admin)
            ->getJson(route('invoices.index'))
            ->assertOk()
            ->assertJsonPath('data.0.invoiceNumber', $invoice->invoice_number)
            ->assertJsonPath('data.0.bookingReference', $booking->booking_reference);

        $this->getJson(route('invoices.show', $invoice->id))
            ->assertOk()
            ->assertJsonPath('data.invoiceNumber', $invoice->invoice_number)
            ->assertJsonPath('data.customer.fullName', 'Invoice Test Guest')
            ->assertJsonPath('data.booking.bookingReference', $booking->booking_reference)
            ->assertJsonStructure([
                'data' => [
                    'charges' => ['subtotal', 'discountAmount', 'taxAmount', 'totalAmount'],
                    'payments',
                    'paidAmount',
                    'balanceDue',
                ],
            ]);

        $newSubtotal = (int) $invoice->subtotal + 1000;
        $this->patchJson(route('invoices.update', $invoice->id), ['subtotal' => $newSubtotal])
            ->assertOk()
            ->assertJsonPath('data.subtotal', $newSubtotal);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'subtotal' => $newSubtotal,
            'total_amount' => $newSubtotal + (int) $invoice->tax_amount - (int) $invoice->discount_amount,
        ]);

        $this->postJson(route('invoices.cancel.post', $invoice->id))
            ->assertOk()
            ->assertJsonPath('data.id', $invoice->id);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'REFUNDED',
        ]);
    }
}
