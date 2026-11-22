<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $query = AuditLog::with('user')->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('action', 'like', "%{$s}%")->orWhere('entity_name', 'like', "%{$s}%"));
        }

        $total = $query->count();
        $auditLogs = $query->skip(($page - 1) * $limit)->take($limit)->get()->map(fn($log) => [
            'id' => $log->id,
            'action' => $log->action,
            'entityName' => $log->entity_name,
            'entityId' => $log->entity_id,
            'details' => $log->details,
            'ipAddress' => $log->ip_address,
            'userName' => $log->user?->name ?? 'System',
            'createdAt' => $log->created_at?->toIso8601String(),
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'data' => $auditLogs,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ]);
        }

        return Inertia::render('protect/audit-logs/index', [
            'auditLogs' => [
                'data' => $auditLogs,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil(max($total, 1) / max($limit, 1)),
                ],
            ],
            'filters' => $request->only(['action', 'search', 'page', 'limit']),
        ]);
    }
}
