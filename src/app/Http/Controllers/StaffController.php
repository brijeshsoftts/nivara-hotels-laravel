<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\UserRole;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $query = Staff::with(['user', 'user.avatar'])
            ->whereHas('user', fn($q) => $q->whereNull('soft_deleted_at'))
            ->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('isActive')) {
            $query->whereHas('user', fn($q) => $q->where('is_active', $request->boolean('isActive')));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%"));
        }

        $total = $query->count();
        $staff = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($st) => [
            'id' => $st->id,
            'userId' => $st->user_id,
            'name' => $st->user?->name ?? 'Staff',
            'fullName' => $st->user?->name ?? 'Staff',
            'email' => $st->user?->email,
            'phone' => $st->user?->phone,
            'category' => $st->category instanceof \BackedEnum ? $st->category->value : (string) $st->category,
            'employmentStatus' => $st->employment_status,
            'joiningDate' => $st->joining_date?->toIso8601String(),
            'salary' => $st->salary,
            'isActive' => (bool) ($st->user?->is_active ?? true),
            'avatar' => $st->user?->avatar ? [
                'id' => $st->user->avatar->id,
                'url' => $st->user->avatar->url,
            ] : null,
        ]);

        $stats = $this->getStats();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'data' => $staff,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ]);
        }

        return Inertia::render('protect/staff/index', [
            'staff' => [
                'data' => $staff,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ],
            'stats' => $stats,
            'filters' => $request->only(['category', 'isActive', 'search', 'page', 'limit']),
        ]);
    }

    public function stats()
    {
        return response()->json(['data' => $this->getStats()]);
    }

    private function getStats(): array
    {
        $visibleStaff = Staff::query()
            ->whereHas('user', fn($query) => $query->whereNull('soft_deleted_at'));
        $total = (clone $visibleStaff)->count();
        $active = (clone $visibleStaff)
            ->whereHas('user', fn($query) => $query->where('is_active', true))
            ->count();
        $inactive = (clone $visibleStaff)
            ->whereHas('user', fn($query) => $query->where('is_active', false))
            ->count();
        $categoryCounts = (clone $visibleStaff)
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');
        $topCategory = collect(Category::cases())
            ->map(fn(Category $category) => [
                'category' => $category,
                'count' => (int) $categoryCounts->get($category->value, 0),
            ])
            ->sortByDesc('count')
            ->first();

        return [
            [
                'id' => 'total-staff',
                'label' => 'Total Staff',
                'value' => number_format($total),
                'link' => '/dashboard/staff',
                'icon' => 'Users',
            ],
            [
                'id' => 'active-staff',
                'label' => 'Active',
                'value' => number_format($active),
                'link' => '/dashboard/staff?isActive=true',
                'icon' => 'UserCheck',
            ],
            [
                'id' => 'inactive-staff',
                'label' => 'Inactive',
                'value' => number_format($inactive),
                'link' => '/dashboard/staff?isActive=false',
                'icon' => 'UserX',
            ],
            [
                'id' => 'staff-' . strtolower($topCategory['category']->value),
                'label' => $this->getCategoryLabel($topCategory['category']),
                'value' => number_format($topCategory['count']),
                'link' => '/dashboard/staff?category=' . $topCategory['category']->value,
                'icon' => 'Users',
            ],
        ];
    }

    private function getCategoryLabel(Category $category): string
    {
        return match ($category) {
            Category::RECEPTIONIST => 'Receptionists',
            Category::HOUSEKEEPER => 'Housekeepers',
        };
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullName' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string'],
            'category' => ['required', 'string'],
            'password' => ['nullable', 'string', 'min:6'],
            'salary' => ['nullable', 'numeric'],
            'fatherName' => ['nullable', 'string'],
            'motherName' => ['nullable', 'string'],
        ]);

        $name = $validated['fullName'] ?? $validated['name'] ?? 'Staff Member';

        return DB::transaction(function () use ($validated, $name, $request) {
            $user = User::create([
                'name' => $name,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role' => UserRole::STAFF->value,
                'category' => $validated['category'],
                'password' => Hash::make($validated['password'] ?? 'Secret123!'),
                'is_active' => true,
            ]);

            $staff = Staff::create([
                'user_id' => $user->id,
                'category' => $validated['category'],
                'father_name' => $validated['fatherName'] ?? 'N/A',
                'mother_name' => $validated['motherName'] ?? 'N/A',
                'id_proof_number' => $validated['idProofNumber'] ?? ('ID_' . strtoupper(Str::random(8))),
                'qualification' => $validated['qualification'] ?? 'Graduate',
                'experience' => $validated['experience'] ?? '1 year',
                'emergency_contact' => $validated['emergencyContact'] ?? $validated['phone'],
                'address' => $validated['address'] ?? 'Staff Quarters',
            ]);

            if ($request->wantsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['data' => ['id' => $staff->id], 'message' => 'Staff created successfully.'], 200);
            }

            return redirect()->back()->with('success', 'Staff member created successfully.');
        });
    }

    public function show(string $id)
    {
        $st = Staff::with([
            'user.avatar',
            'user.assignedHousekeepingTasks.room',
            'idProofImage',
            'signatureImage',
        ])
            ->whereHas('user', fn($query) => $query->whereNull('soft_deleted_at'))
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $st->id,
                'staffId' => $st->id,
                'category' => $st->category instanceof \BackedEnum ? $st->category->value : (string) $st->category,
                'fatherName' => $st->father_name,
                'motherName' => $st->mother_name,
                'idProofNumber' => $st->id_proof_number,
                'qualification' => $st->qualification,
                'experience' => $st->experience,
                'emergencyContact' => $st->emergency_contact,
                'address' => $st->address,
                'createdAt' => $st->created_at?->toIso8601String(),
                'idProofImage' => $st->idProofImage ? [
                    'url' => $st->idProofImage->url,
                    'altText' => $st->idProofImage->alt_text,
                ] : null,
                'signatureImage' => $st->signatureImage ? [
                    'url' => $st->signatureImage->url,
                    'altText' => $st->signatureImage->alt_text,
                ] : null,
                'user' => [
                    'id' => $st->user->id,
                    'fullName' => $st->user->name,
                    'email' => $st->user->email,
                    'phone' => $st->user->phone,
                    'isActive' => (bool) $st->user->is_active,
                    'lastLoginAt' => $st->user->last_login_at?->toIso8601String(),
                    'profileImage' => $st->user->avatar ? [
                        'url' => $st->user->avatar->url,
                        'altText' => $st->user->avatar->alt_text,
                    ] : null,
                ],
                'assignedTasks' => $st->user->assignedHousekeepingTasks
                    ->map(fn($task) => [
                        'id' => $task->id,
                        'taskType' => $task->task_type instanceof \BackedEnum ? $task->task_type->value : (string) $task->task_type,
                        'status' => $task->status instanceof \BackedEnum ? $task->status->value : (string) $task->status,
                        'scheduledDate' => $task->scheduled_date?->toIso8601String() ?? '',
                        'room' => [
                            'id' => $task->room?->id ?? '',
                            'roomNumber' => $task->room?->room_number ?? '',
                            'name' => $task->room?->name,
                        ],
                    ])
                    ->values(),
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $staff = Staff::findOrFail($id);
        $validated = $request->validate([
            'category' => ['nullable', 'string'],
            'salary' => ['nullable', 'numeric'],
            'employmentStatus' => ['nullable', 'string'],
            'fullName' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string'],
        ]);

        if (isset($validated['category'])) {
            $staff->category = $validated['category'];
        }
        if (isset($validated['salary'])) {
            $staff->salary = $validated['salary'];
        }
        if (isset($validated['employmentStatus'])) {
            $staff->employment_status = $validated['employmentStatus'];
        }
        $staff->save();

        if ($staff->user) {
            if (isset($validated['category'])) {
                $staff->user->category = $validated['category'];
            }
            if (isset($validated['fullName'])) {
                $staff->user->name = $validated['fullName'];
            }
            if (isset($validated['phone'])) {
                $staff->user->phone = $validated['phone'];
            }
            $staff->user->save();
        }

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $staff->id], 'message' => 'Staff updated successfully']);
        }

        return redirect()->back()->with('success', 'Staff member updated successfully.');
    }

    public function toggleStatus(Request $request, string $id)
    {
        $staff = Staff::findOrFail($id);
        if ($staff->user) {
            $staff->user->update(['is_active' => ! $staff->user->is_active]);
        }

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $staff->id]]);
        }

        return redirect()->back()->with('success', 'Staff status updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $staff = Staff::findOrFail($id);
        $user = $staff->user;
        $staff->delete();
        $user?->delete();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => 'Staff deleted successfully']);
        }

        return redirect()->back()->with('success', 'Staff member deleted successfully.');
    }
}
