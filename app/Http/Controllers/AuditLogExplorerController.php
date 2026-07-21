<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogExplorerController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user || !$user->isAdmin()) {
                abort(403, 'Unauthorized access to system audit logs.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        // Apply Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->input('ip_address'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->latest()->paginate(30)->withQueryString();

        // Get dropdown options
        $users = User::whereHas('auditLogs')->orderBy('name')->get(['id', 'name']);
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit_logs.index', [
            'logs' => $logs,
            'users' => $users,
            'actions' => $actions,
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');
        
        return response()->json([
            'id' => $auditLog->id,
            'user' => $auditLog->user->name ?? 'System',
            'action' => $auditLog->action,
            'entity_type' => basename(str_replace('\\', '/', $auditLog->entity_type)),
            'entity_id' => $auditLog->entity_id,
            'ip_address' => $auditLog->ip_address,
            'created_at' => $auditLog->created_at->format('d M Y, h:i A'),
            'old_values' => $auditLog->old_values,
            'new_values' => $auditLog->new_values,
        ]);
    }
}
