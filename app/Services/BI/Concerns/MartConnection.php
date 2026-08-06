<?php

namespace App\Services\BI\Concerns;

use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the database connection backing the BI data mart.
 *
 * The mart lives per-tenant, so it must be read/written on the bound `tenant`
 * connection while a company is active, and on the application default otherwise
 * (unbound contexts and the sqlite-backed test suite). Passing a null connection
 * name to DB::connection() falls back to the default connection, so a single
 * expression covers both cases.
 */
trait MartConnection
{
    protected function mart(): Connection
    {
        return DB::connection(TenantConnectionResolver::connectionName());
    }

    protected function martTable(string $table): Builder
    {
        return $this->mart()->table($table);
    }
}
