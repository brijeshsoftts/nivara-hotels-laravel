<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $query = Customer::with(['user', 'bookings'])->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('id_proof_number', 'like', "%{$s}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%"));
            });
        }

        $total = $query->count();
        $customers = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($c) => [
            'id' => $c->id,
            'userId' => $c->user_id,
            'name' => $c->user?->name ?? 'Guest',
            'fullName' => $c->user?->full_name ?? $c->user?->name ?? 'Guest',
            'email' => $c->user?->email,
            'phone' => $c->user?->phone,
            'idProofType' => $c->id_proof_type,
            'idProofNumber' => $c->id_proof_number,
            'city' => $c->city,
            'state' => $c->state,
            'country' => $c->country ?? 'India',
            'status' => $c->status,
            'totalBookings' => $c->bookings->count(),
            'createdAt' => $c->created_at?->toIso8601String(),
        ]);

        $stats = [
            [
                'id' => 'total-customers',
                'title' => 'Registered Guests',
                'value' => (string) max(Customer::count(), 5),
                'change' => '+14%',
                'trend' => 'up',
                'description' => 'Guest accounts',
                'label' => 'Registered Guests',
                'link' => '/dashboard/customers',
                'icon' => 'UserCheck',
            ],
            [
                'id' => 'active-stays',
                'title' => 'Active Bookings',
                'value' => (string) max(Customer::has('bookings')->count(), 3),
                'change' => '+8%',
                'trend' => 'up',
                'description' => 'Current or past bookings',
                'label' => 'Active Bookings',
                'link' => '/dashboard/bookings',
                'icon' => 'CalendarCheck',
            ],
        ];

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => $customers,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ]);
        }

        return Inertia::render('protect/customers/index', [
            'customers' => [
                'data' => $customers,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ],
            'stats' => $stats,
            'filters' => $request->only(['search', 'page', 'limit']),
        ]);
    }

    public function stats()
    {
        return response()->json([
            'data' => [
                ['id' => 'total-customers', 'title' => 'Registered Guests', 'value' => (string) max(Customer::count(), 5), 'change' => '+14%', 'trend' => 'up', 'description' => 'Guest accounts'],
                ['id' => 'active-stays', 'title' => 'Active Bookings', 'value' => (string) max(Customer::has('bookings')->count(), 3), 'change' => '+8%', 'trend' => 'up', 'description' => 'Current or past bookings'],
            ],
        ]);
    }

    public function search(Request $request)
    {
        $s = $request->input('q', $request->input('search', ''));
        $customers = Customer::with('user')
            ->where(function ($query) use ($s) {
                $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
                    ->orWhere('id_proof_number', 'like', "%{$s}%");
            })
            ->take(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'fullName' => $c->user?->name ?? 'Guest',
                'email' => $c->user?->email,
                'phone' => $c->user?->phone,
                'idProofType' => $c->id_proof_type,
                'idProofNumber' => $c->id_proof_number,
            ]);

        return response()->json(['data' => $customers]);
    }

    public function show(string $id)
    {
        $c = Customer::with(['user', 'bookings'])->findOrFail($id);
        return response()->json([
            'data' => [
                'id' => $c->id,
                'userId' => $c->user_id,
                'name' => $c->user?->name ?? 'Guest',
                'fullName' => $c->user?->full_name ?? $c->user?->name ?? 'Guest',
                'email' => $c->user?->email,
                'phone' => $c->user?->phone,
                'idProofType' => $c->id_proof_type,
                'idProofNumber' => $c->id_proof_number,
                'city' => $c->city,
                'state' => $c->state,
                'country' => $c->country ?? 'India',
                'status' => $c->status,
                'totalBookings' => $c->bookings->count(),
                'createdAt' => $c->created_at?->toIso8601String(),
            ]
        ]);
    }

    public function customerBookings(string $id)
    {
        $c = Customer::findOrFail($id);
        $bookings = \App\Models\Booking::where('customer_id', $c->user_id)->get();
        return response()->json(['data' => $bookings]);
    }

    public function customerPayments(string $id)
    {
        $c = Customer::findOrFail($id);
        $payments = \App\Models\Payment::where('customer_id', $c->user_id)->get();
        return response()->json(['data' => $payments]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullName' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
            'idProofType' => ['nullable', 'string'],
            'idProofNumber' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
        ]);

        $name = $validated['fullName'] ?? $validated['name'] ?? 'Guest';

        return DB::transaction(function () use ($validated, $name, $request) {
            $user = User::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $name,
                    'full_name' => $name,
                    'phone' => $validated['phone'],
                    'role' => UserRole::CUSTOMER->value,
                    'password' => Hash::make(Str::random(16)),
                    'password_hash' => Hash::make(Str::random(16)),
                    'is_active' => true,
                ]
            );

            $customer = Customer::create([
                'user_id' => $user->id,
                'id_proof_number' => $validated['idProofNumber'] ?? ('ID_' . strtoupper(Str::random(8))),
                'address' => $validated['address'] ?? ($validated['city'] ?? 'Nivara Residence'),
            ]);

            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['data' => ['id' => $customer->id], 'message' => 'Customer created successfully.'], 200);
            }

            return redirect()->back()->with('success', 'Customer registered successfully.');
        });
    }

    public function update(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);
        $validated = $request->validate([
            'fullName' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'idProofType' => ['nullable', 'string'],
            'idProofNumber' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
        ]);

        if (isset($validated['idProofType'])) $customer->id_proof_type = $validated['idProofType'];
        if (isset($validated['idProofNumber'])) $customer->id_proof_number = $validated['idProofNumber'];
        if (isset($validated['city'])) $customer->city = $validated['city'];
        if (isset($validated['state'])) $customer->state = $validated['state'];
        $customer->save();

        if ($customer->user) {
            if (isset($validated['fullName'])) {
                $customer->user->full_name = $validated['fullName'];
                $customer->user->name = $validated['fullName'];
            }
            if (isset($validated['phone'])) $customer->user->phone = $validated['phone'];
            $customer->user->save();
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $customer->id], 'message' => 'Customer updated successfully']);
        }

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Customer deleted successfully']);
        }

        return redirect()->back()->with('success', 'Customer deleted successfully.');
    }
}

