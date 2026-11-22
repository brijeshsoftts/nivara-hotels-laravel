<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 15);
        $query = Inquiry::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('subject', 'like', "%{$s}%"));
        }

        $total = $query->count();
        $inquiries = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($inq) => [
            'id' => $inq->id,
            'name' => $inq->name,
            'email' => $inq->email,
            'phone' => $inq->phone,
            'subject' => $inq->subject,
            'message' => $inq->message,
            'status' => $inq->status,
            'createdAt' => $inq->created_at?->toIso8601String(),
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => $inquiries,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ]);
        }

        return Inertia::render('protect/inquiries/index', [
            'inquiries' => [
                'data' => $inquiries,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ],
            'filters' => $request->only(['status', 'search', 'page', 'limit']),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $inquiry = Inquiry::findOrFail($id);
        $inquiry->delete();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Inquiry deleted successfully']);
        }

        return redirect()->back()->with('success', 'Inquiry deleted successfully.');
    }
}

