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
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $companyId = $request->user()->getActiveCompanyId();

        $users = User::whereHas('companies', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->with(['companies' => function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        }])->get();

        return view('admin.users.index', compact('users'));
    }

    public function edit(Request $request, User $user)
    {
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $companyId = $request->user()->getActiveCompanyId();

        $pivot = $user->companies()->where('company_id', $companyId)->first()?->pivot;

        return view('admin.users.edit', compact('user', 'pivot'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $companyId = $request->user()->getActiveCompanyId();

        $validated = $request->validate([
            'role' => 'required|in:system_admin,company_admin,accountant,approver,viewer',
        ]);

        $user->companies()->updateExistingPivot($companyId, [
            'role' => $validated['role'],
        ]);

        $currentTeamId = getPermissionsTeamId();
        setPermissionsTeamId($companyId);
        $user->syncRoles([$validated['role']]);
        setPermissionsTeamId($currentTeamId);

        return redirect()->route('admin.users.index')->with('success', 'User role updated successfully.');
    }

    public function toggle2fa(Request $request, User $user)
    {
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $companyId = $request->user()->getActiveCompanyId();

        $user->update([
            'two_factor_enabled' => !$user->two_factor_enabled,
        ]);

        $action = $user->two_factor_enabled ? 'enabled' : 'disabled';

        return redirect()->route('admin.users.edit', $user)
            ->with('success', "Two-factor authentication {$action} for {$user->name}.");
    }
}
