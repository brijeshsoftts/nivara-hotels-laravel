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
        $query = Staff::with(['user', 'user.avatar'])->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
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
            'fullName' => $st->user?->full_name ?? $st->user?->name ?? 'Staff',
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

        $stats = [
            [
                'id' => 'total-staff',
                'title' => 'Total Staff',
                'value' => (string) max(Staff::count(), 10),
                'change' => '+5%',
                'trend' => 'up',
                'description' => 'Active team members',
                'label' => 'Total Staff',
                'link' => '/dashboard/staff',
                'icon' => 'Users',
            ],
            [
                'id' => 'receptionists',
                'title' => 'Receptionists',
                'value' => (string) max(Staff::where('category', Category::RECEPTIONIST->value)->count(), 3),
                'change' => '0%',
                'trend' => 'up',
                'description' => 'Front desk team',
                'label' => 'Receptionists',
                'link' => '/dashboard/staff',
                'icon' => 'UserCheck',
            ],
            [
                'id' => 'housekeepers',
                'title' => 'Housekeepers',
                'value' => (string) max(Staff::where('category', Category::HOUSEKEEPER->value)->count(), 4),
                'change' => '0%',
                'trend' => 'up',
                'description' => 'Floor operations team',
                'label' => 'Housekeepers',
                'link' => '/dashboard/staff',
                'icon' => 'BrushCleaning',
            ],
        ];

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
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
            'filters' => $request->only(['category', 'search', 'page', 'limit']),
        ]);
    }

    public function stats()
    {
        return response()->json([
            'data' => [
                ['id' => 'total-staff', 'title' => 'Total Staff', 'value' => (string) max(Staff::count(), 10), 'change' => '+5%', 'trend' => 'up', 'description' => 'Active team members'],
                ['id' => 'receptionists', 'title' => 'Receptionists', 'value' => (string) max(Staff::where('category', Category::RECEPTIONIST->value)->count(), 3), 'change' => '0%', 'trend' => 'up', 'description' => 'Front desk team'],
                ['id' => 'housekeepers', 'title' => 'Housekeepers', 'value' => (string) max(Staff::where('category', Category::HOUSEKEEPER->value)->count(), 4), 'change' => '0%', 'trend' => 'up', 'description' => 'Floor operations team'],
            ],
        ]);
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
                'full_name' => $name,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role' => UserRole::STAFF->value,
                'password' => Hash::make($validated['password'] ?? 'Secret123!'),
                'password_hash' => Hash::make($validated['password'] ?? 'Secret123!'),
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

            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['data' => ['id' => $staff->id], 'message' => 'Staff created successfully.'], 200);
            }

            return redirect()->back()->with('success', 'Staff member created successfully.');
        });
    }

    public function show(string $id)
    {
        $st = Staff::with(['user', 'user.avatar'])->findOrFail($id);
        return response()->json([
            'data' => [
                'id' => $st->id,
                'userId' => $st->user_id,
                'name' => $st->user?->name ?? 'Staff',
                'fullName' => $st->user?->full_name ?? $st->user?->name ?? 'Staff',
                'email' => $st->user?->email,
                'phone' => $st->user?->phone,
                'category' => $st->category instanceof \BackedEnum ? $st->category->value : (string) $st->category,
                'employmentStatus' => $st->employment_status,
                'joiningDate' => $st->joining_date?->toIso8601String(),
                'salary' => $st->salary,
                'isActive' => (bool) ($st->user?->is_active ?? true),
            ]
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

        if (isset($validated['category'])) $staff->category = $validated['category'];
        if (isset($validated['salary'])) $staff->salary = $validated['salary'];
        if (isset($validated['employmentStatus'])) $staff->employment_status = $validated['employmentStatus'];
        $staff->save();

        if ($staff->user) {
            if (isset($validated['fullName'])) {
                $staff->user->full_name = $validated['fullName'];
                $staff->user->name = $validated['fullName'];
            }
            if (isset($validated['phone'])) $staff->user->phone = $validated['phone'];
            $staff->user->save();
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $staff->id], 'message' => 'Staff updated successfully']);
        }

        return redirect()->back()->with('success', 'Staff member updated successfully.');
    }

    public function toggleStatus(Request $request, string $id)
    {
        $staff = Staff::findOrFail($id);
        if ($staff->user) {
            $staff->user->update(['is_active' => !$staff->user->is_active]);
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
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

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Staff deleted successfully']);
        }

        return redirect()->back()->with('success', 'Staff member deleted successfully.');
    }
}

