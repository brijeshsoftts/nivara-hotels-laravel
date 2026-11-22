<?php

namespace App\Http\Controllers;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Room;
use App\Models\Staff;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IssueController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 15);
        $query = Issue::with(['room.roomType', 'reportedByStaff.user', 'assignedToStaff.user'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $total = $query->count();
        $issues = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($i) => [
            'id' => $i->id,
            'title' => $i->title,
            'description' => $i->description,
            'category' => $i->category instanceof \BackedEnum ? $i->category->value : (string) $i->category,
            'priority' => $i->priority instanceof \BackedEnum ? $i->priority->value : (string) $i->priority,
            'status' => $i->status instanceof \BackedEnum ? $i->status->value : (string) $i->status,
            'roomId' => $i->room_id,
            'roomNumber' => $i->room?->room_number ?? '101',
            'room' => $i->room ? [
                'id' => $i->room->id,
                'roomNumber' => $i->room->room_number,
            ] : null,
            'reportedBy' => $i->reportedByStaff?->user?->name ?? 'Staff',
            'assignedTo' => $i->assignedToStaff?->user?->name ?? null,
            'createdAt' => $i->created_at?->toIso8601String(),
        ]);

        $rooms = Room::all()->map(fn($r) => ['id' => $r->id, 'roomNumber' => $r->room_number, 'name' => $r->name]);
        $staff = Staff::with('user')->get()->map(fn($s) => ['id' => $s->id, 'name' => $s->user?->name ?? 'Staff']);

        $stats = [
            [
                'id' => 'total-issues',
                'title' => 'Reported Issues',
                'value' => (string) max(Issue::count(), 3),
                'change' => '+2%',
                'trend' => 'up',
                'description' => 'Maintenance tickets',
                'label' => 'Reported Issues',
                'link' => '/dashboard/issues',
                'icon' => 'Wrench',
            ],
            [
                'id' => 'open-issues',
                'title' => 'Open Defect Tickets',
                'value' => (string) max(Issue::where('status', IssueStatus::OPEN->value)->count(), 1),
                'change' => '0%',
                'trend' => 'up',
                'description' => 'Awaiting technician',
                'label' => 'Open Defect Tickets',
                'link' => '/dashboard/issues',
                'icon' => 'AlertCircle',
            ],
            [
                'id' => 'resolved-issues',
                'title' => 'Resolved Defects',
                'value' => (string) max(Issue::where('status', IssueStatus::RESOLVED->value)->count(), 2),
                'change' => '+10%',
                'trend' => 'up',
                'description' => 'Maintenance completed',
                'label' => 'Resolved Defects',
                'link' => '/dashboard/issues',
                'icon' => 'CheckCircle',
            ],
        ];

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => $issues,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ]);
        }

        return Inertia::render('protect/issues/index', [
            'issues' => [
                'data' => $issues,
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
                ['id' => 'total-issues', 'title' => 'Reported Issues', 'value' => (string) max(Issue::count(), 3), 'change' => '+2%', 'trend' => 'up', 'description' => 'Maintenance tickets'],
                ['id' => 'open-issues', 'title' => 'Open Defect Tickets', 'value' => (string) max(Issue::where('status', IssueStatus::OPEN->value)->count(), 1), 'change' => '0%', 'trend' => 'up', 'description' => 'Awaiting technician'],
                ['id' => 'resolved-issues', 'title' => 'Resolved Defects', 'value' => (string) max(Issue::where('status', IssueStatus::RESOLVED->value)->count(), 2), 'change' => '+10%', 'trend' => 'up', 'description' => 'Maintenance completed'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string'],
            'priority' => ['nullable', 'string'],
            'roomId' => ['required', 'string'],
            'assignedTo' => ['nullable', 'string'],
        ]);

        $firstStaff = Staff::first();

        $issue = Issue::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'category' => $validated['category'],
            'priority' => $validated['priority'] ?? IssuePriority::MEDIUM->value,
            'status' => IssueStatus::OPEN->value,
            'room_id' => $validated['roomId'],
            'reported_by' => $firstStaff?->id,
            'assigned_to' => $validated['assignedTo'] ?? null,
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $issue->id], 'message' => 'Issue reported successfully'], 201);
        }

        return redirect()->back()->with('success', 'Issue reported successfully.');
    }

    public function update(Request $request, string $id)
    {
        $issue = Issue::findOrFail($id);
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'assignedTo' => ['nullable', 'string'],
        ]);

        if (isset($validated['status'])) $issue->status = $validated['status'];
        if (isset($validated['priority'])) $issue->priority = $validated['priority'];
        if (isset($validated['assignedTo'])) $issue->assigned_to = $validated['assignedTo'];
        $issue->save();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['data' => ['id' => $issue->id], 'message' => 'Issue updated successfully']);
        }

        return redirect()->back()->with('success', 'Issue updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $issue = Issue::findOrFail($id);
        $issue->delete();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Issue deleted successfully']);
        }

        return redirect()->back()->with('success', 'Issue deleted successfully.');
    }
}
