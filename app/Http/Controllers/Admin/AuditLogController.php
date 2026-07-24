<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $query = AuditLog::where('company_id', $companyId)->with('user');

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($module = $request->input('module')) {
            $query->where('auditable_type', 'App\\Models\\' . $module);
        }

        if ($from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $logs = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        $users = \App\Models\User::whereIn('id', function ($q) use ($companyId) {
            $q->select('user_id')->from('company_user')->where('company_id', $companyId);
        })->get();

        return view('admin.audit-log.index', compact('logs', 'users'));
    }

    public function exportCsv(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $query = AuditLog::where('company_id', $companyId)->with('user');

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }
        if ($from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $logs = $query->orderByDesc('created_at')->limit(5000)->get();

        $csv = "Date,User,Action,Subject Type,Subject ID,IP Address,Notes\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $log->created_at?->format('Y-m-d H:i:s') ?? '',
                $log->user?->name ?? 'System',
                $log->action,
                class_basename($log->auditable_type),
                $log->auditable_id,
                $log->ip_address ?? '',
                str_replace('"', '""', $log->notes ?? '')
            );
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit_log_' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}
