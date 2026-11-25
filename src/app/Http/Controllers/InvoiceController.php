<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        [$user, $role, $category] = $this->actor($request);
        $this->assertReader($role, $category);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([...array_column(InvoiceStatus::cases(), 'value'), 'OUTSTANDING'])],
            'issuedThisMonth' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 10);
        $query = Invoice::with(['customer', 'booking.customer', 'booking.bookingRooms.roomType', 'booking.bookingRooms.room', 'payments']);

        if ($role === UserRole::CUSTOMER->value) {
            $query->where(fn ($invoices) => $invoices
                ->where('customer_id', $user->id)
                ->orWhereHas('booking', fn ($bookings) => $bookings->where('customer_id', $user->id)));
        }
        if (! empty($filters['status'])) {
            if ($filters['status'] === 'OUTSTANDING') {
                $query->whereIn('status', [
                    InvoiceStatus::UNPAID->value,
                    InvoiceStatus::PARTIALLY_PAID->value,
                ]);
            } else {
                $query->where('status', $filters['status']);
            }
        }
        if ($request->boolean('issuedThisMonth')) {
            $monthStart = now()->startOfMonth();
            $query->where('issued_at', '>=', $monthStart)
                ->where('issued_at', '<', $monthStart->copy()->addMonth());
        }
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($invoices) use ($search) {
                $invoices->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn ($bookings) => $bookings->where('booking_reference', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($customers) => $customers
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $total = (clone $query)->count();
        $invoices = $query->orderByDesc('issued_at')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn (Invoice $invoice) => $role === UserRole::CUSTOMER->value
                ? $this->mapCustomerInvoice($invoice)
                : $this->mapInvoiceItem($invoice));
        $meta = [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ];
        $stats = $this->getStats($role === UserRole::CUSTOMER->value ? $user : null);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => $invoices, 'meta' => $meta]);
        }

        return Inertia::render('protect/invoices/index', [
            'invoices' => ['data' => $invoices, 'meta' => $meta],
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function stats(Request $request)
    {
        [$user, $role, $category] = $this->actor($request);
        $this->assertReader($role, $category);

        return response()->json(['data' => $this->getStats($role === UserRole::CUSTOMER->value ? $user : null)]);
    }

    public function show(Request $request, string $id)
    {
        [$user, $role, $category] = $this->actor($request);
        $this->assertReader($role, $category);

        $query = Invoice::with([
            'customer',
            'booking.customer',
            'booking.bookingRooms.roomType',
            'booking.bookingRooms.room',
            'payments' => fn ($payments) => $payments->orderByDesc('created_at'),
        ]);
        if ($role === UserRole::CUSTOMER->value) {
            $query->where(fn ($invoices) => $invoices
                ->where('customer_id', $user->id)
                ->orWhereHas('booking', fn ($bookings) => $bookings->where('customer_id', $user->id)));
        }

        $invoice = $query->findOrFail($id);

        return response()->json(['data' => $this->mapInvoiceDetails($invoice)]);
    }

    public function update(Request $request, string $id)
    {
        [$user, $role] = $this->actor($request);
        $this->assertManager($role);
        $amounts = $request->validate([
            'subtotal' => ['sometimes', 'required', 'integer', 'min:0'],
            'taxAmount' => ['sometimes', 'required', 'integer', 'min:0'],
            'discountAmount' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);
        abort_if($amounts === [], 422, 'Provide at least one invoice amount to update.');

        $result = DB::transaction(function () use ($id, $amounts) {
            $invoice = Invoice::with('payments')->lockForUpdate()->findOrFail($id);
            $hasSettledPayments = $invoice->payments->contains(fn (Payment $payment) => in_array(
                $this->enumValue($payment->payment_status),
                [PaymentStatus::COMPLETED->value, PaymentStatus::REFUNDED->value],
                true,
            ));
            abort_if(
                $invoice->status !== InvoiceStatus::UNPAID || $hasSettledPayments,
                409,
                'Only unpaid invoices without settled payments can be updated.',
            );

            $subtotal = (int) ($amounts['subtotal'] ?? $invoice->subtotal);
            $taxAmount = (int) ($amounts['taxAmount'] ?? $invoice->tax_amount);
            $discountAmount = (int) ($amounts['discountAmount'] ?? $invoice->discount_amount);
            $totalAmount = $subtotal + $taxAmount - $discountAmount;
            abort_if($totalAmount < 0, 422, 'Discount cannot exceed subtotal plus GST.');

            Payment::where('invoice_id', $id)
                ->where('payment_status', PaymentStatus::PENDING->value)
                ->update(['payment_status' => PaymentStatus::FAILED->value]);
            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ]);

            return compact('id', 'subtotal', 'taxAmount', 'discountAmount', 'totalAmount');
        });

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => $result, 'message' => 'Invoice updated successfully.']);
        }

        return redirect()->back()->with('success', 'Invoice updated successfully.');
    }

    public function cancel(Request $request, string $id)
    {
        [, $role] = $this->actor($request);
        $this->assertManager($role);

        DB::transaction(function () use ($id) {
            $invoice = Invoice::with('payments')->lockForUpdate()->findOrFail($id);
            if ($invoice->status === InvoiceStatus::REFUNDED) {
                return;
            }
            abort_if(
                $invoice->payments->contains(fn (Payment $payment) => $this->enumValue($payment->payment_status) === PaymentStatus::COMPLETED->value),
                409,
                'Refund completed payments before cancelling this invoice.',
            );
            abort_if(
                $invoice->status !== InvoiceStatus::UNPAID,
                409,
                'This invoice cannot be cancelled in its current status.',
            );

            Payment::where('invoice_id', $id)
                ->where('payment_status', PaymentStatus::PENDING->value)
                ->update(['payment_status' => PaymentStatus::FAILED->value]);
            $invoice->update(['status' => InvoiceStatus::REFUNDED->value]);
        });

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $id], 'message' => 'Invoice cancelled successfully.']);
        }

        return redirect()->back()->with('success', 'Invoice cancelled successfully.');
    }

    private function getStats(?User $customer): array
    {
        $baseQuery = Invoice::query();
        if ($customer) {
            $baseQuery->where(fn ($invoices) => $invoices
                ->where('customer_id', $customer->id)
                ->orWhereHas('booking', fn ($bookings) => $bookings->where('customer_id', $customer->id)));
        }

        $monthStart = now()->startOfMonth();
        $nextMonthStart = now()->addMonth()->startOfMonth();
        $openInvoices = (clone $baseQuery)->whereIn('status', [
            InvoiceStatus::UNPAID->value,
            InvoiceStatus::PARTIALLY_PAID->value,
        ]);
        $openTotal = (int) (clone $openInvoices)->sum('total_amount');
        $paidOnOpen = (int) Payment::where('payment_status', PaymentStatus::COMPLETED->value)
            ->whereIn('invoice_id', (clone $openInvoices)->select('id'))
            ->sum('amount');

        $formatAmount = fn (int $amount) => '₹'.number_format($amount);
        $link = '/dashboard/invoices';

        return [
            ['label' => 'Invoiced This Month', 'value' => $formatAmount((int) (clone $baseQuery)->whereBetween('issued_at', [$monthStart, $nextMonthStart])->sum('total_amount')), 'link' => $link.'?issuedThisMonth=1', 'icon' => 'IndianRupee'],
            ['label' => 'Paid Invoices', 'value' => number_format((clone $baseQuery)->where('status', InvoiceStatus::PAID->value)->count()), 'link' => $link.'?status='.InvoiceStatus::PAID->value, 'icon' => 'CheckCircle'],
            ['label' => 'Partially Paid', 'value' => number_format((clone $baseQuery)->where('status', InvoiceStatus::PARTIALLY_PAID->value)->count()), 'link' => $link.'?status='.InvoiceStatus::PARTIALLY_PAID->value, 'icon' => 'Clock'],
            ['label' => 'Outstanding Balance', 'value' => $formatAmount(max(0, $openTotal - $paidOnOpen)), 'link' => $link.'?status=OUTSTANDING', 'icon' => 'Wallet'],
        ];
    }

    private function mapInvoiceItem(Invoice $invoice): array
    {
        $paidAmount = $this->paidAmount($invoice);

        return [
            'id' => $invoice->id,
            'invoiceNumber' => $invoice->invoice_number,
            'bookingReference' => $invoice->booking->booking_reference,
            'customerName' => $this->customerName($invoice),
            'totalAmount' => (int) $invoice->total_amount,
            'paidAmount' => $paidAmount,
            'balanceDue' => max(0, (int) $invoice->total_amount - $paidAmount),
            'status' => $this->enumValue($invoice->status),
            'issuedAt' => $invoice->issued_at?->toIso8601String(),
        ];
    }

    private function mapCustomerInvoice(Invoice $invoice): array
    {
        return [
            ...$this->mapInvoiceItem($invoice),
            'bookingId' => $invoice->booking->id,
            'invoiceDate' => $invoice->issued_at?->toDateString(),
            'dueDate' => $invoice->booking->check_out_date?->toDateString(),
        ];
    }

    private function mapInvoiceDetails(Invoice $invoice): array
    {
        $booking = $invoice->booking;
        $bookingRoom = $booking->bookingRooms->first();
        $checkIn = $booking->check_in_date;
        $checkOut = $booking->check_out_date;
        $nights = $checkIn && $checkOut ? max(0, (int) $checkIn->diffInDays($checkOut, false)) : 0;
        $paidAmount = $this->paidAmount($invoice);
        $customer = $invoice->customer ?? $booking->customer;

        return [
            ...$this->mapInvoiceItem($invoice),
            'customer' => [
                'id' => $customer?->id ?? $invoice->customer_id ?? $booking->customer_id ?? '',
                'fullName' => $customer?->full_name ?? $customer?->name ?? $booking->guest_name ?? 'Guest',
                'email' => $customer?->email ?? $booking->guest_email ?? '',
                'phone' => $customer?->phone ?? $booking->guest_phone ?? '',
            ],
            'booking' => [
                'id' => $booking->id,
                'bookingReference' => $booking->booking_reference,
                'status' => $this->enumValue($booking->status),
                'checkInDate' => $checkIn?->toIso8601String(),
                'checkOutDate' => $checkOut?->toIso8601String(),
                'roomCharge' => $bookingRoom ? [
                    'roomType' => $bookingRoom->roomType?->name ?? 'Room',
                    'roomNumber' => $bookingRoom->room?->room_number,
                    'pricePerNight' => (int) $bookingRoom->price_per_night,
                    'nights' => $nights,
                    'total' => (int) $bookingRoom->price_per_night * $nights,
                ] : null,
            ],
            'charges' => [
                'subtotal' => (int) $invoice->subtotal,
                'discountAmount' => (int) $invoice->discount_amount,
                'taxAmount' => (int) $invoice->tax_amount,
                'totalAmount' => (int) $invoice->total_amount,
            ],
            'payments' => $invoice->payments->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'paymentReference' => $payment->payment_reference,
                'transactionId' => $payment->transaction_id,
                'amount' => (int) $payment->amount,
                'paymentMethod' => $this->enumValue($payment->payment_method),
                'paymentStatus' => $this->enumValue($payment->payment_status),
                'paidAt' => $payment->paid_at?->toIso8601String(),
                'createdAt' => $payment->created_at?->toIso8601String(),
            ])->values(),
            'paidAmount' => $paidAmount,
            'balanceDue' => max(0, (int) $invoice->total_amount - $paidAmount),
        ];
    }

    private function paidAmount(Invoice $invoice): int
    {
        return (int) $invoice->payments
            ->filter(fn (Payment $payment) => $this->enumValue($payment->payment_status) === PaymentStatus::COMPLETED->value)
            ->sum('amount');
    }

    private function customerName(Invoice $invoice): string
    {
        $customer = $invoice->customer ?? $invoice->booking->customer;

        return $customer?->full_name ?? $customer?->name ?? $invoice->booking->guest_name ?? 'Guest';
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? $value->value : (string) $value;
    }

    private function actor(Request $request): array
    {
        $user = $request->user();
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        return [$user, $role, $user->category];
    }

    private function assertReader(string $role, ?string $category): void
    {
        abort_unless(
            in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value, UserRole::STAFF->value, UserRole::CUSTOMER->value], true)
                && ! ($role === UserRole::STAFF->value && $category === 'HOUSEKEEPER'),
            403,
        );
    }

    private function assertManager(string $role): void
    {
        abort_unless(in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value], true), 403);
    }
}
