<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse|InertiaResponse
    {
        abort_unless($request->user()?->role === UserRole::ADMIN->value, 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'entityType' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $page = (int) ($filters['page'] ?? 1);
        $limit = (int) ($filters['limit'] ?? 20);
        $query = AuditLog::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($filters['entityType'])) {
            $query->where('entity_type', $filters['entityType']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('action', 'like', "%{$search}%")
                    ->orWhere('entity_type', 'like', "%{$search}%")
                    ->orWhere('entity_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $total = $query->count();
        $auditLogs = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn(AuditLog $log): array => [
            'id' => $log->id,
            'userId' => $log->user_id,
            'action' => $log->action,
            'entityType' => $log->entity_type,
            'entityId' => $log->entity_id,
            'oldValues' => $log->old_values ?? [],
            'newValues' => $log->new_values ?? [],
            'createdAt' => $log->created_at?->toIso8601String(),
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'email' => $log->user->email,
            ] : null,
        ]);

        $meta = [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => max((int) ceil($total / $limit), 1),
        ];

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['data' => $auditLogs, 'meta' => $meta]);
        }

        return Inertia::render('protect/audit-logs/index', [
            'auditLogs' => ['data' => $auditLogs, 'meta' => $meta],
            'filters' => [
                'search' => $filters['search'] ?? '',
                'entityType' => $filters['entityType'] ?? '',
                'page' => $page,
                'limit' => $limit,
            ],
        ]);
    }
}
