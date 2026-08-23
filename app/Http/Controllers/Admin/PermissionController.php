<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Accounting\RoleService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $service = new RoleService();
        $roleSummaries = $service->getRoleSummaries();
        $selectedRole = null;
        $matrix = null;
        $moduleGroups = $service->getModuleGroups();

        if ($request->filled('role_id')) {
            $selectedRole = Role::with('permissions')->find($request->role_id);
            if ($selectedRole) {
                $matrix = $service->getMatrix($selectedRole);
            }
        }

        return view('admin.permissions.index', compact(
            'roleSummaries', 'selectedRole', 'matrix', 'moduleGroups'
        ));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'source_role_id' => 'nullable|exists:roles,id',
        ]);

        $service = new RoleService();
        $role = $service->createRole($validated, $validated['source_role_id'] ?? null, $request->user()->id);

        return response()->json(['ok' => true, 'role_id' => $role->id, 'name' => $role->name]);
    }

    public function savePermissions(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'required|array',
            'permissions.*' => 'string|max:100',
            'expected_updated_at' => 'nullable|string',
            'force' => 'nullable|boolean',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $service = new RoleService();

        if (isset($validated['expected_updated_at']) && empty($validated['force'])) {
            $expected = \Carbon\Carbon::parse($validated['expected_updated_at']);
            $actual = $role->updated_at;
            if ($actual && $actual->gt($expected)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'conflict',
                    'message' => 'This role\'s permissions changed since you loaded them — reload to see the latest and reapply your changes.',
                ], 409);
            }
        }

        if ($service->wouldSelfLockout($role, $validated['permissions'], $request->user()->id)) {
            return response()->json([
                'ok' => false,
                'error' => 'lockout',
                'message' => 'Saving this change would lock you out of role management. At least one of your roles must retain roles.view, roles.create, or roles.edit permission.',
            ], 422);
        }

        $result = $service->savePermissions($role, $validated['permissions'], $request->user()->id);

        $role->refresh();

        return response()->json([
            'ok' => true,
            'added' => $result['added'],
            'removed' => $result['removed'],
            'updated_at' => $role->updated_at?->toISOString(),
        ]);
    }

    public function toggleActive(Request $request, Role $role)
    {
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $service = new RoleService();
        $result = $service->toggleActive($role, $request->user()->id);

        return response()->json($result);
    }

    public function getRolePermissions(Request $request, Role $role)
    {
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $service = new RoleService();
        $matrix = $service->getMatrix($role);
        $granted = $role->permissions->pluck('name')->toArray();

        return response()->json([
            'ok' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => ucfirst(str_replace('_', ' ', $role->name)),
                'is_active' => $role->is_active,
                'updated_at' => $role->updated_at?->toISOString(),
                'permission_count' => count($granted),
            ],
            'granted' => $granted,
            'matrix' => $matrix,
        ]);
    }
}
