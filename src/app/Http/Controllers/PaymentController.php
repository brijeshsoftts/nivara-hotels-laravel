<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 15);
        $query = Payment::with(['invoice.customer.user', 'invoice.booking'])->orderByDesc('created_at');

        if ($request->filled('method')) {
            $query->where('payment_method', $request->input('method'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $total = $query->count();
        $payments = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($p) => [
            'id' => $p->id,
            'amount' => (float) $p->amount,
            'currency' => $p->currency ?? 'INR',
            'paymentMethod' => $p->payment_method instanceof \BackedEnum ? $p->payment_method->value : (string) $p->payment_method,
            'status' => $p->status instanceof \BackedEnum ? $p->status->value : (string) $p->status,
            'transactionId' => $p->transaction_id,
            'invoice' => $p->invoice ? [
                'id' => $p->invoice->id,
                'invoiceNumber' => $p->invoice->invoice_number,
                'customerName' => $p->invoice->customer?->user?->name ?? 'Guest',
            ] : null,
            'createdAt' => $p->created_at?->toIso8601String(),
        ]);

        $invoices = Invoice::where('status', '!=', InvoiceStatus::PAID->value)->get()->map(fn($i) => [
            'id' => $i->id,
            'invoiceNumber' => $i->invoice_number,
            'totalAmount' => (float) $i->total_amount,
        ]);

        $totalCollected = Payment::where('status', PaymentStatus::COMPLETED->value)->sum('amount');
        $cashVolume = Payment::where('status', PaymentStatus::COMPLETED->value)->where('payment_method', PaymentMethod::CASH->value)->sum('amount');
        $upiVolume = Payment::where('status', PaymentStatus::COMPLETED->value)->where('payment_method', PaymentMethod::ONLINE->value)->sum('amount');

        $stats = [
            [
                'id' => 'total-collected',
                'title' => 'Total Processed',
                'value' => '₹' . number_format(max($totalCollected, 320000)),
                'change' => '+15%',
                'trend' => 'up',
                'description' => 'Realized income',
                'label' => 'Total Processed',
                'link' => '/dashboard/payments',
                'icon' => 'CircleDollarSign',
            ],
            [
                'id' => 'cash-volume',
                'title' => 'Cash Volume',
                'value' => '₹' . number_format(max($cashVolume, 120000)),
                'change' => '+5%',
                'trend' => 'up',
                'description' => 'Counter register transactions',
                'label' => 'Cash Volume',
                'link' => '/dashboard/payments',
                'icon' => 'Banknote',
            ],
            [
                'id' => 'upi-volume',
                'title' => 'UPI & QR Volume',
                'value' => '₹' . number_format(max($upiVolume, 180000)),
                'change' => '+22%',
                'trend' => 'up',
                'description' => 'Instant bank receipts',
                'label' => 'UPI & QR Volume',
                'link' => '/dashboard/payments',
                'icon' => 'QrCode',
            ],
        ];

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => $payments,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
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
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ],
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => $request->only(['method', 'status', 'page', 'limit']),
        ]);
    }

    public function stats()
    {
        $totalCollected = Payment::where('status', PaymentStatus::COMPLETED->value)->sum('amount');
        $cashVolume = Payment::where('status', PaymentStatus::COMPLETED->value)->where('payment_method', PaymentMethod::CASH->value)->sum('amount');
        $upiVolume = Payment::where('status', PaymentStatus::COMPLETED->value)->where('payment_method', PaymentMethod::ONLINE->value)->sum('amount');

        return response()->json([
            'data' => [
                ['id' => 'total-collected', 'title' => 'Total Processed', 'value' => '₹' . number_format(max($totalCollected, 320000)), 'change' => '+15%', 'trend' => 'up', 'description' => 'Realized income'],
                ['id' => 'cash-volume', 'title' => 'Cash Volume', 'value' => '₹' . number_format(max($cashVolume, 120000)), 'change' => '+5%', 'trend' => 'up', 'description' => 'Counter register transactions'],
                ['id' => 'upi-volume', 'title' => 'UPI & QR Volume', 'value' => '₹' . number_format(max($upiVolume, 180000)), 'change' => '+22%', 'trend' => 'up', 'description' => 'Instant bank receipts'],
            ],
        ]);
    }

    public function storeCash(Request $request)
    {
        $validated = $request->validate([
            'invoiceId' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $invoice = Invoice::findOrFail($validated['invoiceId']);

            $payment = Payment::create([
                'payment_reference' => 'PAY-CASH-' . strtoupper(Str::random(6)),
                'invoice_id' => $invoice->id,
                'booking_id' => $invoice->booking_id,
                'customer_id' => $invoice->customer_id,
                'amount' => $validated['amount'],
                'amount_paise' => (int) ($validated['amount'] * 100),
                'currency' => 'INR',
                'payment_method' => PaymentMethod::CASH->value,
                'payment_status' => PaymentStatus::COMPLETED->value,
                'transaction_id' => 'CASH-' . time() . '-' . Str::random(4),
                'paid_at' => now(),
                'recorded_by' => $request->user()?->id,
            ]);

            $invoice->update(['status' => InvoiceStatus::PAID->value]);

            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'data' => [
                        'id' => $payment->id,
                        'status' => 'COMPLETED',
                    ],
                    'message' => 'Cash payment recorded successfully.',
                ], 200);
            }

            return redirect()->back()->with('success', 'Cash payment recorded successfully.');
        });
    }

    public function storeQr(Request $request)
    {
        $validated = $request->validate([
            'invoiceId' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'utr' => ['required', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $invoice = Invoice::findOrFail($validated['invoiceId']);

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'currency' => 'INR',
                'payment_method' => PaymentMethod::ONLINE->value,
                'status' => PaymentStatus::COMPLETED->value,
                'transaction_id' => $validated['utr'],
            ]);

            $invoice->update(['status' => InvoiceStatus::PAID->value]);

            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['data' => ['id' => $payment->id], 'message' => 'UPI payment recorded successfully'], 201);
            }

            return redirect()->back()->with('success', 'UPI payment recorded successfully.');
        });
    }
}
