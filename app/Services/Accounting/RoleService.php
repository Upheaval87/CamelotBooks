<?php

namespace App\Services\Accounting;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RoleService
{
    protected int $companyId;

    public const SENSITIVE_ACTIONS = [
        'delete', 'void', 'configure', 'override', 'unlock', 'reopen', 'recalculate',
        'configure_rules', 'restore', 'reverse', 'reset',
    ];

    public const MODULE_GROUPS = [
        'Financial Transactions' => [
            'invoices', 'credit-notes', 'sales-receipts', 'quotations', 'sales-orders',
            'bills', 'vendor-credits', 'expenses', 'expense-claims', 'expense-categories',
            'expense-recurring',
        ],
        'Customers & Vendors' => ['customers', 'vendors'],
        'Products & Inventory' => [
            'products', 'item-categories', 'inventory-items', 'stock-adjustments',
            'stock-transfers', 'stock-counts', 'assemblies', 'uom-conversions', 'landed-costs',
        ],
        'Purchasing' => [
            'purchase-requisitions', 'purchase-orders', 'goods-received-notes',
        ],
        'Payments' => ['customer-payments', 'vendor-payments'],
        'Banking' => [
            'bank-accounts', 'deposits', 'cheques', 'petty-cash', 'bank-reconciliations',
        ],
        'Accounting' => [
            'chart-of-accounts', 'journal-entries', 'accounting-periods', 'fiscal-years',
            'cost-centers', 'exchange-rates', 'recurring-journals', 'account-classification',
            'transaction-reversals',
        ],
        'Payroll' => ['employees', 'payroll-runs', 'paye-tables', 'pension-schemes'],
        'Fixed Assets' => [
            'asset-categories', 'fixed-assets', 'depreciation', 'asset-disposals',
            'asset-transfers', 'asset-impairments', 'asset-revaluations', 'asset-usage',
        ],
        'Budgets' => ['budgets'],
        'POS' => [
            'pos-sales', 'pos-terminals', 'pos-payment-methods', 'pos-till-sessions',
            'pos-settlements', 'pos-returns',
        ],
        'Administration' => [
            'system-settings', 'features', 'users', 'roles', 'numbering-sequences',
            'audit-log', 'backups', 'security-settings', 'notifications', 'system-health',
            'setup-wizard', 'companies', 'branches', 'branch-requests',
        ],
        'Analytics & BI' => ['analytics', 'bi'],
        'Tax' => ['tax-rates', 'tax-returns'],
    ];

