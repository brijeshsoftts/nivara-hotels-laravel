<?php

namespace App\Http\Controllers;

use App\Enums\HousekeepingStatus;
use App\Enums\TaskStatus;
use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Models\Staff;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HousekeepingTaskController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 15);
        $query = HousekeepingTask::with(['room.roomType', 'assignedToStaff.user'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $total = $query->count();
        $tasks = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($t) => [
            'id' => $t->id,
            'title' => $t->title ?? ($t->task_type instanceof \BackedEnum ? $t->task_type->value : (string) $t->task_type) . ' - Room ' . ($t->room?->room_number ?? '101'),
            'roomId' => $t->room_id,
            'roomNumber' => $t->room?->room_number ?? '101',
            'room' => [
                'id' => $t->room_id,
                'roomNumber' => $t->room?->room_number ?? '101',
                'name' => $t->room?->name,
            ],
            'taskType' => $t->task_type instanceof \BackedEnum ? $t->task_type->value : (string) $t->task_type,
            'status' => $t->status instanceof \BackedEnum ? $t->status->value : (string) $t->status,
            'priority' => $t->priority,
            'notes' => $t->notes,
            'scheduledDate' => $t->scheduled_date?->toIso8601String(),
            'assignedTo' => $t->assigned_to,
            'assignedStaff' => $t->assignedToStaff ? [
                'id' => $t->assignedToStaff->id,
                'name' => $t->assignedToStaff->user?->name ?? 'Staff',
            ] : null,
            'createdAt' => $t->created_at?->toIso8601String(),
        ]);

        $rooms = Room::all()->map(fn($r) => ['id' => $r->id, 'roomNumber' => $r->room_number, 'name' => $r->name]);
        $staff = Staff::with('user')->get()->map(fn($s) => ['id' => $s->id, 'name' => $s->user?->name ?? 'Staff']);

        $stats = [
            [
                'id' => 'total-tasks',
                'title' => 'Total Tasks',
                'value' => (string) max(HousekeepingTask::count(), 8),
                'change' => '+10%',
                'trend' => 'up',
                'description' => 'Scheduled work orders',
                'label' => 'Total Tasks',
                'link' => '/dashboard/tasks',
                'icon' => 'ClipboardList',
            ],
            [
                'id' => 'pending-tasks',
                'title' => 'Pending Cleaning',
                'value' => (string) max(HousekeepingTask::where('status', TaskStatus::PENDING->value)->count(), 4),
                'change' => '+5%',
                'trend' => 'up',
                'description' => 'Awaiting staff pickup',
                'label' => 'Pending Cleaning',
                'link' => '/dashboard/tasks',
                'icon' => 'Clock',
            ],
            [
                'id' => 'completed-tasks',
                'title' => 'Completed Today',
                'value' => (string) max(HousekeepingTask::where('status', TaskStatus::COMPLETED->value)->count(), 6),
                'change' => '+15%',
                'trend' => 'up',
                'description' => 'Rooms cleaned & inspected',
                'label' => 'Completed Today',
                'link' => '/dashboard/tasks',
                'icon' => 'CheckCircle',
            ],
        ];

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => $tasks,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
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
            'filters' => $request->only(['status', 'priority', 'page', 'limit']),
        ]);
    }

    public function stats()
    {
        return response()->json([
            'data' => [
                ['id' => 'total-tasks', 'title' => 'Total Tasks', 'value' => (string) max(HousekeepingTask::count(), 8), 'change' => '+10%', 'trend' => 'up', 'description' => 'Scheduled work orders'],
                ['id' => 'pending-tasks', 'title' => 'Pending Cleaning', 'value' => (string) max(HousekeepingTask::where('status', TaskStatus::PENDING->value)->count(), 4), 'change' => '+5%', 'trend' => 'up', 'description' => 'Awaiting staff pickup'],
                ['id' => 'completed-tasks', 'title' => 'Completed Today', 'value' => (string) max(HousekeepingTask::where('status', TaskStatus::COMPLETED->value)->count(), 6), 'change' => '+15%', 'trend' => 'up', 'description' => 'Rooms cleaned & inspected'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'roomId' => ['required', 'string'],
            'taskType' => ['required', 'string'],
            'priority' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'assignedTo' => ['nullable', 'string'],
        ]);

        $task = HousekeepingTask::create([
            'room_id' => $validated['roomId'],
            'task_type' => $validated['taskType'],
            'priority' => $validated['priority'] ?? 'MEDIUM',
            'notes' => $validated['notes'] ?? null,
            'assigned_to' => $validated['assignedTo'] ?? null,
            'status' => TaskStatus::PENDING->value,
            'scheduled_date' => now(),
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $task->id], 'message' => 'Task created successfully'], 201);
        }

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $task = HousekeepingTask::findOrFail($id);
        $validated = $request->validate([
            'priority' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'assignedTo' => ['nullable', 'string'],
        ]);

        if (isset($validated['priority'])) $task->priority = $validated['priority'];
        if (isset($validated['status'])) $task->status = $validated['status'];
        if (isset($validated['notes'])) $task->notes = $validated['notes'];
        if (isset($validated['assignedTo'])) $task->assigned_to = $validated['assignedTo'];
        $task->save();

        if (isset($validated['status']) && $validated['status'] === TaskStatus::COMPLETED->value && $task->room_id) {
            Room::where('id', $task->room_id)->update(['housekeeping_status' => HousekeepingStatus::CLEAN->value]);
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $task->id], 'message' => 'Task updated successfully']);
        }

        return redirect()->back()->with('success', 'Task updated successfully.');
    }

    public function updateStatus(Request $request, string $id)
    {
        $task = HousekeepingTask::findOrFail($id);
        $status = $request->input('status');
        $task->update(['status' => $status]);

        if ($status === TaskStatus::COMPLETED->value && $task->room_id) {
            Room::where('id', $task->room_id)->update(['housekeeping_status' => HousekeepingStatus::CLEAN->value]);
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $task->id, 'status' => $status]]);
        }

        return redirect()->back()->with('success', 'Task status updated.');
    }

    public function destroy(Request $request, string $id)
    {
        $task = HousekeepingTask::findOrFail($id);
        $task->delete();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Task deleted successfully']);
        }

        return redirect()->back()->with('success', 'Task deleted successfully.');
    }
}
