<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Re-seed permissions and roles for provisioned companies.
 * Ensures every permission from config('permissions') exists in each tenant DB.
 *
 *   php artisan tenant:sync-permissions
 *   php artisan tenant:sync-permissions --company=1
 */
class SyncTenantPermissions extends Command
{
    protected $signature = 'tenant:sync-permissions {--company= : Restrict to a single company id}';

    protected $description = 'Sync permissions and roles from config into provisioned tenant databases';

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

        $central = DB::getDefaultConnection();
        $failed = 0;
        $totalCreated = 0;

        foreach ($companies as $company) {
            $resolver->resolve($company);

            try {
                $before = DB::connection(TenantConnectionResolver::CONNECTION_NAME)
                    ->table('permissions')
                    ->count();

                Artisan::call('db:seed', [
                    '--class' => \Database\Seeders\RolePermissionSeeder::class,
                    '--force' => true,
                ]);

                $after = DB::connection(TenantConnectionResolver::CONNECTION_NAME)
                    ->table('permissions')
                    ->count();

                $created = $after - $before;
                $totalCreated += $created;

                if ($created > 0) {
                    $this->info("  #{$company->id} {$company->name}: +{$created} permissions ({$before} → {$after})");
                } else {
                    $this->info("  #{$company->id} {$company->name}: up to date ({$after} permissions)");
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
            $this->error("{$failed} company sync(s) failed. {$totalCreated} total permissions created.");
            return static::FAILURE;
        }

        $this->info("Sync complete. {$totalCreated} total permissions created across " . $companies->count() . " companies.");
        return static::SUCCESS;
    }
}
