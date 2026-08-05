<?php

namespace App\Services\SuperAdmin;

use App\Models\Branch;
use App\Models\Company;
use App\Services\Tenancy\TenantConnectionResolver;

class TenantBranchReader
{
    public function __construct(private readonly TenantConnectionResolver $resolver)
    {
    }

    /**
     * Branches for a provisioned company, read from its TENANT database.
     * Returns [] for unprovisioned/inactive companies or any connection failure.
     *
     * @return array<int, array{id: int, name: string, code: string|null}>
     */
    public function branchesFor(Company $company): array
    {
        if (! $company->isProvisioned() || ! $company->is_active) {
            return [];
        }

        try {
            $this->resolver->resolve($company);

            $branches = Branch::query()
                ->where('company_id', $company->id)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->toArray();
        } catch (\Throwable) {
            $branches = [];
        } finally {
            $this->resolver->clear();
        }

        return $branches;
    }
}
