<?php

namespace App\Services\Tenancy;

use App\Models\Company;
use App\Models\CompanyAccessLog;
use App\Models\User;

/**
 * Shared "enter a company" flow used by the two-step login (auto-select) and the
 * explicit company picker. Binds the tenant connection, records the session
 * company, updates the user's last-company field and writes an audit row.
 *
 * Super admins go through the SAME explicit path (never bound silently at login);
 * their entries are recorded with action = 'support'.
 */
class CompanyAccessService
{
    public function __construct(private readonly TenantConnectionResolver $resolver)
    {
    }

    public function enter(User $user, Company $company, string $action = CompanyAccessLog::ACTION_LOGIN): void
    {
        // Provisioned companies get the tenant connection bound; unprovisioned
        // companies run in legacy mode (shared DB) with no binding.
        if ($company->isProvisioned() && $company->is_active) {
            $this->resolver->resolve($company);
        }

        session(['current_company_id' => $company->id]);

        setPermissionsTeamId($company->id);

        $user->forceFill(['current_company_id' => $company->id])->save();

        if (config('tenancy.audit.enabled', true)) {
            CompanyAccessLog::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'action' => $action,
            ]);
        }
    }
}
