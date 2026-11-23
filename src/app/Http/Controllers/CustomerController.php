<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
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
    private const ACTIVE_BOOKING_STATUSES = ['PENDING', 'CONFIRMED', 'CHECKED_IN'];

    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $query = Customer::with(['user.avatar', 'bookings'])
            ->whereHas('user', fn($q) => $q
                ->where('role', UserRole::CUSTOMER->value)
                ->whereNull('soft_deleted_at'))
            ->orderByDesc('created_at');

        if ($request->filled('isActive')) {
            $query->whereHas('user', fn($q) => $q->where('is_active', $request->boolean('isActive')));
        }
        if ($request->filled('hasActiveBooking')) {
            $bookingFilter = fn($q) => $q->whereIn('status', self::ACTIVE_BOOKING_STATUSES);
            $request->boolean('hasActiveBooking')
                ? $query->whereHas('bookings', $bookingFilter)
                : $query->whereDoesntHave('bookings', $bookingFilter);
        }
        if ($request->filled('createdWithinDays')) {
            $days = filter_var($request->input('createdWithinDays'), FILTER_VALIDATE_INT);
            if ($days !== false && $days > 0) {
                $cutoff = now()->subDays(min($days, 3650));
                $query->whereHas('user', fn($q) => $q->where('created_at', '>=', $cutoff));
            }
        }

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
            'activeBooking' => $c->bookings->contains(fn($booking) => in_array(
                $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status,
                self::ACTIVE_BOOKING_STATUSES,
                true,
            )),
            'totalSpent' => $c->bookings->sum('total_amount'),
            'lastLogin' => $c->user?->last_login_at?->toIso8601String(),
            'isActive' => (bool) ($c->user?->is_active ?? false),
            'profile' => $c->user?->avatar ? [
                'url' => $c->user->avatar->url,
                'altText' => $c->user->avatar->alt_text,
            ] : null,
            'createdAt' => $c->created_at?->toIso8601String(),
        ]);

        $stats = $this->getStats();

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
            'filters' => $request->only(['search', 'isActive', 'hasActiveBooking', 'createdWithinDays', 'page', 'limit']),
        ]);
    }

    public function stats()
    {
        return response()->json(['data' => $this->getStats()]);
    }

    private function getStats(): array
    {
        $customerQuery = fn() => User::where('role', UserRole::CUSTOMER->value)
            ->whereNull('soft_deleted_at');
        $activeBookingStatuses = [
            BookingStatus::PENDING->value,
            BookingStatus::CONFIRMED->value,
            BookingStatus::CHECKED_IN->value,
        ];
        $total = $customerQuery()->count();
        $active = $customerQuery()->where('is_active', true)->count();
        $withActiveBooking = $customerQuery()
            ->whereHas('bookings', fn($query) => $query->whereIn('status', $activeBookingStatuses))
            ->count();
        $newCustomers = $customerQuery()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            [
                'id' => 'total-customer',
                'title' => 'Total Customers',
                'label' => 'Total Customers',
                'value' => number_format($total),
                'description' => 'Registered customer accounts',
                'tone' => 'gold',
                'icon' => 'Users',
                'link' => '/dashboard/customers',
            ],
            [
                'id' => 'active-customer',
                'title' => 'Active Customers',
                'label' => 'Active Customers',
                'value' => number_format($active),
                'description' => 'Accounts currently active',
                'tone' => 'emerald',
                'icon' => 'UserCheck',
                'link' => '/dashboard/customers?isActive=1',
            ],
            [
                'id' => 'customers-with-active-bookings',
                'title' => 'With Active Bookings',
                'label' => 'With Active Bookings',
                'value' => number_format($withActiveBooking),
                'description' => 'Pending, confirmed, or checked in',
                'tone' => 'sky',
                'icon' => 'CalendarCheck',
                'link' => '/dashboard/customers?hasActiveBooking=1',
            ],
            [
                'id' => 'new-customer',
                'title' => 'New This Month',
                'label' => 'New This Month',
                'value' => number_format($newCustomers),
                'description' => 'Joined in the last 30 days',
                'tone' => 'violet',
                'icon' => 'UserPlus',
                'link' => '/dashboard/customers?createdWithinDays=30',
            ],
        ];
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
