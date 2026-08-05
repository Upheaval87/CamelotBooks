<?php

namespace App\Services\Tenancy;

use App\Models\Company;
use App\Services\ModuleRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompanyProvisioningService
{
    private const DB_NAME_PREFIX = 'acct_';
    private const DB_NAME_SUFFIX_LENGTH = 8;
    private const MAX_DB_NAME_LENGTH = 64;

    public function __construct(
        private readonly TenantDefaultsSeeder $defaultsSeeder,
        private string $migrationPath = 'database/migrations/tenant',
    ) {
    }

    /**
     * Provision a tenant database for a company.
     *
     * When `$seedDefaults` is true (default), the tenant is seeded with a default
     * chart of accounts, fiscal year, periods, approvals and numbering sequences.
     * Data-migration mode passes false so the tenant starts empty and the real
     * company data is copied in by {@see CompanyDataMigrator}.
     */
    public function provision(Company $company, ?string $migrationPath = null, bool $seedDefaults = true): Company
    {
        if ($company->provisioning_status === Company::STATUS_ACTIVE) {
            throw new \RuntimeException("Company [{$company->id}] is already provisioned.");
        }

        $dbName = null;
        $centralConnection = DB::getDefaultConnection();

        $company->forceFill([
            'provisioning_status' => Company::STATUS_PROVISIONING,
            'last_provisioning_error' => null,
        ])->save();

        try {
            $dbName = $this->generateDatabaseName($company);
            $this->createDatabase($dbName);

            $connection = $this->registerConnection($company, $dbName);

            $this->runMigrations($connection, $migrationPath ?? $this->migrationPath);

            if ($seedDefaults) {
                $this->defaultsSeeder->seed($company, $connection, $centralConnection);
            } else {
                // migrate --database switches the app default connection; restore it
                // here because the defaults seeder (which normally restores it in a
                // finally block) is being skipped.
                DB::setDefaultConnection($centralConnection);
            }

            $this->seedCompanyModules($company);

            $company->forceFill([
                'provisioning_status' => Company::STATUS_ACTIVE,
                'db_name' => $dbName,
                'db_host' => $company->db_host,
                'db_port' => $company->db_port,
                'db_username' => $company->db_username,
                'provisioned_at' => now(),
                'last_provisioning_error' => null,
            ])->save();

            return $company->fresh();
        } catch (\Throwable $e) {
            DB::setDefaultConnection($centralConnection);

            if ($dbName !== null) {
                try {
                    $this->dropDatabase($dbName);
                } catch (\Throwable $dropError) {
                    Log::error("Failed to drop tenant database [{$dbName}] during rollback: {$dropError->getMessage()}");
                }
            }

            $company->forceFill([
                'provisioning_status' => Company::STATUS_FAILED,
                'db_name' => null,
                'last_provisioning_error' => Str::limit($e->getMessage(), 2000),
            ])->save();

            Log::error("Company [{$company->id}] provisioning failed", [
                'company' => $company->id,
                'db_name' => $dbName,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    public function generateDatabaseName(Company $company): string
    {
        $seed = Str::lower(Str::slug($company->company_code ?: $company->name, '_'));
        $seed = preg_replace('/[^a-z0-9_]/', '', $seed);
        $seed = substr($seed, 0, self::MAX_DB_NAME_LENGTH - strlen(self::DB_NAME_PREFIX) - 1 - self::DB_NAME_SUFFIX_LENGTH);

        do {
            $suffix = substr(bin2hex(random_bytes(4)), 0, self::DB_NAME_SUFFIX_LENGTH);
            $name = self::DB_NAME_PREFIX . $seed . '_' . $suffix;
        } while (strlen($name) > self::MAX_DB_NAME_LENGTH || $this->databaseExists($name) || Company::where('db_name', $name)->exists());

        return $name;
    }

    private function createDatabase(string $name): void
    {
        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new \RuntimeException("Invalid database name [{$name}]");
        }

        DB::connection('provisioning')->statement(
            "CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    private function dropDatabase(string $name): void
    {
        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new \RuntimeException("Invalid database name [{$name}]");
        }

        DB::connection('provisioning')->statement("DROP DATABASE IF EXISTS `{$name}`");
    }

    private function databaseExists(string $name): bool
    {
        return (bool) DB::connection('provisioning')->selectOne(
            'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$name]
        );
    }

    /**
     * Register (or refresh) the runtime tenant connection for a company.
     * Idempotent — used by both provisioning and verify-only flows.
     */
    public function registerConnection(Company $company, string $dbName): string
    {
        $base = config('database.connections.mysql');

        $connection = array_merge($base, [
            'database' => $dbName,
            'host' => $company->db_host ?: $base['host'],
            'port' => $company->db_port ?: $base['port'],
            'username' => $company->db_username ?: $base['username'],
            'password' => $company->db_password ?: $base['password'],
        ]);

        unset($connection['url']);

        $name = "tenant_{$company->id}";

        Config::set("database.connections.{$name}", $connection);
        DB::purge($name);

        return $name;
    }

    private function runMigrations(string $connection, string $path): void
    {
        $exitCode = Artisan::call('migrate', [
            '--database' => $connection,
            '--path' => $path,
            '--realpath' => $this->isAbsolutePath($path),
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Tenant migration failed: ' . Artisan::output());
        }
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[a-zA-Z]:[\\\\\/]/', $path) === 1;
    }

    private function seedCompanyModules(Company $company): void
    {
        $now = now();

        foreach (ModuleRegistry::catalog() as $code => $module) {
            DB::table('modules')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $module['name'],
                    'description' => $module['description'] ?? null,
                    'is_core' => $module['is_core'],
                    'sort_order' => $module['sort_order'],
                    'updated_at' => $now,
                ]
            );
        }

        $moduleIds = DB::table('modules')->pluck('is_core', 'id');

        foreach ($moduleIds as $moduleId => $isCore) {
            DB::table('company_modules')->updateOrInsert(
                ['company_id' => $company->id, 'module_id' => $moduleId],
                [
                    'is_active' => (bool) $isCore,
                    'activated_at' => (bool) $isCore ? $now : null,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
