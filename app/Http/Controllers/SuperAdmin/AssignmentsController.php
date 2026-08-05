<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use App\Services\SuperAdmin\RoleCatalog;
use App\Services\SuperAdmin\TenantBranchReader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssignmentsController extends Controller
{
    public function index()
    {
        $assignments = UserCompanyAssignment::query()
            ->with(['user', 'company'])
            ->orderByDesc('id')
            ->paginate(25);

        return view('superadmin.assignments.index', compact('assignments'));
    }

    public function create(Request $request)
    {
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);
        $companies = Company::query()->orderBy('name')->get(['id', 'name', 'is_active', 'provisioning_status']);
        $roles = RoleCatalog::companyRoles();

        $preselectUser = $request->filled('user')
            ? User::query()->find((int) $request->query('user'))
            : null;

        return view('superadmin.assignments.create', compact('users', 'companies', 'roles', 'preselectUser'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'assignments' => 'required|array|min:1',
            'assignments.*.company_id' => 'required|exists:companies,id',
            'assignments.*.role' => ['required', Rule::in(array_keys(RoleCatalog::companyRoles()))],
            'assignments.*.branch_ids' => 'nullable|array',
            'assignments.*.branch_ids.*' => 'integer',
        ]);

        $user = User::query()->findOrFail((int) $validated['user_id']);

        $count = 0;
        foreach ($validated['assignments'] as $row) {
            $company = Company::query()->findOrFail((int) $row['company_id']);

            $assignment = UserCompanyAssignment::updateOrCreate(
                ['user_id' => $user->id, 'company_id' => $company->id],
                [
                    'role' => $row['role'],
                    'branch_ids' => array_values(array_map('intval', $row['branch_ids'] ?? [])),
                    'is_active' => true,
                ]
            );
            $count++;

            $this->syncCompanyRole($user, $company->id, $row['role']);

            SuperAdminAuditLog::log(
                $request->user()->id,
                SuperAdminAuditLog::ACTION_ASSIGNMENT_CREATED,
                $company->id,
                'assignment',
                $assignment->id,
                null,
                ['user_id' => $user->id, 'company_id' => $company->id, 'role' => $row['role'], 'branch_ids' => $assignment->branch_ids],
                "Assigned '{$user->name}' to '{$company->name}' as {$row['role']}."
            );
        }

        return redirect()->route('superadmin.users.show', $user)
            ->with('success', "Saved {$count} assignment".($count === 1 ? '' : 's').' for '.$user->name.'.');
    }

    public function edit(UserCompanyAssignment $assignment)
    {
        $assignment->load(['user', 'company']);
        $roles = RoleCatalog::companyRoles();
        $branches = app(TenantBranchReader::class)->branchesFor($assignment->company);

        return view('superadmin.assignments.edit', compact('assignment', 'roles', 'branches'));
    }

    public function update(Request $request, UserCompanyAssignment $assignment)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(RoleCatalog::companyRoles()))],
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $before = ['role' => $assignment->role, 'branch_ids' => $assignment->branch_ids, 'is_active' => $assignment->is_active];

        $assignment->update([
            'role' => $validated['role'],
            'branch_ids' => array_values(array_map('intval', $validated['branch_ids'] ?? [])),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->syncCompanyRole($assignment->user, $assignment->company_id, $validated['role']);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_ASSIGNMENT_UPDATED,
            $assignment->company_id,
            'assignment',
            $assignment->id,
            $before,
            ['role' => $assignment->role, 'branch_ids' => $assignment->branch_ids, 'is_active' => $assignment->is_active],
            "Assignment updated for '{$assignment->user->name}' in '{$assignment->company->name}'."
        );

        return redirect()->route('superadmin.users.show', $assignment->user)->with('success', 'Assignment updated.');
    }

    public function destroy(Request $request, UserCompanyAssignment $assignment)
    {
        $user = $assignment->user;
        $companyId = $assignment->company_id;

        $assignment->delete();

        $this->syncCompanyRole($user, $companyId, null);

        SuperAdminAuditLog::log(
            $request->user()->id,
            SuperAdminAuditLog::ACTION_ASSIGNMENT_DELETED,
            $companyId,
            'assignment',
            $assignment->id,
            ['user_id' => $user->id, 'company_id' => $companyId],
            null,
            "Assignment removed for '{$user->name}'."
        );

        return redirect()->route('superadmin.users.show', $user)->with('success', 'Assignment removed.');
    }

    /**
     * Keep the user's Spatie roles for a company in sync with the assignment.
     * Roles are team-scoped per company via setPermissionsTeamId().
     */
    private function syncCompanyRole(User $user, int $companyId, ?string $role): void
    {
        $currentTeamId = getPermissionsTeamId();

        setPermissionsTeamId($companyId);
        $user->syncRoles($role === null ? [] : [$role]);

        setPermissionsTeamId($currentTeamId);
    }
}
