<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\IssueCategory;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Issue;
use App\Models\Room;
use App\Models\Staff;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class IssueController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    public function index(Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(100, max(1, (int) $request->input('limit', 15)));
        $query = Issue::with(['room', 'reportedBy', 'assignedTo'])->orderByDesc('reported_at');

        $role = $request->user()?->role;
        $role = $role instanceof \BackedEnum ? $role->value : (string) $role;
        if ($role === UserRole::CUSTOMER->value) {
            $query->where('reported_by_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($issues) use ($search) {
                $issues->where('reference', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('room', fn ($rooms) => $rooms->where('room_number', 'like', "%{$search}%"));
            });
        }

        $total = (clone $query)->count();
        $issues = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn ($i) => [
            'id' => $i->id,
            'reference' => $i->reference,
            'title' => $i->title,
            'category' => $i->category instanceof \BackedEnum ? $i->category->value : (string) $i->category,
            'priority' => $i->priority instanceof \BackedEnum ? $i->priority->value : (string) $i->priority,
            'status' => $i->status instanceof \BackedEnum ? $i->status->value : (string) $i->status,
            'room' => $i->room ? [
                'id' => $i->room->id,
                'roomNumber' => $i->room->room_number,
            ] : null,
            'reporter' => $i->reportedBy ? [
                'id' => $i->reportedBy->id,
                'fullName' => $i->reportedBy->full_name ?? $i->reportedBy->name,
            ] : null,
            'assignee' => $i->assignedTo ? [
                'id' => $i->assignedTo->id,
                'fullName' => $i->assignedTo->full_name ?? $i->assignedTo->name,
            ] : null,
            'reportedAt' => $i->reported_at?->toIso8601String(),
        ]);

        $rooms = Room::all()->map(fn ($r) => ['id' => $r->id, 'roomNumber' => $r->room_number, 'name' => $r->name]);
        $staff = Staff::with('user')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->user?->name ?? 'Staff']);

        $stats = $this->getStats($request);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'data' => $issues,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => (int) ceil(max($total, 1) / $limit),
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
                    'totalPages' => (int) ceil(max($total, 1) / $limit),
                ],
            ],
            'rooms' => $rooms,
            'staff' => $staff,
            'stats' => $stats,
            'filters' => $request->only(['status', 'priority', 'page', 'limit']),
        ]);
    }

    public function show(Request $request, string $id)
    {
        $query = Issue::with(['room', 'reportedBy', 'assignedTo', 'image'])->whereKey($id);
        $role = $request->user()?->role;
        $role = $role instanceof \BackedEnum ? $role->value : (string) $role;
        if ($role === UserRole::CUSTOMER->value) {
            $query->where('reported_by_id', $request->user()->id);
        }

        $issue = $query->firstOrFail();

        return response()->json(['data' => $this->formatIssueDetails($issue)]);
    }

    private function formatIssueDetails(Issue $issue): array
    {
        return [
            'id' => $issue->id,
            'reference' => $issue->reference,
            'title' => $issue->title,
            'description' => $issue->description,
            'category' => $issue->category instanceof \BackedEnum ? $issue->category->value : (string) $issue->category,
            'priority' => $issue->priority instanceof \BackedEnum ? $issue->priority->value : (string) $issue->priority,
            'status' => $issue->status instanceof \BackedEnum ? $issue->status->value : (string) $issue->status,
            'reportedAt' => $issue->reported_at?->toIso8601String(),
            'startedAt' => $issue->started_at?->toIso8601String(),
            'completedAt' => $issue->completed_at?->toIso8601String(),
            'resolvedAt' => $issue->resolved_at?->toIso8601String(),
            'createdAt' => $issue->created_at?->toIso8601String(),
            'updatedAt' => $issue->updated_at?->toIso8601String(),
            'room' => [
                'id' => $issue->room->id,
                'roomNumber' => $issue->room->room_number,
                'name' => $issue->room->name,
                'floor' => $issue->room->floor,
            ],
            'reporter' => [
                'id' => $issue->reportedBy->id,
                'fullName' => $issue->reportedBy->full_name ?? $issue->reportedBy->name,
            ],
            'assignee' => $issue->assignedTo ? [
                'id' => $issue->assignedTo->id,
                'fullName' => $issue->assignedTo->full_name ?? $issue->assignedTo->name,
            ] : null,
            'image' => $issue->image ? [
                'id' => $issue->image->id,
                'url' => $issue->image->url,
                'altText' => $issue->image->alt_text,
            ] : null,
        ];
    }

    public function stats(Request $request)
    {
        return response()->json(['data' => $this->getStats($request)]);
    }

    private function getStats(Request $request): array
    {
        $query = Issue::query();
        $role = $request->user()?->role;
        $role = $role instanceof \BackedEnum ? $role->value : (string) $role;
        $isCustomer = $role === UserRole::CUSTOMER->value;
        if ($isCustomer) {
            $query->where('reported_by_id', $request->user()->id);
        }

        $total = (clone $query)->count();
        $medium = (clone $query)->where('priority', IssuePriority::MEDIUM->value)->count();
        $high = (clone $query)->where('priority', IssuePriority::HIGH->value)->count();
        $urgent = (clone $query)->where('priority', IssuePriority::URGENT->value)->count();

        return [
            [
                'id' => 'total-issues',
                'title' => 'Total Issues',
                'label' => 'Total Issues',
                'value' => number_format($total),
                'description' => $isCustomer ? 'Issues you reported' : 'All reported issues',
                'icon' => 'ClipboardList',
                'link' => '/dashboard/issues',
                'tone' => 'sky',
            ],
            [
                'id' => 'medium-issues',
                'title' => 'Medium Priority',
                'label' => 'Medium Priority',
                'value' => number_format($medium),
                'description' => $isCustomer ? 'Your medium-priority issues' : 'Issues with medium priority',
                'icon' => 'CircleAlert',
                'link' => '/dashboard/issues?priority=MEDIUM',
                'tone' => 'gold',
            ],
            [
                'id' => 'high-issues',
                'title' => 'High Priority',
                'label' => 'High Priority',
                'value' => number_format($high),
                'description' => $isCustomer ? 'Your high-priority issues' : 'Issues with high priority',
                'icon' => 'TriangleAlert',
                'link' => '/dashboard/issues?priority=HIGH',
                'tone' => 'rose',
            ],
            [
                'id' => 'urgent-issues',
                'title' => 'Urgent Priority',
                'label' => 'Urgent Priority',
                'value' => number_format($urgent),
                'description' => $isCustomer ? 'Your urgent issues' : 'Issues requiring urgent attention',
                'icon' => 'Siren',
                'link' => '/dashboard/issues?priority=URGENT',
                'tone' => 'violet',
            ],
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'description' => ['required', 'string', 'min:1', 'max:5000'],
            'category' => ['required', Rule::enum(IssueCategory::class)],
            'priority' => ['required', Rule::enum(IssuePriority::class)],
            'roomId' => ['nullable', 'string', Rule::exists('rooms', 'id')],
            'imageId' => ['nullable', 'string', Rule::exists('images', 'id')],
            'assignedToId' => [
                'nullable',
                'string',
                Rule::exists('users', 'id')->where('role', UserRole::STAFF->value)->where('is_active', true)->whereNull('soft_deleted_at'),
            ],
        ]);

        $role = $request->user()->role;
        $role = $role instanceof \BackedEnum ? $role->value : (string) $role;
        $roomId = $validated['roomId'] ?? null;
        if ($role === UserRole::CUSTOMER->value) {
            abort_if($request->filled('roomId'), 422, 'Customers cannot select an issue room.');
            $booking = Booking::where('customer_id', $request->user()->id)
                ->where('status', BookingStatus::CHECKED_IN->value)
                ->orderByDesc('checked_in_at')
                ->first();
            $roomId = $booking?->bookingRooms()
                ->whereNotNull('assigned_room_id')
                ->value('assigned_room_id');
            abort_if(! $roomId, 422, 'A current checked-in stay is required to report an issue.');
        }
        abort_if(! $roomId, 422, 'A room is required to report an issue.');

        $issue = Issue::create([
            'reference' => 'ISS-'.now()->timestamp.'-'.Str::upper(Str::random(5)),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'priority' => $validated['priority'] ?? IssuePriority::MEDIUM->value,
            'status' => IssueStatus::OPEN->value,
            'room_id' => $roomId,
            'reported_by_id' => $request->user()->id,
            'assigned_to_id' => $validated['assignedToId'] ?? null,
            'image_id' => $validated['imageId'] ?? null,
        ]);

        $this->notificationService->createForRoles(
            [UserRole::ADMIN, UserRole::MANAGER],
            NotificationType::ISSUE_CREATED,
            "New issue: {$issue->reference}",
            "{$issue->title} ({$issue->category->value}, {$issue->priority->value}): ".substr($issue->description, 0, 240).'. Reported by '.($request->user()->full_name ?? $request->user()->name).'.',
            'ISSUE',
            $issue->id,
            [(string) $request->user()->id],
        );

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            $issue->load(['room', 'reportedBy']);

            return response()->json([
                'data' => [
                    'id' => $issue->id,
                    'reference' => $issue->reference,
                    'title' => $issue->title,
                    'category' => $issue->category->value,
                    'priority' => $issue->priority->value,
                    'status' => $issue->status->value,
                    'room' => ['id' => $issue->room->id, 'roomNumber' => $issue->room->room_number],
                    'reporter' => ['id' => $issue->reportedBy->id, 'fullName' => $issue->reportedBy->full_name ?? $issue->reportedBy->name],
                    'reportedAt' => $issue->reported_at?->toIso8601String(),
                ],
                'message' => 'Issue reported successfully',
            ], 201);
        }

        return redirect()->back()->with('success', 'Issue reported successfully.');
    }

    public function update(Request $request, string $id)
    {
        $issue = Issue::findOrFail($id);
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:200'],
            'description' => ['sometimes', 'required', 'string', 'min:1', 'max:5000'],
            'category' => ['sometimes', 'required', Rule::enum(IssueCategory::class)],
            'status' => ['sometimes', 'required', Rule::enum(IssueStatus::class)],
            'priority' => ['sometimes', 'required', Rule::enum(IssuePriority::class)],
            'assignedToId' => [
                'sometimes',
                'nullable',
                'string',
                Rule::exists('users', 'id')->where('role', UserRole::STAFF->value)->where('is_active', true)->whereNull('soft_deleted_at'),
            ],
        ]);

        abort_if($validated === [], 422, 'Provide at least one issue field to update.');
        $previousAssigneeId = $issue->assigned_to_id;

        foreach (['title', 'description', 'category', 'status', 'priority'] as $field) {
            if (array_key_exists($field, $validated)) {
                $issue->{$field} = $validated[$field];
            }
        }
        if (($validated['status'] ?? null) === IssueStatus::IN_PROGRESS->value && ! $issue->started_at) {
            $issue->started_at = now();
        }
        if (($validated['status'] ?? null) === IssueStatus::COMPLETED->value && ! $issue->completed_at) {
            $issue->completed_at = now();
        }
        if (($validated['status'] ?? null) === IssueStatus::RESOLVED->value && ! $issue->resolved_at) {
            $issue->resolved_at = now();
        }
        if (array_key_exists('assignedToId', $validated)) {
            $issue->assigned_to_id = $validated['assignedToId'];
        }
        $changedFields = array_keys($issue->getDirty());
        $issue->save();

        $updaterId = (string) $request->user()->id;
        if (
            $previousAssigneeId !== $issue->assigned_to_id
            && $issue->assigned_to_id
            && (string) $issue->assigned_to_id !== $updaterId
        ) {
            $this->notificationService->createForUsers(
                [(string) $issue->assigned_to_id],
                NotificationType::ISSUE_ASSIGNED,
                'Issue assigned to you',
                "Issue {$issue->reference}: {$issue->title} ({$issue->category->value}, {$issue->priority->value}) was assigned to you by ".($request->user()->full_name ?? $request->user()->name).'.',
                'ISSUE',
                $issue->id,
            );
        }

        $updatedFields = array_diff($changedFields, ['assigned_to_id']);
        $stakeholderIds = array_values(array_unique(array_filter(
            [$issue->reported_by_id, $issue->assigned_to_id],
            fn ($userId): bool => $userId !== null && (string) $userId !== $updaterId,
        )));
        if ($updatedFields !== [] && $stakeholderIds !== []) {
            $this->notificationService->createForUsers(
                array_map('strval', $stakeholderIds),
                NotificationType::ISSUE_UPDATED,
                "Issue updated: {$issue->reference}",
                "{$issue->title} is {$issue->status->value} with {$issue->priority->value} priority. Updated by ".($request->user()->full_name ?? $request->user()->name).': '.implode(', ', $updatedFields).'.',
                'ISSUE',
                $issue->id,
            );
        }

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            $issue->load(['room', 'reportedBy', 'assignedTo', 'image']);

            return response()->json([
                'data' => $this->formatIssueDetails($issue),
                'message' => 'Issue updated successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Issue updated successfully.');
    }

    public function destroy(Request $request, string $id)
    {
        $issue = Issue::findOrFail($id);
        $issue->delete();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => 'Issue deleted successfully']);
        }

        return redirect()->back()->with('success', 'Issue deleted successfully.');
    }
}
