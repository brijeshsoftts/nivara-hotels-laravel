<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $role = $this->roleValue($user);
        $props = ['stats' => $this->dashboardService->stats($user)];

        if (in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value], true)) {
            $props += [
                'revenueTrend' => $this->dashboardService->revenueTrend('28d'),
                'bookingStatus' => $this->dashboardService->bookingStatus(),
                'stays' => $this->dashboardService->stays(),
            ];
        } elseif ($role === UserRole::STAFF->value && $user->category === Category::RECEPTIONIST->value) {
            $props += [
                'roomStatus' => $this->dashboardService->roomStatus(),
                'bookingStatus' => $this->dashboardService->bookingStatus(),
                'stays' => $this->dashboardService->stays(),
            ];
        } elseif ($role === UserRole::STAFF->value && $user->category === Category::HOUSEKEEPER->value) {
            $props['tasks'] = $this->dashboardService->tasks($user);
        } elseif ($role === UserRole::CUSTOMER->value) {
            $props['nextBooking'] = $this->dashboardService->nextBooking($user);
        }

        return Inertia::render('protect/Dashboard', $props);
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return response()->json(['data' => $this->dashboardService->stats($user)]);
    }

    public function roomStatus(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $role = $this->roleValue($user);
        abort_unless(
            in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value], true)
                || ($role === UserRole::STAFF->value && $user->category === Category::RECEPTIONIST->value),
            403,
        );

        return response()->json(['data' => $this->dashboardService->roomStatus()]);
    }

    public function revenueTrend(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user instanceof User && in_array($this->roleValue($user), [UserRole::ADMIN->value, UserRole::MANAGER->value], true),
            403,
        );
        $validated = $request->validate([
            'range' => ['sometimes', 'in:7d,14d,28d'],
        ]);

        return response()->json(['data' => $this->dashboardService->revenueTrend($validated['range'] ?? '7d')]);
    }

    public function bookingStatus(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $role = $this->roleValue($user);
        abort_unless(
            in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value], true)
                || ($role === UserRole::STAFF->value && $user->category === Category::RECEPTIONIST->value),
            403,
        );
        $validated = $request->validate([
            'range' => ['sometimes', 'in:all,7d,14d,28d'],
        ]);

        return response()->json(['data' => $this->dashboardService->bookingStatus($validated['range'] ?? 'all')]);
    }

    public function stays(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $role = $this->roleValue($user);
        abort_unless(
            in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value], true)
                || ($role === UserRole::STAFF->value && $user->category === Category::RECEPTIONIST->value),
            403,
        );

        $date = $request->input('date', 'today');
        if ($date !== 'today') {
            $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        }
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:5'],
        ]);

        return response()->json(['data' => $this->dashboardService->stays($date, (int) ($validated['limit'] ?? 5))]);
    }

    private function roleValue(User $user): string
    {
        return $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
    }
}
