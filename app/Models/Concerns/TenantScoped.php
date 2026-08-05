<?php

namespace App\Models\Concerns;

use App\Services\Tenancy\TenantConnectionResolver;

/**
 * Resolves this model's connection at query time.
 *
 * When a tenant connection has been bound for the current request, the model
 * queries the `tenant` database (or the test `connection_override`). When no
 * tenant context is bound — CLI commands, provisioning/seeding, unbound tests —
 * the model falls back to the application default (central) connection.
 *
 * Apply this trait to every company-scoped model. CENTRAL models (User, Company,
 * Module, CompanyModule, UserCompanyAssignment, CompanyAccessLog) must NOT use it.
 */
trait TenantScoped
{
    public function getConnectionName(): ?string
    {
        return TenantConnectionResolver::connectionName();
    }
}
