<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SuperAdminAuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SuperAdminAuditLog::query()->with(['user', 'company']);

        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->input('company_id'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        $logs = $query->latest('created_at')->paginate(25)->withQueryString();
        $companies = Company::query()->orderBy('name')->get(['id', 'name']);
        $actions = SuperAdminAuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('superadmin.audit.index', compact('logs', 'companies', 'actions'));
    }
}
