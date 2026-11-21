<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\UserRole;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return match ($user->role) {
            UserRole::ADMIN->value,
            UserRole::MANAGER->value => view(
                'pages.protect.dashboard.management-dashboard',
                $this->dashboardService->management()
            ),
            UserRole::STAFF->value && Category::RECEPTIONIST->value => view(
                'pages.protect.dashboard.receptionist-dashboard',
                $this->dashboardService->receptionist()
            ),
            UserRole::STAFF->value && Category::HOUSEKEEPER->value => view(
                'pages.protect.dashboard.housekeeper-dashboard',
                $this->dashboardService->housekeeper()
            ),
            UserRole::CUSTOMER->value => view(
                'pages.protect.dashboard.customer-dashboard',
                $this->dashboardService->customer()
            ),
            default => abort(403, 'Unauthorized action.'),
        };
    }
}
