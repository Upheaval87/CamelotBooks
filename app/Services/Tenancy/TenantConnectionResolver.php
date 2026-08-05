<?php

namespace App\Services\Tenancy;

use App\Models\Company;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Resolves and binds the runtime `tenant` connection for the current request.
 *
 * The application default connection always stays the CENTRAL database. This
 * service registers a second, dynamically-configured connection named `tenant`
 * built from the central company record (db_name + optional per-company
 * credentials). Eloquent models using the TenantScoped trait switch to it via
 * TenantScoped::getConnectionName() -> TenantConnectionResolver::connectionName().
 *
 * Binding is idempotent per request: resolving the same company with the same
 * config is a no-op, so middleware can run on every request without reconnecting.
 */
class TenantConnectionResolver
{
    public const CONNECTION_NAME = 'tenant';

    private ?int $boundCompanyId = null;

    private string $boundConfigHash = '';

    /**
     * The connection name tenant models should query, or null when no tenant is bound.
     */
    public static function connectionName(): ?string
    {
        return app(static::class)->resolvedConnectionName();
    }

    public function resolvedConnectionName(): ?string
    {
        if (!$this->isBound()) {
            return null;
        }

        $override = $this->connectionOverride();

        return $override ?? self::CONNECTION_NAME;
    }

    /**
     * Bind the tenant connection for a company. Throws when the company is not
     * provisioned/active or has no tenant database.
     */
    public function resolve(Company $company, bool $force = false): void
    {
        if (!$company->isProvisioned() || !$company->is_active || blank($company->db_name)) {
            throw new \RuntimeException(
                "Company [{$company->id}] is not provisioned and active for tenant routing."
            );
        }

        $hash = $this->configHash($company);

        if (!$force && $this->isBound() && $this->boundCompanyId === $company->id && $this->boundConfigHash === $hash) {
            return;
        }

        if ($this->connectionOverride() === null) {
            $this->registerTenantConnection($company);
        }

        $this->boundCompanyId = $company->id;
        $this->boundConfigHash = $hash;
    }

    /**
     * Bind the tenant connection for a company id, loading the central record.
     */
    public function resolveForCompanyId(int $companyId, bool $force = false): Company
    {
        $company = Company::query()->find($companyId);

        if (!$company) {
            throw new \InvalidArgumentException("Company [{$companyId}] does not exist.");
        }

        $this->resolve($company, $force);

        return $company;
    }

    /**
     * Forget the bound tenant connection for the current process.
     */
    public function clear(): void
    {
        $name = self::CONNECTION_NAME;

        if (Config::has("database.connections.{$name}")) {
            DB::purge($name);
            Config::set("database.connections.{$name}", null);
        }

        $this->boundCompanyId = null;
        $this->boundConfigHash = '';
    }

    public function isBound(): bool
    {
        return $this->boundCompanyId !== null;
    }

    public function boundCompanyId(): ?int
    {
        return $this->boundCompanyId;
    }

    /**
     * The connection config that would be registered for a company.
     * Public so tests can assert credentials are built correctly.
     */
    public function connectionConfig(Company $company): array
    {
        $base = config('database.connections.' . config('tenancy.routing.base_connection', 'mysql'), []);

        $config = array_merge($base, [
            'database' => $company->db_name,
            'host' => $company->db_host ?: ($base['host'] ?? '127.0.0.1'),
            'port' => $company->db_port ?: ($base['port'] ?? 3306),
            'username' => $company->db_username ?: ($base['username'] ?? 'root'),
            'password' => $company->db_password ?: ($base['password'] ?? ''),
        ]);

        unset($config['url']);

        return $config;
    }

    private function registerTenantConnection(Company $company): void
    {
        $name = self::CONNECTION_NAME;

        if (Config::has("database.connections.{$name}")) {
            DB::purge($name);
        }

        Config::set("database.connections.{$name}", $this->connectionConfig($company));
    }

    private function configHash(Company $company): string
    {
        return md5(json_encode([
            'db' => $company->db_name,
            'host' => $company->db_host,
            'port' => $company->db_port,
            'user' => $company->db_username,
            'pass' => $company->db_password,
        ]));
    }

    private function connectionOverride(): ?string
    {
        $override = config('tenancy.routing.connection_override');

        return is_string($override) && $override !== '' ? $override : null;
    }
}
