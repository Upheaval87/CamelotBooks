<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $users = User::whereHas('companies', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->with(['companies' => function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        }])->get();

        return view('admin.users.index', compact('users'));
    }

    public function edit(Request $request, User $user)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $pivot = $user->companies()->where('company_id', $companyId)->first()?->pivot;

        return view('admin.users.edit', compact('user', 'pivot'));
    }

    public function update(Request $request, User $user)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $validated = $request->validate([
            'role' => 'required|in:system_admin,company_admin,accountant,approver,viewer',
        ]);

        $user->companies()->updateExistingPivot($companyId, [
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User role updated successfully.');
    }

    public function toggle2fa(Request $request, User $user)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $user->update([
            'two_factor_enabled' => !$user->two_factor_enabled,
        ]);

        $action = $user->two_factor_enabled ? 'enabled' : 'disabled';

        return redirect()->route('admin.users.edit', $user)
            ->with('success', "Two-factor authentication {$action} for {$user->name}.");
    }
}