    public const MODULE_ICONS = [
        'invoices' => 'IV', 'credit-notes' => 'CN', 'sales-receipts' => 'SR',
        'quotations' => 'QT', 'sales-orders' => 'SO', 'bills' => 'BL',
        'vendor-credits' => 'VC', 'expenses' => 'EX', 'expense-claims' => 'EC',
        'expense-categories' => 'EC', 'expense-recurring' => 'ER',
        'customers' => 'CU', 'vendors' => 'VE',
        'products' => 'PR', 'item-categories' => 'IC', 'inventory-items' => 'II',
        'stock-adjustments' => 'SA', 'stock-transfers' => 'ST', 'stock-counts' => 'SC',
        'assemblies' => 'AS', 'uom-conversions' => 'UC', 'landed-costs' => 'LC',
        'purchase-requisitions' => 'RQ', 'purchase-orders' => 'PO',
        'goods-received-notes' => 'GR',
        'customer-payments' => 'CP', 'vendor-payments' => 'VP',
        'bank-accounts' => 'BA', 'deposits' => 'DP', 'cheques' => 'CH',
        'petty-cash' => 'PC', 'bank-reconciliations' => 'BR',
        'chart-of-accounts' => 'CO', 'journal-entries' => 'JE',
        'accounting-periods' => 'AP', 'fiscal-years' => 'FY',
        'cost-centers' => 'CC', 'exchange-rates' => 'XR',
        'recurring-journals' => 'RJ', 'account-classification' => 'AC',
        'transaction-reversals' => 'TR',
        'employees' => 'EM', 'payroll-runs' => 'PR', 'paye-tables' => 'PT',
        'pension-schemes' => 'PS',
        'asset-categories' => 'AC', 'fixed-assets' => 'FA', 'depreciation' => 'DA',
        'asset-disposals' => 'AD', 'asset-transfers' => 'AT',
        'asset-impairments' => 'AI', 'asset-revaluations' => 'AV',
        'asset-usage' => 'AU',
        'budgets' => 'BG',
        'pos-sales' => 'PS', 'pos-terminals' => 'PT', 'pos-payment-methods' => 'PM',
        'pos-till-sessions' => 'TS', 'pos-settlements' => 'SE', 'pos-returns' => 'PR',
        'system-settings' => 'SS', 'features' => 'FT', 'users' => 'US',
        'roles' => 'RL', 'numbering-sequences' => 'NS', 'audit-log' => 'AL',
        'backups' => 'BK', 'security-settings' => 'SC', 'notifications' => 'NT',
        'system-health' => 'SH', 'setup-wizard' => 'SW', 'companies' => 'CO',
        'branches' => 'BR', 'branch-requests' => 'BQ',
        'analytics' => 'AN', 'bi' => 'BI',
        'tax-rates' => 'TR', 'tax-returns' => 'TX',
    ];

    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId ?? (int) session('current_company_id');
    }

    public function getCatalog(): array
    {
        return [
            'modules' => config('permissions.modules', []),
            'reports' => config('permissions.reports', []),
        ];
    }

    public function getModuleGroups(): array
    {
        return self::MODULE_GROUPS;
    }

    public function getModuleIcon(string $module): string
    {
        return self::MODULE_ICONS[$module] ?? strtoupper(substr($module, 0, 2));
    }

    public function isSensitive(string $action): bool
    {
        return in_array($action, self::SENSITIVE_ACTIONS, true);
    }

    public function getRoleSummaries()
    {
        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->withCount(['permissions as permission_count', 'users as user_count'])
            ->get();

        return $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'label' => ucfirst(str_replace('_', ' ', $role->name)),
                'initials' => collect(explode(' ', $role->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->implode(''),
                'permission_count' => $role->permission_count,
                'user_count' => $role->user_count,
                'is_active' => $role->is_active,
                'updated_at' => $role->updated_at?->toISOString(),
            ];
        });
    }

    public const STANDARD_ACTIONS = [
        'view', 'create', 'edit', 'delete', 'submit', 'approve',
        'post', 'reverse', 'export', 'configure',
    ];

    public function getMatrix(Role $role): array
    {
        $catalog = $this->getCatalog();
        $granted = $role->permissions->pluck('name')->flip()->toArray();

        $modules = [];
        foreach ($catalog['modules'] as $moduleKey => $configActions) {
            $group = $this->findModuleGroup($moduleKey);
            $features = [];

            $allActions = array_values(array_unique(array_merge($configActions, self::STANDARD_ACTIONS)));

            foreach ($allActions as $action) {
                $permName = $moduleKey . '.' . $action;
                $features[] = [
                    'module' => $moduleKey,
                    'action' => $action,
                    'permission' => $permName,
                    'granted' => isset($granted[$permName]),
                    'sensitive' => $this->isSensitive($action),
                ];
            }

            $modules[] = [
                'key' => $moduleKey,
                'label' => ucfirst(str_replace(['-', '_'], ' ', $moduleKey)),
                'icon' => $this->getModuleIcon($moduleKey),
                'group' => $group,
                'actions' => $features,
            ];
        }

        $reports = [];
        foreach ($catalog['reports'] as $reportKey => $reportLabel) {
            $reportActions = ['view', 'export', 'print', 'email', 'schedule'];
            $reportPerms = [];
            foreach ($reportActions as $action) {
                $permName = 'reports.' . $reportKey . '.' . $action;
                $reportPerms[] = [
                    'module' => 'reports.' . $reportKey,
                    'action' => $action,
                    'permission' => $permName,
                    'granted' => isset($granted[$permName]),
                    'sensitive' => false,
                ];
            }
            $reports[] = [
                'key' => $reportKey,
                'label' => $reportLabel,
                'group' => 'Reports',
                'actions' => $reportPerms,
            ];
        }

        return ['modules' => $modules, 'reports' => $reports];
    }

    public function countGranted(array $matrix): int
    {
        $count = 0;
        foreach (['modules', 'reports'] as $type) {
            foreach ($matrix[$type] as $item) {
                foreach ($item['actions'] as $a) {
                    if ($a['granted']) $count++;
                }
            }
        }
        return $count;
    }

    public function countSensitive(array $matrix): int
    {
        $count = 0;
        foreach (['modules', 'reports'] as $type) {
            foreach ($matrix[$type] as $item) {
                foreach ($item['actions'] as $a) {
                    if ($a['granted'] && $a['sensitive']) $count++;
                }
            }
        }
        return $count;
    }

    public function savePermissions(Role $role, array $permissionNames, int $userId): array
    {
        $guard = 'web';
        $allPerms = \App\Models\Permission::where('guard_name', $guard)->get()->keyBy('name');
        $permissionIds = [];

        foreach ($permissionNames as $name) {
            if (isset($allPerms[$name])) {
                $permissionIds[] = $allPerms[$name]->id;
            } else {
                $perm = \App\Models\Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => $guard,
                ]);
                $permissionIds[] = $perm->id;
            }
        }

        return DB::transaction(function () use ($role, $permissionIds, $permissionNames, $userId, $guard, $allPerms) {
            $oldPerms = $role->permissions->pluck('name', 'id')->toArray();
            $oldNames = array_values($oldPerms);

            $role->permissions()->sync($permissionIds);

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            if ($role->wasRecentlyCreated || $role->wasChanged('is_active')) {
                $role->update(['is_active' => $role->is_active]);
            }

            $added = array_diff($permissionNames, $oldNames);
            $removed = array_diff($oldNames, $permissionNames);

            foreach (['add' => $added, 'remove' => $removed] as $action => $diff) {
                foreach ($diff as $permName) {
                    DB::table('auth_audit_log')->insertOrIgnore([
                        'company_id' => $this->companyId,
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'permission' => $permName,
                        'action' => $action === 'add' ? 'grant' : 'revoke',
                        'user_id' => $userId,
                        'ip_address' => request()->ip(),
                        'created_at' => now(),
                    ]);
                }
            }

            return ['ok' => true, 'added' => count($added), 'removed' => count($removed)];
        });
    }

    public function createRole(array $data, ?int $sourceRoleId, int $userId): Role
    {
        return DB::transaction(function () use ($data, $sourceRoleId, $userId) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
                'is_active' => true,
            ]);

            if ($sourceRoleId) {
                $sourceRole = Role::findOrFail($sourceRoleId);
                $sourcePerms = $sourceRole->permissions->pluck('id')->toArray();
                if (!empty($sourcePerms)) {
                    $role->permissions()->sync($sourcePerms);
                    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                }
            }

            return $role;
        });
    }

    public function toggleActive(Role $role, int $userId): array
    {
        $newActive = !$role->is_active;

        if (!$newActive) {
            $userCount = $role->users()->count();
            if ($userCount > 0) {
                $role->update(['is_active' => false]);
                return ['ok' => true, 'deactivated' => true, 'user_count' => $userCount];
            }
        }

        $role->update(['is_active' => $newActive]);
        return ['ok' => true, 'deactivated' => !$newActive, 'user_count' => $role->users()->count()];
    }

    public function wouldSelfLockout(Role $role, array $permissionNames, int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        $userRoleIds = $user->roles->pluck('id')->toArray();
        if (!in_array($role->id, $userRoleIds)) return false;

        $hasRoleMgmt = in_array('roles.view', $permissionNames) || in_array('roles.create', $permissionNames) || in_array('roles.edit', $permissionNames);

        if (!$hasRoleMgmt) {
            $otherRolesWithMgmt = Role::where('id', '!=', $role->id)
                ->whereIn('id', $userRoleIds)
                ->whereHas('permissions', fn($q) => $q->whereIn('name', ['roles.view', 'roles.create', 'roles.edit']))
                ->exists();
            return !$otherRolesWithMgmt;
        }

        return false;
    }

    protected function findModuleGroup(string $moduleKey): string
    {
        foreach (self::MODULE_GROUPS as $group => $modules) {
            if (in_array($moduleKey, $modules)) {
                return $group;
            }
        }
        return 'Other';
    }
}
