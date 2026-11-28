<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    private const DEFAULT_USER_PASSWORD = 'Admin@1234';

    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $managedRoles = [UserRole::MANAGER->value, UserRole::STAFF->value];
        $query = User::with('avatar')
            ->whereIn('role', $managedRoles)
            ->whereNull('soft_deleted_at')
            ->orderByDesc('created_at');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%"));
        }

        $total = $query->count();
        $users = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'role' => $u->role instanceof \BackedEnum ? $u->role->value : (string) $u->role,
            'isActive' => (bool) $u->is_active,
            'avatar' => $u->avatar ? [
                'id' => $u->avatar->id,
                'url' => $u->avatar->url,
                'altText' => $u->avatar->alt_text,
            ] : null,
            'createdAt' => $u->created_at?->toIso8601String(),
        ]);

        $stats = $this->getStats();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'data' => $users,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ]);
        }

        return Inertia::render('protect/users', [
            'users' => [
                'data' => $users,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ],
            'stats' => $stats,
            'filters' => $request->only(['role', 'isActive', 'search', 'page', 'limit']),
        ]);
    }

    public function stats()
    {
        return response()->json(['data' => $this->getStats()]);
    }

    private function getStats(): array
    {
        $managedRoles = [UserRole::MANAGER->value, UserRole::STAFF->value];
        $managerCount = User::where('role', UserRole::MANAGER->value)
            ->whereNull('soft_deleted_at')
            ->count();
        $staffCount = User::where('role', UserRole::STAFF->value)
            ->whereNull('soft_deleted_at')
            ->count();
        $inactiveCount = User::whereIn('role', $managedRoles)
            ->where('is_active', false)
            ->whereNull('soft_deleted_at')
            ->count();

        return [
            [
                'id' => 'total-accounts',
                'label' => 'Total Accounts',
                'value' => number_format($managerCount + $staffCount),
                'link' => '/dashboard/users',
                'icon' => 'Users',
            ],
            [
                'id' => 'manager-accounts',
                'label' => 'Managers',
                'value' => number_format($managerCount),
                'link' => '/dashboard/users?role=MANAGER',
                'icon' => 'UserCheck',
            ],
            [
                'id' => 'staff-accounts',
                'label' => 'Staff Accounts',
                'value' => number_format($staffCount),
                'link' => '/dashboard/users?role=STAFF',
                'icon' => 'UserPlus',
            ],
            [
                'id' => 'inactive-accounts',
                'label' => 'Inactive Accounts',
                'value' => number_format($inactiveCount),
                'link' => '/dashboard/users?isActive=false',
                'icon' => 'UserX',
            ],
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string'],
            'role' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:6'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $name = $validated['name'] ?? explode('@', $validated['email'])[0];

        $user = User::create([
            'name' => $name,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'] ?? UserRole::MANAGER->value,
            'password' => Hash::make($validated['password'] ?? self::DEFAULT_USER_PASSWORD),
            'is_active' => $validated['isActive'] ?? true,
        ]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $user->id, 'email' => $user->email], 'message' => 'User created successfully.'], 200);
        }

        return redirect()->back()->with('success', 'User created successfully.');
    }

    public function show(string $id)
    {
        $u = User::with('avatar')->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'role' => $u->role instanceof \BackedEnum ? $u->role->value : (string) $u->role,
                'isActive' => (bool) $u->is_active,
                'createdAt' => $u->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string'],
            'role' => ['nullable', 'string'],
            'isActive' => ['nullable', 'boolean'],
        ]);

        $updates = [];
        if (isset($validated['name'])) {
            $updates['name'] = $validated['name'];
        }
        if (isset($validated['phone'])) {
            $updates['phone'] = $validated['phone'];
        }
        if (isset($validated['role'])) {
            $updates['role'] = $validated['role'];
        }
        if (isset($validated['isActive'])) {
            $updates['is_active'] = $validated['isActive'];
        }

        $user->update($updates);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $user->id], 'message' => 'User updated successfully']);
        }

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function toggleStatus(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $user->id, 'isActive' => (bool) $user->is_active]]);
        }

        return redirect()->back()->with('success', 'User status updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => 'User deleted successfully']);
        }

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
