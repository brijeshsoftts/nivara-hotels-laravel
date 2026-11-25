<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $this->notificationService->markAsRead($user, $id);

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $this->notificationService->markAllAsRead($user);

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}
