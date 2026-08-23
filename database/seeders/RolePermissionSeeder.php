<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permConfig = config('permissions');

        // ── 1. Create all module permissions ──
        $allPermissions = [];
        foreach ($permConfig['modules'] as $module => $actions) {
            foreach ($actions as $action) {
                $permName = "{$module}.{$action}";
                $allPermissions[$permName] = Permission::firstOrCreate([
                    'name' => $permName,
                    'guard_name' => 'web',
                ])->id;
            }
        }

        // ── 2. Create all report permissions (view + export/print/email/schedule) ──
        $reportKeys = [];
        $reportActions = ['view', 'export', 'print', 'email', 'schedule'];
        foreach ($permConfig['reports'] as $key => $label) {
            $reportKeys[] = $key;
            foreach ($reportActions as $action) {
                $permName = "reports.{$key}.{$action}";
                if (!isset($allPermissions[$permName])) {
                    $perm = Permission::firstOrCreate([
                        'name' => $permName,
                        'guard_name' => 'web',
                    ]);
                    $allPermissions[$permName] = $perm->id;
                }
            }
        }

        // ── 3. Build a flat permission-name → ID lookup ──
        $permNameToId = [];
        foreach (Permission::all() as $p) {
            $permNameToId[$p->name] = $p->id;
        }

        /**
         * Resolve a permission pattern to a list of permission names.
         * Supports:
         *   '*'          → all permissions
         *   'module.*'   → all actions for that module
         *   'reports.*'  → all report permissions
         *   'module.action' → that specific permission
         */
        $resolve = function (string $pattern) use ($permConfig, $reportKeys, $reportActions): array {
            if ($pattern === '*') {
                $names = [];
                foreach ($permConfig['modules'] as $module => $actions) {
                    foreach ($actions as $action) {
                        $names[] = "{$module}.{$action}";
                    }
                }
                foreach ($reportKeys as $key) {
                    foreach ($reportActions as $action) {
                        $names[] = "reports.{$key}.{$action}";
                    }
                }
                return array_unique($names);
            }

            if ($pattern === 'reports.*') {
                $names = [];
                foreach ($reportKeys as $key) {
                    foreach ($reportActions as $action) {
                        $names[] = "reports.{$key}.{$action}";
                    }
                }
                return $names;
            }

            // Check if it's a "module.*" pattern
            $parts = explode('.', $pattern);
            if (count($parts) === 2 && $parts[1] === '*' && isset($permConfig['modules'][$parts[0]])) {
                $module = $parts[0];
                return array_map(fn($a) => "{$module}.{$a}", $permConfig['modules'][$module]);
            }

            return [$pattern];
        };

        // Cache resolved permission name lists for each role
        $rolePermsCache = [];

        // ── 4. Create roles and assign permissions ──
        foreach ($permConfig['roles'] as $roleName => $roleDef) {
            $roleData = ['name' => $roleName, 'guard_name' => 'web'];

            // system_admin is global — explicitly set company_id to null
            if (($roleDef['scope'] ?? 'company') === 'global') {
                $roleData['company_id'] = null;
            }

            $role = Role::firstOrCreate($roleData);

            // Resolve all permission patterns for this role
            $resolvedNames = [];
            foreach ($roleDef['permissions'] as $pattern) {
                $resolvedNames = array_merge($resolvedNames, $resolve($pattern));
            }
            $resolvedNames = array_unique($resolvedNames);

            // Filter to only permissions that actually exist
            $validIds = [];
            foreach ($resolvedNames as $name) {
                if (isset($permNameToId[$name])) {
                    $validIds[] = $permNameToId[$name];
                }
            }

            $role->syncPermissions($validIds);
            $rolePermsCache[$roleName] = $resolvedNames;
        }
    }
}
