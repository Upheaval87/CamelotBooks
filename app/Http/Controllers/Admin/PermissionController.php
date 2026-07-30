<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $modules = config('permissions.modules', []);
        $reportPermissions = config('permissions.reports', []);
        $roles = Role::where('guard_name', 'web')->orderBy('name')->get();
        $allPermissions = Permission::where('guard_name', 'web')->orderBy('name')->get()->keyBy('name');

        $selectedRole = null;
        if ($request->filled('role_id')) {
            $selectedRole = Role::with('permissions')->find($request->role_id);
        }

        return view('admin.permissions.index', compact('modules', 'reportPermissions', 'roles', 'allPermissions', 'selectedRole'));
    }

    public function sync(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $permissionIds = $validated['permissions'] ?? [];

        $role->syncPermissions($permissionIds);

        return redirect()->route('admin.permissions.index', ['role_id' => $role->id])
            ->with('success', "Permissions updated for role '{$role->name}'.");
    }
}
