<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\NotificationType;
use App\Enums\OccupancyStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Room;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PaymentController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request)
    {
        [$user, $role, $category] = $this->actor($request);
        $this->assertPaymentReader($role, $category);
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(100, max(1, (int) $request->input('limit', 15)));
        $query = Payment::with(['invoice.booking', 'booking', 'customer', 'receivedBy'])
            ->orderByDesc('created_at');

        if ($role === UserRole::CUSTOMER->value) {
            $query->where('customer_id', $user->id);
        }
        $method = $request->input('paymentMethod', $request->input('method'));
        $status = $request->input('paymentStatus', $request->input('status'));
        if ($method) {
            $query->where('payment_method', $method);
        }
        if ($status) {
            $query->where('payment_status', $status);
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($payments) use ($search) {
                $payments->where('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn($bookings) => $bookings->where('booking_reference', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn($customers) => $customers->where('name', 'like', "%{$search}%"));
            });
        }

        $total = (clone $query)->count();
        $payments = $query->skip(($page - 1) * $limit)->take($limit)->get()
            ->map(fn(Payment $payment) => $role === UserRole::CUSTOMER->value
                ? $this->mapCustomerPayment($payment)
                : $this->mapPayment($payment));

        $invoices = Invoice::whereNotIn('status', [InvoiceStatus::PAID->value, InvoiceStatus::REFUNDED->value])
            ->when($role === UserRole::CUSTOMER->value, fn($invoices) => $invoices->where('customer_id', $user->id))
            ->get()->map(fn($i) => [
                'id' => $i->id,
                'invoiceNumber' => $i->invoice_number,
                'totalAmount' => (float) $i->total_amount,
            ]);
        $stats = $this->getStats($role, $category, $user->id);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'data' => $payments,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => (int) ceil(max($total, 1) / $limit),
                ],
            ]);
        }

        return Inertia::render('protect/payments/index', [
            'payments' => [
                'data' => $payments,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => (int) ceil(max($total, 1) / $limit),
                ],
            ],
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => $request->only(['paymentMethod', 'paymentStatus', 'search', 'page', 'limit']),
        ]);
    }

    public function show(Request $request, string $id)
    {
        [$user, $role, $category] = $this->actor($request);
        $this->assertPaymentReader($role, $category);
        $payment = Payment::with(['invoice.booking', 'booking', 'customer', 'receivedBy'])->findOrFail($id);
        abort_if($role === UserRole::CUSTOMER->value && $payment->customer_id !== $user->id, 404);

        return response()->json(['data' => $this->mapPayment($payment)]);
    }

    public function stats(Request $request)
    {
        [$user, $role, $category] = $this->actor($request);
        $this->assertPaymentReader($role, $category);

        return response()->json(['data' => $this->getStats($role, $category, $user->id)]);
    }

    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'bookingId' => ['required', 'string'],
            'paymentId' => ['nullable', 'string'],
        ]);
        [$user, $role, $category] = $this->actor($request);
        $this->assertPaymentInitiator($role, $category);
        $order = $this->createOrderForBooking(
            $validated['bookingId'],
            $user->id,
            $role,
            $validated['paymentId'] ?? null,
        );

        return response()->json(['data' => $order, 'message' => 'Payment order created.'], 201);
    }

    public function createPublicOrder(Request $request)
    {
        $validated = $request->validate([
            'bookingId' => ['required', 'string'],
            'checkoutToken' => ['required', 'uuid'],
        ]);
        $order = $this->createOrderForBooking(
            $validated['bookingId'],
            '',
            UserRole::CUSTOMER->value,
            null,
            $validated['checkoutToken'],
        );

        return response()->json(['data' => $order, 'message' => 'Payment order created.'], 201);
    }

    private function createOrderForBooking(
        string $bookingId,
        string $userId,
        string $role,
        ?string $paymentId = null,
        ?string $checkoutToken = null,
    ): array {
        $keyId = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');
        abort_if(! $keyId || ! $keySecret, 503, 'Online payments are not configured.');

        $attempt = DB::transaction(function () use ($bookingId, $userId, $role, $paymentId, $checkoutToken) {
            $booking = Booking::query()->lockForUpdate()->find($bookingId);
            abort_unless($booking, 404, 'Booking not found.');
            if ($checkoutToken !== null) {
                $tokenHash = hash('sha256', $checkoutToken);
                abort_unless($booking->public_checkout_token_hash && hash_equals($booking->public_checkout_token_hash, $tokenHash), 404, 'Booking not found.');
                abort_if($booking->checkout_expires_at && $booking->checkout_expires_at->isPast(), 422, 'Checkout has expired.');
            } elseif ($role === UserRole::CUSTOMER->value) {
                abort_unless($booking->customer_id === $userId, 404, 'Booking not found.');
            }

            $this->assertInvoicePayable($booking, PaymentMethod::ONLINE, allowCheckedIn: true);
            $invoice = Invoice::query()->lockForUpdate()->where('booking_id', $booking->id)->first();
            abort_unless($invoice, 404, 'Invoice not found.');
            abort_if($invoice->status === InvoiceStatus::REFUNDED, 422, 'This booking is not payable.');

            $paidAmount = (int) Payment::where('invoice_id', $invoice->id)
                ->where('payment_status', PaymentStatus::COMPLETED->value)
                ->sum('amount');
            $outstanding = (int) $invoice->total_amount - $paidAmount;
            abort_if($outstanding <= 0, 422, 'No outstanding balance remains.');

            $query = Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('booking_id', $booking->id)
                ->where('payment_method', PaymentMethod::ONLINE->value)
                ->whereIn('payment_status', [PaymentStatus::PENDING->value, PaymentStatus::FAILED->value]);
            if ($paymentId) {
                $query->where('id', $paymentId);
            }
            $attempt = $query->lockForUpdate()->latest('created_at')->first();
            if ($paymentId && ! $attempt) {
                abort(404, 'Payment not found.');
            }
            if ($attempt && $attempt->payment_status === PaymentStatus::FAILED && $attempt->transaction_id) {
                $attempt = null;
            }
            if ($attempt && (int) $attempt->amount !== $outstanding) {
                abort(409, 'Payment is being reconciled.');
            }
            if (! $attempt) {
                $attempt = Payment::create([
                    'payment_reference' => $this->createPaymentReference(),
                    'invoice_id' => $invoice->id,
                    'booking_id' => $booking->id,
                    'customer_id' => $checkoutToken ? null : $booking->customer_id,
                    'amount' => $outstanding,
                    'amount_paise' => $outstanding * 100,
                    'currency' => 'INR',
                    'payment_method' => PaymentMethod::ONLINE->value,
                    'payment_status' => PaymentStatus::PENDING->value,
                    'recorded_by' => $role === UserRole::CUSTOMER->value ? null : $userId,
                    'order_creation_started_at' => now(),
                ]);
            } else {
                if ($attempt->payment_status === PaymentStatus::FAILED) {
                    $attempt->payment_status = PaymentStatus::PENDING->value;
                }
                if (! $attempt->order_id) {
                    abort_if(
                        $attempt->order_creation_started_at
                            && $attempt->order_creation_started_at->gt(now()->subMinutes(2)),
                        409,
                        'Payment is being reconciled.'
                    );
                    $attempt->order_creation_started_at = now();
                }
                $attempt->save();
            }

            return $attempt->fresh();
        });

        if ($attempt->order_id) {
            return [
                'paymentId' => $attempt->id,
                'orderId' => $attempt->order_id,
                'amount' => (int) ($attempt->amount_paise ?? $attempt->amount * 100),
                'currency' => $attempt->currency,
                'keyId' => $keyId,
            ];
        }

        try {
            $gateway = Http::withBasicAuth($keyId, $keySecret)->timeout(15);
            $ordersResponse = $gateway->get('https://api.razorpay.com/v1/orders', [
                'receipt' => $attempt->payment_reference,
            ]);
            $gatewayOrder = $ordersResponse->successful()
                ? collect($ordersResponse->json('items', []))->firstWhere('receipt', $attempt->payment_reference)
                : null;

            if (! $gatewayOrder) {
                $response = $gateway->post('https://api.razorpay.com/v1/orders', [
                    'amount' => (int) ($attempt->amount_paise ?? $attempt->amount * 100),
                    'currency' => $attempt->currency,
                    'receipt' => $attempt->payment_reference,
                    'notes' => ['paymentId' => $attempt->id],
                ]);
                abort_unless($response->successful(), 502, 'The payment gateway could not create an order.');
                $gatewayOrder = $response->json();
            }
            abort_unless(
                isset($gatewayOrder['id'], $gatewayOrder['amount'], $gatewayOrder['currency'])
                    && (int) $gatewayOrder['amount'] === (int) ($attempt->amount_paise ?? $attempt->amount * 100)
                    && $gatewayOrder['currency'] === $attempt->currency,
                502,
                'The payment gateway returned an invalid order.'
            );
            $attempt->update([
                'order_id' => $gatewayOrder['id'],
                'amount_paise' => (int) $gatewayOrder['amount'],
            ]);
        } catch (\Throwable $error) {
            $attempt->update(['order_creation_started_at' => null]);
            if ($error instanceof HttpExceptionInterface) {
                throw $error;
            }
            abort(502, 'The payment gateway is temporarily unavailable.');
        }

        return [
            'paymentId' => $attempt->id,
            'orderId' => $gatewayOrder['id'],
            'amount' => (int) $gatewayOrder['amount'],
            'currency' => $gatewayOrder['currency'],
            'keyId' => $keyId,
        ];
    }

    public function verifyPayment(Request $request)
    {
        $validated = $request->validate([
            'paymentId' => ['required', 'string'],
            'razorpayPaymentId' => ['required', 'string'],
            'razorpayOrderId' => ['required', 'string'],
            'razorpaySignature' => ['required', 'string'],
        ]);
        [$user, $role, $category] = $this->actor($request);
        $this->assertPaymentInitiator($role, $category);
        $attempt = Payment::findOrFail($validated['paymentId']);
        abort_if($role === UserRole::CUSTOMER->value && $attempt->customer_id !== $user->id, 404);
        $payment = $this->verifyGatewayPayment($attempt, $validated);

        return response()->json(['data' => $this->mapPayment($payment), 'message' => 'Payment verified.']);
    }

    public function verifyPublicPayment(Request $request)
    {
        $validated = $request->validate([
            'paymentId' => ['required', 'string'],
            'razorpayPaymentId' => ['required', 'string'],
            'razorpayOrderId' => ['required', 'string'],
            'razorpaySignature' => ['required', 'string'],
            'checkoutToken' => ['required', 'uuid'],
        ]);
        $attempt = Payment::with('booking')->findOrFail($validated['paymentId']);
        $tokenHash = hash('sha256', $validated['checkoutToken']);
        abort_unless(
            $attempt->customer_id === null
                && $attempt->booking?->public_checkout_token_hash
                && hash_equals($attempt->booking->public_checkout_token_hash, $tokenHash),
            404,
            'Payment not found.'
        );
        $this->verifyGatewayPayment($attempt, $validated);

        return response()->json(['data' => $this->mapPublicPaymentStatus($attempt->fresh(), $tokenHash), 'message' => 'Payment verified.']);
    }

    private function verifyGatewayPayment(Payment $attempt, array $validated): Payment
    {
        $keyId = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');
        abort_if(! $keyId || ! $keySecret, 503, 'Online payments are not configured.');
        abort_unless($attempt->order_id === $validated['razorpayOrderId'], 422, 'Payment order does not match.');

        $expectedSignature = hash_hmac(
            'sha256',
            $validated['razorpayOrderId'] . '|' . $validated['razorpayPaymentId'],
            $keySecret
        );
        abort_unless(hash_equals($expectedSignature, $validated['razorpaySignature']), 422, 'Payment signature is invalid.');

        $response = Http::withBasicAuth($keyId, $keySecret)
            ->timeout(15)
            ->get('https://api.razorpay.com/v1/payments/' . rawurlencode($validated['razorpayPaymentId']));
        abort_unless($response->successful(), 502, 'Could not verify the payment with the gateway.');
        $gatewayPayment = $response->json();
        abort_unless(
            ($gatewayPayment['order_id'] ?? null) === $attempt->order_id
                && (int) ($gatewayPayment['amount'] ?? 0) === (int) ($attempt->amount_paise ?? $attempt->amount * 100)
                && ($gatewayPayment['currency'] ?? null) === $attempt->currency
                && in_array($gatewayPayment['status'] ?? null, ['captured', 'failed'], true),
            422,
            'The gateway payment does not match this order.'
        );

        $this->persistGatewayPayment($attempt, $gatewayPayment);

        return Payment::with(['invoice.booking', 'booking', 'customer', 'receivedBy'])->findOrFail($attempt->id);
    }

    private function persistGatewayPayment(Payment $attempt, array $gatewayPayment): void
    {
        DB::transaction(function () use ($attempt, $gatewayPayment) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($payment->payment_status === PaymentStatus::REFUNDED) {
                return;
            }
            if ($gatewayPayment['status'] === 'failed') {
                if ($payment->payment_status !== PaymentStatus::COMPLETED) {
                    $payment->payment_status = PaymentStatus::FAILED->value;
                    $payment->transaction_id = $gatewayPayment['id'] ?? null;
                    $payment->save();
                }

                return;
            }
            if (empty($gatewayPayment['captured'])) {
                return;
            }
            if ($payment->payment_status === PaymentStatus::COMPLETED && $payment->transaction_id === $gatewayPayment['id']) {
                return;
            }
            abort_unless(in_array($payment->payment_status, [PaymentStatus::PENDING, PaymentStatus::FAILED], true), 409, 'Payment status changed.');

            $payment->payment_status = PaymentStatus::COMPLETED->value;
            $payment->transaction_id = $gatewayPayment['id'];
            $payment->paid_at = isset($gatewayPayment['created_at'])
                ? now()->setTimestamp((int) $gatewayPayment['created_at'])
                : now();
            $payment->save();

            $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);
            $booking = Booking::query()->lockForUpdate()->findOrFail($payment->booking_id);
            $this->refreshInvoiceStatus($invoice, $booking, $payment);
        });
    }

    public function publicPaymentStatus(Request $request)
    {
        $validated = $request->validate([
            'paymentId' => ['required', 'string'],
            'checkoutToken' => ['required', 'uuid'],
        ]);
        $tokenHash = hash('sha256', $validated['checkoutToken']);
        $attempt = Payment::with(['invoice.booking', 'booking'])->findOrFail($validated['paymentId']);
        abort_unless(
            $attempt->customer_id === null
                && $attempt->booking?->public_checkout_token_hash
                && hash_equals($attempt->booking->public_checkout_token_hash, $tokenHash),
            404,
            'Payment not found.'
        );

        return response()->json(['data' => $this->mapPublicPaymentStatus($attempt, $tokenHash)]);
    }

    private function mapPublicPaymentStatus(Payment $payment, string $tokenHash): array
    {
        abort_unless(
            $payment->booking?->public_checkout_token_hash
                && hash_equals($payment->booking->public_checkout_token_hash, $tokenHash),
            404,
            'Payment not found.'
        );
        $paidAmount = (int) Payment::where('invoice_id', $payment->invoice_id)
            ->where('payment_status', PaymentStatus::COMPLETED->value)
            ->sum('amount');

        return [
            'paymentId' => $payment->id,
            'paymentStatus' => $payment->payment_status instanceof \BackedEnum ? $payment->payment_status->value : (string) $payment->payment_status,
            'invoiceStatus' => $payment->invoice->status instanceof \BackedEnum ? $payment->invoice->status->value : (string) $payment->invoice->status,
            'bookingId' => $payment->booking_id,
            'bookingReference' => $payment->booking->booking_reference,
            'bookingStatus' => $payment->booking->status instanceof \BackedEnum ? $payment->booking->status->value : (string) $payment->booking->status,
            'totalAmount' => (int) $payment->invoice->total_amount,
            'paidAmount' => $paidAmount,
        ];
    }

    public function webhook(Request $request)
    {
        $rawBody = $request->getContent();
        $signature = (string) $request->header('x-razorpay-signature');
        $eventId = (string) $request->header('x-razorpay-event-id');
        $webhookSecret = config('services.razorpay.webhook_secret');
        abort_if(! $webhookSecret, 503, 'Payment webhooks are not configured.');
        abort_if(! $signature || ! $eventId, 400, 'Webhook signature and event ID are required.');

        $expectedSignature = hash_hmac('sha256', $rawBody, $webhookSecret);
        abort_unless(hash_equals($expectedSignature, $signature), 400, 'Webhook signature is invalid.');
        $body = json_decode($rawBody, true);
        abort_unless(is_array($body) && isset($body['event']), 400, 'Webhook event is invalid.');
        if (PaymentWebhookEvent::where('event_id', $eventId)->exists()) {
            return response()->json(['message' => 'Webhook already processed.']);
        }

        $eventType = $body['event'];
        $entity = $body['payload']['payment']['entity'] ?? null;
        $orderId = is_array($entity) ? ($entity['order_id'] ?? null) : null;
        $gatewayPaymentId = is_array($entity) ? ($entity['id'] ?? null) : null;

        DB::transaction(function () use ($eventId, $eventType, $orderId, $gatewayPaymentId, $entity) {
            PaymentWebhookEvent::create([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'order_id' => $orderId,
                'gateway_payment_id' => $gatewayPaymentId,
            ]);

            if (! in_array($eventType, ['payment.captured', 'payment.failed'], true) || ! is_array($entity)) {
                return;
            }
            abort_unless(isset($entity['id'], $entity['order_id'], $entity['amount'], $entity['currency']), 400, 'Webhook payment data is invalid.');
            $payment = Payment::where('order_id', $orderId)->first();
            abort_unless($payment, 404, 'Payment order not found.');
            abort_unless(
                $entity['order_id'] === $payment->order_id
                    && (int) $entity['amount'] === (int) ($payment->amount_paise ?? $payment->amount * 100)
                    && $entity['currency'] === $payment->currency,
                422,
                'Webhook payment does not match the stored order.'
            );
            abort_unless($eventType !== 'payment.captured' || ($entity['status'] ?? null) === 'captured', 409, 'Payment is still being reconciled.');
            $this->persistGatewayPayment($payment, $entity);
        });

        return response()->json(['message' => 'Webhook processed.']);
    }

    public function refund(Request $request, string $id)
    {
        [, $role] = $this->actor($request);
        abort_unless(in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value], true), 403);
        DB::transaction(function () use ($id) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($id);
            abort_unless($payment->payment_status === PaymentStatus::COMPLETED, 422, 'Only completed payments can be refunded.');
            $payment->payment_status = PaymentStatus::REFUNDED->value;
            $payment->save();
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);
            $paidAmount = (int) Payment::where('invoice_id', $invoice->id)
                ->where('payment_status', PaymentStatus::COMPLETED->value)
                ->sum('amount');
            $invoice->status = $paidAmount === 0
                ? InvoiceStatus::REFUNDED
                : ($paidAmount >= (int) $invoice->total_amount ? InvoiceStatus::PAID : InvoiceStatus::PARTIALLY_PAID);
            $invoice->save();
        });

        return response()->json(['message' => 'Payment refunded successfully.']);
    }

    private function assertPaymentInitiator(string $role, ?string $category): void
    {
        abort_unless(
            in_array($role, [UserRole::CUSTOMER->value, UserRole::ADMIN->value, UserRole::MANAGER->value], true)
                || ($role === UserRole::STAFF->value && $category === 'RECEPTIONIST'),
            403
        );
    }

    private function actor(Request $request): array
    {
        $user = $request->user();
        $role = $user?->role;
        $role = $role instanceof \BackedEnum ? $role->value : (string) $role;
        $category = $user?->category;
        $category = $category instanceof \BackedEnum ? $category->value : $category;

        return [$user, $role, $category];
    }

    private function assertPaymentReader(string $role, ?string $category): void
    {
        abort_unless(
            in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value, UserRole::CUSTOMER->value], true)
                || ($role === UserRole::STAFF->value && $category === 'RECEPTIONIST'),
            403
        );
    }

    private function getStats(string $role, ?string $category, string $userId): array
    {
        $query = Payment::query();
        if ($role === UserRole::CUSTOMER->value) {
            $query->where('customer_id', $userId);
        } elseif ($role === UserRole::STAFF->value && $category !== 'RECEPTIONIST') {
            abort(403);
        }

        $completed = (clone $query)->where('payment_status', PaymentStatus::COMPLETED->value);
        $pending = (clone $query)->where('payment_status', PaymentStatus::PENDING->value);
        $refunded = (clone $query)->where('payment_status', PaymentStatus::REFUNDED->value);
        $completedAmount = (clone $completed)->sum('amount');
        $pendingAmount = (clone $pending)->sum('amount');
        $refundedAmount = (clone $refunded)->sum('amount');

        return [
            ['id' => 'total-collected', 'icon' => 'IndianRupee', 'title' => 'Total Collected', 'label' => 'Total Collected', 'value' => '₹' . number_format($completedAmount), 'description' => 'From completed payments', 'link' => '/dashboard/payments?paymentStatus=' . PaymentStatus::COMPLETED->value, 'tone' => 'emerald'],
            ['id' => 'pending-payments', 'icon' => 'Clock', 'title' => 'Pending Payments', 'label' => 'Pending Payments', 'value' => '₹' . number_format($pendingAmount), 'description' => number_format((clone $pending)->count()) . ' awaiting confirmation', 'link' => '/dashboard/payments?paymentStatus=' . PaymentStatus::PENDING->value, 'tone' => 'gold'],
            ['id' => 'refunded-payments', 'icon' => 'RefreshCw', 'title' => 'Refunded', 'label' => 'Refunded', 'value' => '₹' . number_format($refundedAmount), 'description' => 'Total amount refunded', 'link' => '/dashboard/payments?paymentStatus=' . PaymentStatus::REFUNDED->value, 'tone' => 'rose'],
            ['id' => 'completed-payments', 'icon' => 'CheckCircle', 'title' => 'Completed Payments', 'label' => 'Completed Payments', 'value' => number_format((clone $completed)->count()), 'description' => 'Successfully collected transactions', 'link' => '/dashboard/payments?paymentStatus=' . PaymentStatus::COMPLETED->value, 'tone' => 'sky'],
        ];
    }

    private function mapPayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'bookingId' => $payment->booking_id,
            'paymentReference' => $payment->payment_reference,
            'transactionId' => $payment->transaction_id,
            'invoiceNumber' => $payment->invoice?->invoice_number ?? '',
            'bookingReference' => $payment->booking?->booking_reference ?? $payment->invoice?->booking?->booking_reference ?? '',
            'customerName' => $payment->customer?->name ?? $payment->booking?->guest_name ?? 'Guest',
            'amount' => (int) $payment->amount,
            'currency' => $payment->currency ?? 'INR',
            'paymentMethod' => $payment->payment_method instanceof \BackedEnum ? $payment->payment_method->value : (string) $payment->payment_method,
            'paymentStatus' => $payment->payment_status instanceof \BackedEnum ? $payment->payment_status->value : (string) $payment->payment_status,
            'paidAt' => $payment->paid_at?->toIso8601String(),
            'recordedByName' => $payment->receivedBy?->name,
            'createdAt' => $payment->created_at?->toIso8601String(),
        ];
    }

    private function mapCustomerPayment(Payment $payment): array
    {
        $data = $this->mapPayment($payment);

        return [
            'id' => $data['id'],
            'bookingId' => $data['bookingId'],
            'paymentReference' => $data['paymentReference'],
            'bookingReference' => $data['bookingReference'],
            'customerName' => $data['customerName'],
            'date' => $data['paidAt'] ?? $data['createdAt'],
            'invoiceNumber' => $data['invoiceNumber'],
            'transactionId' => $data['transactionId'],
            'amount' => $data['amount'],
            'paymentMethod' => $data['paymentMethod'],
            'status' => $data['paymentStatus'],
            'paidAt' => $data['paidAt'],
            'recordedByName' => $data['recordedByName'],
            'createdAt' => $data['createdAt'],
        ];
    }

    public function storeCash(Request $request)
    {
        $validated = $request->validate([
            'invoiceId' => ['required', 'string'],
            'amount' => ['nullable', 'integer', 'min:1'],
        ]);
        [, $role, $category] = $this->actor($request);
        $this->assertPaymentRecorder($role, $category);

        $result = DB::transaction(function () use ($validated, $request) {
            $invoiceReference = Invoice::findOrFail($validated['invoiceId']);
            $booking = Booking::query()->lockForUpdate()->findOrFail($invoiceReference->booking_id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceReference->id);
            $this->assertInvoicePayable($booking, PaymentMethod::CASH);

            abort_if($invoice->status === InvoiceStatus::PAID || $invoice->status === InvoiceStatus::REFUNDED, 422, 'No outstanding balance remains.');

            $paidAmount = (int) Payment::where('invoice_id', $invoice->id)
                ->where('payment_status', PaymentStatus::COMPLETED->value)
                ->sum('amount');
            $outstanding = (int) $invoice->total_amount - $paidAmount;
            abort_if($outstanding <= 0, 422, 'No outstanding balance remains.');
            if (isset($validated['amount'])) {
                abort_if((int) $validated['amount'] !== $outstanding, 422, 'Collect the full outstanding balance.');
            }
            abort_if(Payment::where('invoice_id', $invoice->id)
                ->where('payment_method', PaymentMethod::ONLINE->value)
                ->where('payment_status', PaymentStatus::PENDING->value)
                ->exists(), 409, 'An online payment is still being confirmed.');

            $payment = Payment::create([
                'payment_reference' => $this->createPaymentReference(),
                'invoice_id' => $invoice->id,
                'booking_id' => $booking->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $outstanding,
                'currency' => 'INR',
                'payment_method' => PaymentMethod::CASH->value,
                'payment_status' => PaymentStatus::COMPLETED->value,
                'paid_at' => now(),
                'recorded_by' => $request->user()->id,
            ]);

            $balance = $this->refreshInvoiceStatus($invoice, $booking, $payment);

            return $this->paymentResult($payment, $booking, $invoice, $balance);
        });

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => $result, 'message' => 'Cash payment collected.'], 201);
        }

        return redirect()->back()->with('success', 'Cash payment recorded successfully.');
    }

    public function storeQr(Request $request)
    {
        $validated = $request->validate([
            'invoiceId' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:1'],
            'transactionId' => ['required', 'string', 'max:120', Rule::unique('payments', 'transaction_id')],
        ]);
        [, $role, $category] = $this->actor($request);
        $this->assertPaymentRecorder($role, $category);

        $result = DB::transaction(function () use ($validated, $request) {
            $invoiceReference = Invoice::findOrFail($validated['invoiceId']);
            $booking = Booking::query()->lockForUpdate()->findOrFail($invoiceReference->booking_id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceReference->id);
            $this->assertInvoicePayable($booking, PaymentMethod::ONLINE);

            abort_if($invoice->status === InvoiceStatus::PAID || $invoice->status === InvoiceStatus::REFUNDED, 422, 'No outstanding balance remains.');
            $pendingAttempt = Payment::where('invoice_id', $invoice->id)
                ->where('payment_method', PaymentMethod::ONLINE->value)
                ->where('payment_status', PaymentStatus::PENDING->value)
                ->lockForUpdate()
                ->first();
            abort_if($pendingAttempt && ($pendingAttempt->order_id || $pendingAttempt->transaction_id), 409, 'An online checkout is still active.');
            abort_if(
                $pendingAttempt?->order_creation_started_at
                    && $pendingAttempt->order_creation_started_at->gt(now()->subMinutes(2)),
                409,
                'An online checkout is still being prepared.'
            );

            $paidAmount = (int) Payment::where('invoice_id', $invoice->id)
                ->where('payment_status', PaymentStatus::COMPLETED->value)
                ->sum('amount');
            $outstanding = (int) $invoice->total_amount - $paidAmount;
            abort_if($outstanding <= 0, 422, 'No outstanding balance remains.');
            abort_if((int) $validated['amount'] !== $outstanding, 422, 'The amount must match the full outstanding balance.');

            if ($pendingAttempt) {
                $payment = $pendingAttempt;
                $payment->amount = $outstanding;
                $payment->amount_paise = null;
                $payment->payment_status = PaymentStatus::COMPLETED->value;
                $payment->transaction_id = $validated['transactionId'];
                $payment->paid_at = now();
                $payment->recorded_by = $request->user()->id;
                $payment->save();
            } else {
                $payment = Payment::create([
                    'payment_reference' => $this->createPaymentReference(),
                    'invoice_id' => $invoice->id,
                    'booking_id' => $booking->id,
                    'customer_id' => $invoice->customer_id,
                    'amount' => $outstanding,
                    'currency' => 'INR',
                    'payment_method' => PaymentMethod::ONLINE->value,
                    'payment_status' => PaymentStatus::COMPLETED->value,
                    'transaction_id' => $validated['transactionId'],
                    'paid_at' => now(),
                    'recorded_by' => $request->user()->id,
                ]);
            }

            $balance = $this->refreshInvoiceStatus($invoice, $booking, $payment);

            return $this->paymentResult($payment, $booking, $invoice, $balance);
        });

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => $result, 'message' => 'QR payment recorded.'], 201);
        }

        return redirect()->back()->with('success', 'UPI payment recorded successfully.');
    }

    private function assertPaymentRecorder(string $role, ?string $category): void
    {
        abort_unless(
            in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value], true)
                || ($role === UserRole::STAFF->value && $category === 'RECEPTIONIST'),
            403
        );
    }

    private function assertInvoicePayable(Booking $booking, PaymentMethod $method, bool $allowCheckedIn = false): void
    {
        $bookingMethod = $booking->payment_method;
        abort_unless($bookingMethod === $method, 422, 'This booking is not payable with the selected method.');
        $payableStatuses = [BookingStatus::PENDING, BookingStatus::CONFIRMED];
        if ($allowCheckedIn) {
            $payableStatuses[] = BookingStatus::CHECKED_IN;
        }
        abort_unless(in_array($booking->status, $payableStatuses, true), 422, 'This booking is not payable in its current state.');
        abort_unless($booking->bookingRooms()->whereNotNull('assigned_room_id')->exists(), 422, 'An assigned room is required before payment can be recorded.');
    }

    private function refreshInvoiceStatus(Invoice $invoice, Booking $booking, Payment $payment): int
    {
        $paidAmount = (int) Payment::where('invoice_id', $invoice->id)
            ->where('payment_status', PaymentStatus::COMPLETED->value)
            ->sum('amount');
        $invoice->status = $paidAmount >= (int) $invoice->total_amount
            ? InvoiceStatus::PAID
            : ($paidAmount > 0 ? InvoiceStatus::PARTIALLY_PAID : InvoiceStatus::UNPAID);
        $invoice->save();

        $customerId = $payment->customer_id ?? $booking->customer_id;

        if ($customerId) {
            $this->notificationService->createForUsers(
                [(string) $customerId],
                NotificationType::PAYMENT_RECEIVED,
                'Payment received',
                'INR ' . number_format((int) $payment->amount, 0, '.', ',') . " received via {$payment->payment_method->value}. Reference {$payment->payment_reference} for booking {$booking->booking_reference}.",
                'PAYMENT',
                $payment->id,
            );

            $this->notificationService->createForRoles(
                [UserRole::ADMIN, UserRole::MANAGER],
                NotificationType::PAYMENT_RECEIVED,
                'Payment received',
                'INR ' . number_format((int) $payment->amount, 0, '.', ',') . " received for booking {$booking->booking_reference}.",
                'PAYMENT',
                $payment->id,
                $payment->recorded_by ? [(string) $payment->recorded_by] : [],
            );
        }

        if ($invoice->status === InvoiceStatus::PAID && $booking->status === BookingStatus::PENDING) {
            $booking->status = BookingStatus::CONFIRMED;
            $booking->save();
            if ($booking->customer_id) {
                $this->notificationService->createForUsers(
                    [(string) $booking->customer_id],
                    NotificationType::BOOKING_CONFIRMED,
                    'Booking confirmed',
                    "Booking {$booking->booking_reference} is confirmed after payment.",
                    'BOOKING',
                    $booking->id,
                );
            }
            $this->notificationService->createForRoles(
                [UserRole::ADMIN, UserRole::MANAGER],
                NotificationType::BOOKING_CONFIRMED,
                'Booking confirmed',
                "Booking {$booking->booking_reference} was confirmed after successful payment.",
                'BOOKING',
                $booking->id,
                $payment->recorded_by ? [(string) $payment->recorded_by] : [],
            );
            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->assigned_room_id) {
                    Room::whereKey($bookingRoom->assigned_room_id)
                        ->update(['occupancy_status' => OccupancyStatus::OCCUPIED->value]);
                }
            }
        }

        return $paidAmount;
    }

    private function paymentResult(Payment $payment, Booking $booking, Invoice $invoice, int $paidAmount): array
    {
        return [
            'payment' => [
                'id' => $payment->id,
                'paymentReference' => $payment->payment_reference,
                'invoiceId' => $invoice->id,
                'amount' => (int) $payment->amount,
                'paymentMethod' => $payment->payment_method->value,
                'paymentStatus' => $payment->payment_status->value,
                'paidAt' => $payment->paid_at?->toIso8601String(),
            ],
            'booking' => [
                'id' => $booking->id,
                'totalAmount' => (int) $invoice->total_amount,
                'paidAmount' => $paidAmount,
                'balance' => max((int) $invoice->total_amount - $paidAmount, 0),
            ],
        ];
    }

    private function createPaymentReference(): string
    {
        return 'PAY-' . now()->timestamp . '-' . Str::upper(Str::random(5));
    }
}
