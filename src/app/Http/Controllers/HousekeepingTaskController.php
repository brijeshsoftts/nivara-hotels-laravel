<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\HousekeepingStatus;
use App\Enums\NotificationType;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Enums\UserRole;
use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class HousekeepingTaskController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTaskAccess($user);

        $page = max(1, (int) $request->input('page', 1));
        $limit = min(100, max(1, (int) $request->input('limit', 10)));
        $query = HousekeepingTask::with(['room', 'assignedTo'])
            ->when($user->role === UserRole::STAFF->value, fn($tasks) => $tasks->where('assigned_to', $user->id))
            ->orderBy('scheduled_date');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('taskType')) {
            $query->where('task_type', $request->input('taskType'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($tasks) use ($search) {
                $tasks->whereHas('room', fn($rooms) => $rooms
                    ->where('room_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('assignedTo', fn($assignees) => $assignees
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $total = (clone $query)->count();
        $tasks = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($task) => [
            'id' => $task->id,
            'room' => [
                'id' => $task->room->id,
                'roomNumber' => $task->room->room_number,
            ],
            'taskType' => $task->task_type instanceof \BackedEnum ? $task->task_type->value : (string) $task->task_type,
            'status' => $task->status instanceof \BackedEnum ? $task->status->value : (string) $task->status,
            'scheduledDate' => $task->scheduled_date?->toIso8601String(),
            'startedAt' => $task->started_at?->toIso8601String(),
            'completedAt' => $task->completed_at?->toIso8601String(),
            'assignee' => [
                'id' => $task->assignedTo?->id ?? '',
                'name' => $task->assignedTo?->name ?? 'Unassigned',
            ],
        ]);

        $rooms = Room::where('is_active', true)->orderBy('room_number')->get()
            ->map(fn($room) => [
                'id' => $room->id,
                'roomNumber' => $room->room_number,
                'name' => $room->name,
                'floor' => $room->floor,
            ]);
        $staff = Staff::with('user')
            ->where('category', Category::HOUSEKEEPER->value)
            ->whereHas('user', fn($users) => $users->where('is_active', true)->whereNull('soft_deleted_at'))
            ->get()
            ->map(fn($housekeeper) => [
                'id' => $housekeeper->user_id,
                'name' => $housekeeper->user?->name ?? 'Housekeeper',
                'phone' => $housekeeper->user?->phone ?? '',
            ]);

        $stats = $this->getStats($user);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'data' => $tasks,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                ],
            ]);
        }

        return Inertia::render('protect/tasks/index', [
            'tasks' => [
                'data' => $tasks,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ],
            'rooms' => $rooms,
            'staff' => $staff,
            'stats' => $stats,
            'filters' => $request->only(['status', 'taskType', 'search', 'page', 'limit']),
        ]);
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTaskAccess($user);

        return response()->json(['data' => $this->getStats($user)]);
    }

    private function getStats(User $user): array
    {
        $query = HousekeepingTask::query()
            ->when($user->role === UserRole::STAFF->value, fn($tasks) => $tasks->where('assigned_to', $user->id));
        $pending = (clone $query)->where('status', TaskStatus::PENDING->value)->count();
        $inProgress = (clone $query)->where('status', TaskStatus::IN_PROGRESS->value)->count();
        $completed = (clone $query)->where('status', TaskStatus::COMPLETED->value)->count();
        $baseUrl = '/dashboard/tasks';

        return [
            [
                'id' => 'total-tasks',
                'title' => 'Total Tasks',
                'label' => 'Total Tasks',
                'value' => number_format($pending + $inProgress + $completed),
                'description' => 'Tasks scheduled for housekeeping',
                'icon' => 'ClipboardList',
                'link' => $baseUrl,
                'tone' => 'sky',
            ],
            [
                'id' => 'pending-tasks',
                'title' => 'Pending Tasks',
                'label' => 'Pending Tasks',
                'value' => number_format($pending),
                'description' => 'Waiting to be started',
                'icon' => 'Clock',
                'link' => $baseUrl . '?status=' . TaskStatus::PENDING->value,
                'tone' => 'gold',
            ],
            [
                'id' => 'in-progress-tasks',
                'title' => 'In Progress',
                'label' => 'In Progress',
                'value' => number_format($inProgress),
                'description' => 'Tasks currently underway',
                'icon' => 'LoaderCircle',
                'link' => $baseUrl . '?status=' . TaskStatus::IN_PROGRESS->value,
                'tone' => 'violet',
            ],
            [
                'id' => 'completed-tasks',
                'title' => 'Completed',
                'label' => 'Completed',
                'value' => number_format($completed),
                'description' => 'Finished housekeeping tasks',
                'icon' => 'CircleCheck',
                'link' => $baseUrl . '?status=' . TaskStatus::COMPLETED->value,
                'tone' => 'emerald',
            ],
        ];
    }

    private function authorizeTaskAccess(User $user, bool $managementOnly = false): void
    {
        $role = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
        if (in_array($role, [UserRole::ADMIN->value, UserRole::MANAGER->value], true)) {
            return;
        }

        $isHousekeeper = $role === UserRole::STAFF->value
            && Staff::where('user_id', $user->id)
            ->where('category', Category::HOUSEKEEPER->value)
            ->whereHas('user', fn($users) => $users->where('is_active', true)->whereNull('soft_deleted_at'))
            ->exists();

        abort_if($managementOnly || ! $isHousekeeper, 403);
    }

    private function syncRoomHousekeepingStatus(string $roomId, bool $completed = false, bool $abandoned = false): void
    {
        $room = Room::find($roomId);
        if (! $room) {
            return;
        }

        $activeTasks = HousekeepingTask::where('room_id', $roomId)
            ->whereIn('status', [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value]);
        if ((clone $activeTasks)->where('status', TaskStatus::IN_PROGRESS->value)->exists()) {
            $room->housekeeping_status = HousekeepingStatus::CLEANING->value;
        } elseif ($activeTasks->exists()) {
            if ($room->housekeeping_status === HousekeepingStatus::CLEANING) {
                $room->housekeeping_status = HousekeepingStatus::DIRTY->value;
            }
        } elseif ($completed) {
            $room->housekeeping_status = HousekeepingStatus::CLEAN->value;
        } elseif ($abandoned && $room->housekeeping_status === HousekeepingStatus::CLEANING) {
            $room->housekeeping_status = HousekeepingStatus::DIRTY->value;
        }

        $room->save();
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTaskAccess($user, true);

        $validated = $request->validate([
            'roomId' => ['required', 'string', Rule::exists('rooms', 'id')->where('is_active', true)],
            'taskType' => ['required', Rule::enum(TaskType::class)],
            'scheduledDate' => ['required', 'date'],
            'assignedTo' => [
                'required',
                Rule::exists('staff', 'user_id')->where('category', Category::HOUSEKEEPER->value),
                Rule::exists('users', 'id')->where('is_active', true)->whereNull('soft_deleted_at'),
            ],
        ]);

        $task = HousekeepingTask::create([
            'room_id' => $validated['roomId'],
            'task_type' => $validated['taskType'],
            'assigned_to' => $validated['assignedTo'],
            'status' => TaskStatus::PENDING->value,
            'scheduled_date' => $validated['scheduledDate'],
        ]);
        $this->syncRoomHousekeepingStatus($task->room_id);

        if ((string) $validated['assignedTo'] !== (string) $user->id) {
            $room = Room::find($task->room_id);
            $this->notificationService->createForUsers(
                [(string) $validated['assignedTo']],
                NotificationType::HOUSEKEEPING_ASSIGNED,
                'Housekeeping task assigned',
                "{$task->task_type->value} task for room {$room?->room_number} is scheduled for {$task->scheduled_date?->toDateString()}. Assigned by " . $user->name . '.',
                'HOUSEKEEPING_TASK',
                $task->id,
            );
        }

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $task->id], 'message' => 'Task created successfully'], 201);
        }

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTaskAccess($user, true);

        $task = HousekeepingTask::with('room')->findOrFail($id);
        abort_unless($task->status === TaskStatus::PENDING, 409);
        $validated = $request->validate([
            'roomId' => ['sometimes', 'required', 'string', Rule::exists('rooms', 'id')->where('is_active', true)],
            'taskType' => ['sometimes', 'required', Rule::enum(TaskType::class)],
            'scheduledDate' => ['sometimes', 'required', 'date'],
            'assignedTo' => [
                'sometimes',
                'required',
                Rule::exists('staff', 'user_id')->where('category', Category::HOUSEKEEPER->value),
                Rule::exists('users', 'id')->where('is_active', true)->whereNull('soft_deleted_at'),
            ],
        ]);

        if ($validated === []) {
            abort(422, 'Provide at least one task field to update.');
        }

        $originalRoomId = $task->room_id;
        $originalAssigneeId = $task->assigned_to;
        if (isset($validated['roomId'])) {
            $task->room_id = $validated['roomId'];
        }
        if (isset($validated['taskType'])) {
            $task->task_type = $validated['taskType'];
        }
        if (isset($validated['scheduledDate'])) {
            $task->scheduled_date = $validated['scheduledDate'];
        }
        if (isset($validated['assignedTo'])) {
            $task->assigned_to = $validated['assignedTo'];
        }
        $task->save();

        if ($originalRoomId !== $task->room_id) {
            $this->syncRoomHousekeepingStatus($originalRoomId, abandoned: true);
        }
        $this->syncRoomHousekeepingStatus($task->room_id);

        if (
            $originalAssigneeId !== $task->assigned_to
            && (string) $task->assigned_to !== (string) $user->id
        ) {
            $room = Room::find($task->room_id);
            $this->notificationService->createForUsers(
                [(string) $task->assigned_to],
                NotificationType::HOUSEKEEPING_ASSIGNED,
                'Housekeeping task assigned',
                "{$task->task_type->value} task for room {$room?->room_number} is scheduled for {$task->scheduled_date?->toDateString()}. Assigned by " . $user->name . '.',
                'HOUSEKEEPING_TASK',
                $task->id,
            );
        }

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $task->id], 'message' => 'Task updated successfully']);
        }

        return redirect()->back()->with('success', 'Task updated successfully.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTaskAccess($user);

        $task = HousekeepingTask::findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', Rule::in([TaskStatus::IN_PROGRESS->value, TaskStatus::COMPLETED->value])],
        ]);
        if ($user->role === UserRole::STAFF->value) {
            abort_unless($task->assigned_to === $user->id, 403);
        }
        $status = $validated['status'];
        $expectedCurrent = $status === TaskStatus::IN_PROGRESS->value
            ? TaskStatus::PENDING
            : TaskStatus::IN_PROGRESS;
        abort_unless($task->status === $expectedCurrent, 409);

        $task->status = $status;
        if ($status === TaskStatus::IN_PROGRESS->value) {
            $task->started_at = now();
        }
        if ($status === TaskStatus::COMPLETED->value) {
            $task->completed_at = now();
        }
        $task->save();
        $this->syncRoomHousekeepingStatus($task->room_id, completed: $status === TaskStatus::COMPLETED->value);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $task->id, 'status' => $status]]);
        }

        return redirect()->back()->with('success', 'Task status updated.');
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $this->authorizeTaskAccess($user, true);

        $task = HousekeepingTask::findOrFail($id);
        $roomId = $task->room_id;
        $isActive = in_array($task->status, [TaskStatus::PENDING, TaskStatus::IN_PROGRESS], true);
        $task->delete();
        $this->syncRoomHousekeepingStatus($roomId, abandoned: $isActive);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => 'Task deleted successfully']);
        }

        return redirect()->back()->with('success', 'Task deleted successfully.');
    }
}
