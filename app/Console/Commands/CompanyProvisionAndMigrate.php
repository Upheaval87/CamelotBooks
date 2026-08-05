<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Tenancy\CompanyDataMigrator;
use App\Services\Tenancy\CompanyProvisioningService;
use App\Services\Verification\CompanyMigrationVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CompanyProvisionAndMigrate extends Command
{
    protected $signature = 'company:provision-and-migrate
        {--company= : Company ID to provision and migrate}
        {--source=mysql : Source connection holding the shared data}
        {--verify-only : Skip provisioning and only verify an existing tenant}
        {--yes : Skip the confirmation prompt}';

    protected $description = 'Provision a tenant database and copy an existing company\'s data out of the shared database';

    public function handle(CompanyProvisioningService $provisioner, CompanyDataMigrator $migrator, CompanyMigrationVerifier $verifier): int
    {
        $company = $this->resolveCompany();
        if ($company === null) {
            return static::FAILURE;
        }

        if ($this->option('verify-only')) {
            return $this->verifyOnly($company, $provisioner, $verifier);
        }

        if ($company->isProvisioned()) {
            $this->error("Company [{$company->id}] is already provisioned (db: {$company->db_name}). Nothing to do.");
            return static::FAILURE;
        }

        if (!$this->runPreflight()) {
            return static::FAILURE;
        }

        $sourceConnection = (string) $this->option('source');

        $this->info('');
        $this->info("Company: {$company->name} (id: {$company->id})");
        $this->line("Source connection: {$sourceConnection}");

        $this->table(
            ['Step', 'Detail'],
            $this->planRows($company, $migrator, $sourceConnection),
        );

        if (!$this->option('yes')) {
            $this->info('');
            if (!$this->confirm('Provision a new tenant database for this company and copy its data? This does NOT modify the shared database.', false)) {
                $this->warn('Aborted.');
                return static::FAILURE;
            }
        }

        $this->info('');
        $this->info('Provisioning tenant schema (migrate only, no default seeding)...');

        try {
            $company = $provisioner->provision($company, null, false);
        } catch (\Throwable $e) {
            $this->error('Provisioning failed: ' . $e->getMessage());
            return static::FAILURE;
        }

        $tenantConnection = "tenant_{$company->id}";
        $dbName = $company->db_name;

        $this->info("Tenant database created: {$dbName}");
        $this->info('Copying data...');

        try {
            $copied = $migrator->migrate($company, $tenantConnection, $sourceConnection);
        } catch (\Throwable $e) {
            $this->rollback($company, $dbName, $e);
            return static::FAILURE;
        }

        $this->info('Data copy complete.');
        $this->table(['Table', 'Rows copied'], array_map(
            static fn (string $table, int $count): array => [$table, $count],
            array_keys($copied),
            array_values($copied),
        ));

        return $this->runVerification($company, $tenantConnection, $sourceConnection, $verifier, $dbName);
    }

    private function verifyOnly(Company $company, CompanyProvisioningService $provisioner, CompanyMigrationVerifier $verifier): int
    {
        if (!$company->isProvisioned() || $company->db_name === null) {
            $this->error("Company [{$company->id}] is not provisioned.");
            return static::FAILURE;
        }

        // Register the runtime tenant connection without re-provisioning.
        $provisioner->registerConnection($company, $company->db_name);

        return $this->runVerification($company, "tenant_{$company->id}", (string) $this->option('source'), $verifier, $company->db_name, false);
    }

    private function runVerification(Company $company, string $tenantConnection, string $sourceConnection, CompanyMigrationVerifier $verifier, ?string $dbName, bool $rollbackOnFailure = true): int
    {
        $this->info('Verifying migration...');

        try {
            $result = $verifier->verify($company, $tenantConnection, $sourceConnection);
        } catch (\Throwable $e) {
            if ($rollbackOnFailure) {
                $this->rollback($company, $dbName, $e);
            } else {
                $this->error('Verification threw an exception (tenant left in place for inspection): ' . $e->getMessage());
            }
            return static::FAILURE;
        }

        $rows = array_map(
            static fn (array $c): array => [
                $c['name'],
                $c['status'],
                is_array($c['expected']) ? json_encode($c['expected']) : (string) $c['expected'],
                is_array($c['actual']) ? json_encode($c['actual']) : (string) $c['actual'],
                $c['detail'],
            ],
            $result['checks'],
        );
        $this->table(['Check', 'Status', 'Expected', 'Actual', 'Detail'], $rows);

        if ($result['passed']) {
            $this->info('');
            $this->info("All verification checks passed for company [{$company->id}].");
            return static::SUCCESS;
        }

        if ($rollbackOnFailure) {
            $this->error('Verification FAILED. Rolling back the tenant database...');
            $this->rollback($company, $dbName, new \RuntimeException('Verification failed'));
        } else {
            $this->error('Verification FAILED. Tenant database left in place for inspection.');
        }
        return static::FAILURE;
    }

    /**
     * @return array<int, array{string, string}>
     */
    private function planRows(Company $company, CompanyDataMigrator $migrator, string $sourceConnection): array
    {
        $rows = [];

        foreach ($migrator->allManifestTables() as $table) {
            $rows[] = ["Copy {$table}", "{$migrator->sourceCount($table, $company, $sourceConnection)} row(s)"];
        }

        $rows[] = ['Seed stub users', 'Referenced users + company members'];

        return $rows;
    }

    private function resolveCompany(): ?Company
    {
        $id = $this->option('company');

        if ($id === null) {
            $this->error('Missing required option --company=<id>.');
            return null;
        }

        $company = Company::find((int) $id);

        if ($company === null) {
            $this->error("Company [{$id}] not found.");
            return null;
        }

        return $company;
    }

    private function runPreflight(): bool
    {
        try {
            DB::connection('provisioning')->selectOne('SELECT 1');
        } catch (\Throwable $e) {
            $this->error('Provisioning connection unavailable: ' . $e->getMessage());
            return false;
        }

        $this->info('Checking central schema for Phase 1 provisioning fields...');

        if (!Schema::hasColumn('companies', 'provisioning_status')) {
            $this->info('Applying pending central migrations (provisioning fields, modules)...');
            $this->pruneStaleMigrationRecords();
            $exitCode = Artisan::call('migrate', ['--force' => true]);

            if ($exitCode !== 0) {
                $this->error('Central migration failed: ' . Artisan::output());
                return false;
            }
        } else {
            $this->info('Central schema already up to date.');
        }

        return true;
    }

    /**
     * Delete recorded migration rows whose file no longer exists on disk.
     * Renamed migrations leave stale rows behind; without this the migrator
     * would not re-run them (they are tracked by file name), so they are dead
     * weight. Tables themselves are validated separately by schema parity.
     *
     * NOTE: this does NOT cover the reverse case — a file renamed so a NEW name
     * is pending while the old name already created the table. Those must be
     * marked applied (or the file made idempotent) explicitly.
     */
    private function pruneStaleMigrationRecords(): void
    {
        $onDisk = array_merge(
            glob(database_path('migrations/*.php')) ?: [],
            glob(database_path('migrations/tenant/*.php')) ?: [],
        );
        $fileNames = array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $onDisk,
        );

        $table = DB::table('migrations');
        $stale = $table->pluck('migration')
            ->reject(static fn (string $name): bool => in_array($name, $fileNames, true))
            ->values()
            ->all();

        if ($stale !== []) {
            $this->warn('Pruning ' . count($stale) . ' stale migration record(s): ' . implode(', ', $stale));
            $table->whereIn('migration', $stale)->delete();
        }
    }

    private function rollback(Company $company, ?string $dbName, \Throwable $cause): void
    {
        $central = config('database.default');
        DB::setDefaultConnection($central);

        if ($dbName !== null) {
            try {
                DB::connection('provisioning')->statement("DROP DATABASE IF EXISTS `{$dbName}`");
                $this->warn("Dropped tenant database [{$dbName}].");
            } catch (\Throwable $dropError) {
                $this->error('Failed to drop tenant database: ' . $dropError->getMessage());
            }
        }

        $company->forceFill([
            'provisioning_status' => Company::STATUS_FAILED,
            'db_name' => null,
            'provisioned_at' => null,
            'last_provisioning_error' => Str::limit($cause->getMessage(), 2000),
        ])->save();

        $this->error('Rollback complete. Company status set to failed. Shared database was left untouched.');
    }
}
