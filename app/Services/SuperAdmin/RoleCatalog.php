<?php

namespace App\Services\SuperAdmin;

use Illuminate\Support\Str;

class RoleCatalog
{
    /**
     * Company-scoped roles from the canonical config/permissions.php catalog.
     * system_admin is excluded: it is the platform-global role backed by the
     * central is_super_admin flag, not a role you assign within a company.
     *
     * @return array<string, string> role code => human label
     */
    public static function companyRoles(): array
    {
        return collect(config('permissions.roles', []))
            ->filter(fn (array $def) => ($def['scope'] ?? 'company') !== 'global')
            ->map(fn (array $def, string $code) => $def['label'] ?? Str::headline($code))
            ->all();
    }

    public static function isValidRole(string $role): bool
    {
        return array_key_exists($role, self::companyRoles());
    }
}
