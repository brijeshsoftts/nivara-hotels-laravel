<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Image;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CustomerController extends Controller
{
    private const ACTIVE_BOOKING_STATUSES = ['PENDING', 'CONFIRMED', 'CHECKED_IN'];

    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $query = User::with(['avatar', 'customer.idProofImage', 'customer.signatureImage', 'bookings'])
            ->where('role', UserRole::CUSTOMER->value)
            ->whereNull('soft_deleted_at')
            ->orderByDesc('created_at');

        if ($request->filled('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
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
                $query->where('created_at', '>=', $cutoff);
            }
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('id_proof_number', 'like', "%{$s}%"));
            });
        }

        $total = $query->count();
        $customers = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($u) => [
            'id' => $u->id,
            'userId' => $u->id,
            'name' => $u->name ?? 'Guest',
            'email' => $u->email,
            'phone' => $u->phone,
            'idProofNumber' => $u->customer?->id_proof_number,
            'address' => $u->customer?->address,
            'idProof' => $u->customer?->idProofImage ? [
                'url' => $u->customer->idProofImage->url,
                'altText' => $u->customer->idProofImage->alt_text,
            ] : null,
            'signature' => $u->customer?->signatureImage ? [
                'url' => $u->customer->signatureImage->url,
                'altText' => $u->customer->signatureImage->alt_text,
            ] : null,
            'totalBookings' => $u->bookings->count(),
            'activeBooking' => $u->bookings->contains(fn($booking) => in_array(
                $booking->status instanceof \BackedEnum ? $booking->status->value : (string) $booking->status,
                self::ACTIVE_BOOKING_STATUSES,
                true,
            )),
            'totalSpent' => $u->bookings->sum('total_amount'),
            'lastLogin' => $u->last_login_at?->toIso8601String(),
            'lastLoginAt' => $u->last_login_at?->toIso8601String(),
            'isActive' => (bool) $u->is_active,
            'profile' => $u->avatar ? [
                'url' => $u->avatar->url,
                'altText' => $u->avatar->alt_text,
            ] : null,
            'createdAt' => $u->created_at?->toIso8601String(),
            'updatedAt' => $u->updated_at?->toIso8601String(),
        ]);

        $stats = $this->getStats();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
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
                'link' => '/dashboard/customers?isActive=true',
            ],
            [
                'id' => 'customers-with-active-bookings',
                'title' => 'With Active Bookings',
                'label' => 'With Active Bookings',
                'value' => number_format($withActiveBooking),
                'description' => 'Pending, confirmed, or checked in',
                'tone' => 'sky',
                'icon' => 'CalendarCheck',
                'link' => '/dashboard/customers?hasActiveBooking=true',
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
        $s = trim((string) $request->input('q', $request->input('search', '')));
        $customers = User::with('customer')
            ->where('role', UserRole::CUSTOMER->value)
            ->whereNull('soft_deleted_at')
            ->when($s !== '', function ($query) use ($s) {
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%")
                        ->orWhereHas('customer', fn($cq) => $cq->where('id_proof_number', 'like', "%{$s}%"));
                });
            })
            ->orderBy('name')
            ->take(20)
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name ?? 'Guest',
                'email' => $u->email,
                'phone' => $u->phone,
                'idProofNumber' => $u->customer?->id_proof_number,
            ]);

        return response()->json(['data' => $customers]);
    }

    public function show(string $id)
    {
        $u = User::with(['avatar', 'bookings', 'customer.idProofImage', 'customer.signatureImage'])
            ->where('role', UserRole::CUSTOMER->value)
            ->whereNull('soft_deleted_at')
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $u->id,
                'userId' => $u->id,
                'name' => $u->name ?? 'Guest',
                'email' => $u->email,
                'phone' => $u->phone,
                'idProofNumber' => $u->customer?->id_proof_number,
                'address' => $u->customer?->address,
                'idProof' => $u->customer?->idProofImage ? [
                    'url' => $u->customer->idProofImage->url,
                    'altText' => $u->customer->idProofImage->alt_text,
                ] : null,
                'signature' => $u->customer?->signatureImage ? [
                    'url' => $u->customer->signatureImage->url,
                    'altText' => $u->customer->signatureImage->alt_text,
                ] : null,
                'isActive' => (bool) $u->is_active,
                'profile' => $u->avatar ? [
                    'url' => $u->avatar->url,
                    'altText' => $u->avatar->alt_text,
                ] : null,
                'totalBookings' => $u->bookings->count(),
                'lastLoginAt' => $u->last_login_at?->toIso8601String(),
                'createdAt' => $u->created_at?->toIso8601String(),
                'updatedAt' => $u->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function customerBookings(string $id)
    {
        $u = User::where('role', UserRole::CUSTOMER->value)
            ->whereNull('soft_deleted_at')
            ->findOrFail($id);
        $bookings = Booking::where('customer_id', $u->id)->get();

        return response()->json(['data' => $bookings]);
    }

    public function customerPayments(string $id)
    {
        $u = User::where('role', UserRole::CUSTOMER->value)
            ->whereNull('soft_deleted_at')
            ->findOrFail($id);
        $payments = Payment::where('customer_id', $u->id)->get();

        return response()->json(['data' => $payments]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'idProofNumber' => ['nullable', 'string', 'max:100'],
            'idProofImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'signatureImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'city' => ['nullable', 'string'],
        ]);

        $name = $validated['name'] ?? 'Guest';
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($validated, $name, $request, &$storedPaths) {
                $user = User::firstOrCreate(
                    ['email' => $validated['email']],
                    [
                        'name' => $name,
                        'phone' => $validated['phone'],
                        'role' => UserRole::CUSTOMER->value,
                        'password' => Hash::make('password'),
                        'is_active' => true,
                    ]
                );

                $idProofImageId = null;
                if ($request->hasFile('idProofImage')) {
                    [$idProofImageId, $path] = $this->storeUploadedImage(
                        $request->file('idProofImage'),
                        'customers/id-proofs',
                        "{$name} ID proof"
                    );
                    $storedPaths[] = $path;
                }

                $signatureImageId = null;
                if ($request->hasFile('signatureImage')) {
                    [$signatureImageId, $path] = $this->storeUploadedImage(
                        $request->file('signatureImage'),
                        'customers/signatures',
                        "{$name} signature"
                    );
                    $storedPaths[] = $path;
                }

                $customer = Customer::create([
                    'user_id' => $user->id,
                    'id_proof_number' => $validated['idProofNumber'] ?? ('ID_' . strtoupper(Str::random(8))),
                    'id_proof_image_id' => $idProofImageId,
                    'signature_image_id' => $signatureImageId,
                    'address' => $validated['address'] ?? ($validated['city'] ?? 'Nivara Residence'),
                ]);

                if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                    return response()->json(['data' => ['id' => $user->id], 'message' => 'Customer created successfully.'], 200);
                }

                return redirect()->back()->with('success', 'Customer registered successfully.');
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }
    }

    public function update(Request $request, string $id)
    {
        $user = User::with(['customer.idProofImage', 'customer.signatureImage'])
            ->where('role', UserRole::CUSTOMER->value)
            ->whereNull('soft_deleted_at')
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'idProofNumber' => ['nullable', 'string', 'max:100'],
            'idProofImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'signatureImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $storedPaths = [];
        $oldImages = [];

        try {
            DB::transaction(function () use ($user, $validated, $request, &$storedPaths, &$oldImages): void {
                foreach (['name', 'email', 'phone'] as $userField) {
                    if (array_key_exists($userField, $validated) && $validated[$userField] !== null) {
                        $user->{$userField} = $validated[$userField];
                    }
                }
                $user->save();

                $customer = $user->customer ?? new Customer([
                    'user_id' => $user->id,
                    'id_proof_number' => 'ID_' . strtoupper(Str::random(8)),
                    'address' => 'Nivara Residence',
                ]);

                if (array_key_exists('idProofNumber', $validated) && $validated['idProofNumber'] !== null) {
                    $customer->id_proof_number = $validated['idProofNumber'];
                }
                if (array_key_exists('address', $validated) && $validated['address'] !== null) {
                    $customer->address = $validated['address'];
                }

                $displayName = $user->name ?? 'Guest';

                if ($request->hasFile('idProofImage')) {
                    if ($customer->idProofImage) {
                        $oldImages[] = $customer->idProofImage;
                    }
                    [$idProofImageId, $path] = $this->storeUploadedImage(
                        $request->file('idProofImage'),
                        'customers/id-proofs',
                        "{$displayName} ID proof"
                    );
                    $storedPaths[] = $path;
                    $customer->id_proof_image_id = $idProofImageId;
                }

                if ($request->hasFile('signatureImage')) {
                    if ($customer->signatureImage) {
                        $oldImages[] = $customer->signatureImage;
                    }
                    [$signatureImageId, $path] = $this->storeUploadedImage(
                        $request->file('signatureImage'),
                        'customers/signatures',
                        "{$displayName} signature"
                    );
                    $storedPaths[] = $path;
                    $customer->signature_image_id = $signatureImageId;
                }

                $customer->save();

                foreach ($oldImages as $oldImage) {
                    $oldImage->delete();
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        foreach ($oldImages as $oldImage) {
            $this->deleteStoredImage($oldImage);
        }

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $user->id], 'message' => 'Customer updated successfully']);
        }

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    public function toggleStatus(Request $request, string $id)
    {
        $user = User::where('role', UserRole::CUSTOMER->value)
            ->whereNull('soft_deleted_at')
            ->findOrFail($id);

        $user->update(['is_active' => ! $user->is_active]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $user->id, 'isActive' => (bool) $user->is_active]]);
        }

        return redirect()->back()->with('success', 'Customer status updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $user = User::where('role', UserRole::CUSTOMER->value)
            ->whereNull('soft_deleted_at')
            ->findOrFail($id);

        $user->update([
            'is_active' => false,
            'soft_deleted_at' => now(),
        ]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => 'Customer deleted successfully']);
        }

        return redirect()->back()->with('success', 'Customer deleted successfully.');
    }

    /**
     * Store an uploaded customer document image and create its Image record.
     *
     * @return array{0: string, 1: string}
     */
    private function storeUploadedImage(UploadedFile $file, string $directory, string $altText): array
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $path = $file->storePublicly($directory, 'public');
        $image = Image::create([
            'url' => $publicDisk->url($path),
            'alt_text' => $altText,
        ]);

        return [$image->id, $path];
    }

    private function deleteStoredImage(Image $image): void
    {
        $path = parse_url($image->url, PHP_URL_PATH);
        if (is_string($path) && str_starts_with($path, '/storage/')) {
            Storage::disk('public')->delete(substr($path, strlen('/storage/')));
        }
    }
}
