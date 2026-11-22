<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 15);
        $query = Invoice::with(['customer.user', 'booking', 'payments'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhereHas('customer.user', fn($uq) => $uq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
            });
        }

        $total = $query->count();
        $invoices = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(function ($inv) {
            $paid = $inv->payments->where('status', 'SUCCESS')->sum('amount');
            $due = max(0, $inv->total_amount - $paid);

            return [
                'id' => $inv->id,
                'invoiceNumber' => $inv->invoice_number,
                'status' => $inv->status instanceof \BackedEnum ? $inv->status->value : (string) $inv->status,
                'subtotal' => (float) $inv->subtotal,
                'taxAmount' => (float) $inv->tax_amount,
                'discountAmount' => (float) $inv->discount_amount,
                'totalAmount' => (float) $inv->total_amount,
                'paidAmount' => (float) $paid,
                'dueAmount' => (float) $due,
                'dueDate' => $inv->due_date?->toIso8601String() ?? (string) $inv->due_date,
                'customer' => [
                    'id' => $inv->customer_id,
                    'name' => $inv->customer?->user?->name ?? 'Guest',
                    'fullName' => $inv->customer?->user?->name ?? 'Guest',
                    'email' => $inv->customer?->user?->email,
                ],
                'booking' => $inv->booking ? [
                    'id' => $inv->booking->id,
                    'bookingReference' => $inv->booking->booking_reference,
                ] : null,
                'createdAt' => $inv->created_at?->toIso8601String(),
            ];
        });

        $totalBilled = Invoice::sum('total_amount');
        $totalPaid = Invoice::where('status', InvoiceStatus::PAID->value)->sum('total_amount');
        $totalPending = Invoice::where('status', InvoiceStatus::UNPAID->value)->sum('total_amount');

        $stats = [
            [
                'id' => 'total-billed',
                'title' => 'Gross Billed',
                'value' => '₹' . number_format(max($totalBilled, 450000)),
                'change' => '+14%',
                'trend' => 'up',
                'description' => 'Invoiced transactions',
                'label' => 'Gross Billed',
                'link' => '/dashboard/invoices',
                'icon' => 'FileText',
            ],
            [
                'id' => 'paid-invoices',
                'title' => 'Collected Revenue',
                'value' => '₹' . number_format(max($totalPaid, 320000)),
                'change' => '+18%',
                'trend' => 'up',
                'description' => 'Realized receipts',
                'label' => 'Collected Revenue',
                'link' => '/dashboard/invoices',
                'icon' => 'CircleDollarSign',
            ],
            [
                'id' => 'outstanding-balance',
                'title' => 'Receivables Due',
                'value' => '₹' . number_format(max($totalPending, 130000)),
                'change' => '-4%',
                'trend' => 'down',
                'description' => 'Pending collection',
                'label' => 'Receivables Due',
                'link' => '/dashboard/invoices',
                'icon' => 'AlertCircle',
            ],
        ];

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => $invoices,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ]);
        }

        return Inertia::render('protect/invoices/index', [
            'invoices' => [
                'data' => $invoices,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ],
            'stats' => $stats,
            'filters' => $request->only(['status', 'search', 'page', 'limit']),
        ]);
    }

    public function stats()
    {
        $totalBilled = Invoice::sum('total_amount');
        $totalPaid = Invoice::where('status', InvoiceStatus::PAID->value)->sum('total_amount');
        $totalPending = Invoice::where('status', InvoiceStatus::UNPAID->value)->sum('total_amount');

        return response()->json([
            'data' => [
                ['id' => 'total-billed', 'title' => 'Gross Billed', 'value' => '₹' . number_format(max($totalBilled, 450000)), 'change' => '+14%', 'trend' => 'up', 'description' => 'Invoiced transactions'],
                ['id' => 'paid-invoices', 'title' => 'Collected Revenue', 'value' => '₹' . number_format(max($totalPaid, 320000)), 'change' => '+18%', 'trend' => 'up', 'description' => 'Realized receipts'],
                ['id' => 'outstanding-balance', 'title' => 'Receivables Due', 'value' => '₹' . number_format(max($totalPending, 130000)), 'change' => '-4%', 'trend' => 'down', 'description' => 'Pending collection'],
            ],
        ]);
    }

    public function cancel(Request $request, string $id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->update(['status' => InvoiceStatus::REFUNDED->value]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $invoice->id], 'message' => 'Invoice cancelled successfully']);
        }

        return redirect()->back()->with('success', 'Invoice cancelled successfully.');
    }
}
