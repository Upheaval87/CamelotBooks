<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Apply pending tenant migrations to provisioned companies. Tenant DBs are
 * created with the tenant migration set at provisioning time; this command
 * catches migrations added afterwards (e.g. branch_requests/billing_quotations/
 * payments) without touching the central database.
 *
 *   php artisan tenant:migrate --company=1
 */
class MigrateTenantDatabases extends Command
{
    protected $signature = 'tenant:migrate {--company= : Restrict to a single company id}';

    protected $description = 'Run pending tenant migrations for provisioned companies';

    public function handle(TenantConnectionResolver $resolver): int
    {
        $companies = Company::query()
            ->where('is_active', true)
            ->where('provisioning_status', Company::STATUS_ACTIVE)
            ->whereNotNull('db_name')
            ->when($this->option('company'), fn ($q, $id) => $q->where('id', $id))
            ->orderBy('id')
            ->get();

        if ($companies->isEmpty()) {
            $this->error('No active provisioned companies found.');
            return static::FAILURE;
        }

        // migrate --database switches the app default connection; keep the
        // central connection so later loop iterations (and the final summary)
        // still read the central companies table.
        $central = DB::getDefaultConnection();
        $path = database_path('migrations/tenant');
        $failed = 0;

        foreach ($companies as $company) {
            $resolver->resolve($company);

            try {
                $exitCode = Artisan::call('migrate', [
                    '--database' => TenantConnectionResolver::CONNECTION_NAME,
                    '--path' => $path,
                    '--realpath' => true,
                    '--force' => true,
                ]);

                if ($exitCode === 0) {
                    $this->info("  #{$company->id} {$company->name}: OK");
                } else {
                    $this->error("  #{$company->id} {$company->name}: " . Artisan::output());
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->error("  #{$company->id} {$company->name}: {$e->getMessage()}");
                $failed++;
            } finally {
                DB::setDefaultConnection($central);
                $resolver->clear();
            }
        }

        if ($failed > 0) {
            $this->error("{$failed} company migration(s) failed.");
            return static::FAILURE;
        }

        $this->info('Tenant migrations complete.');
        return static::SUCCESS;
    }
}
