<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SuperAdminAuditLog;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $companyCount = Company::query()->count();
        $activeCompanyCount = Company::query()
            ->where('is_active', true)
            ->where('provisioning_status', Company::STATUS_ACTIVE)
            ->count();
        $userCount = User::query()->count();
        $recentAudit = SuperAdminAuditLog::query()
            ->with(['user', 'company'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('superadmin.dashboard', compact('companyCount', 'activeCompanyCount', 'userCount', 'recentAudit'));
    }
}
