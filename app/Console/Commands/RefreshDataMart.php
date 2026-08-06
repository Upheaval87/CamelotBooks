<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\BI\Concerns\MartConnection;
use App\Services\BI\DimDateFiscalMapper;
use App\Services\BI\DimSyncService;
use App\Services\BI\GeneralLedgerFactBuilder;
use App\Services\BI\InventoryMovementFactBuilder;
use App\Services\BI\PayrollFactBuilder;
use App\Services\BI\PurchasesFactBuilder;
use App\Services\BI\SalesFactBuilder;
use App\Services\Tenancy\TenantConnectionResolver;
use Database\Seeders\DimDateSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RefreshDataMart extends Command
{
    use MartConnection;

    protected $signature = 'bi:refresh-data-mart {--company= : Restrict refresh to a single company}';

    protected $description = 'Truncate and rebuild all BI fact/dimension tables per tenant';

    public function handle(): int
    {
        $companyOption = $this->option('company');

        $companies = Company::query()
            ->where('is_active', true)
            ->where('provisioning_status', Company::STATUS_ACTIVE)
            ->when($companyOption, fn ($q) => $q->where('id', $companyOption))
            ->orderBy('id')
            ->get();

        if ($companies->isEmpty()) {
            $this->error('No active provisioned companies to refresh.');
            return static::FAILURE;
        }

        $failed = 0;

        foreach ($companies as $company) {
            $this->info("Refreshing company #{$company->id} — {$company->name}");

            try {
                app(TenantConnectionResolver::class)->resolve($company);
            } catch (\Throwable $e) {
                $this->error("  Skipping company #{$company->id}: {$e->getMessage()}");
                $failed++;
                continue;
            }

            try {
                $this->refreshCompany($company);
            } catch (\Throwable $e) {
                $this->error("  FAILED for {$company->name}: {$e->getMessage()}");
                $failed++;
            } finally {
                app(TenantConnectionResolver::class)->clear();
            }
        }

        if ($failed > 0) {
            $this->error("Completed with {$failed} failed company(ies).");
            return static::FAILURE;
        }

        $this->info('All companies refreshed.');
        return static::SUCCESS;
    }

    protected function refreshCompany(Company $company): void
    {
        $startedAt = now();

        $logId = $this->martTable('bi_refresh_log')->insertGetId([
            'company_id'   => $company->id,
            'started_at'   => $startedAt,
            'status'       => 'running',
            'triggered_by' => 'artisan',
            'created_at'   => $startedAt,
            'updated_at'   => $startedAt,
        ]);

        $this->info('  BI Data Mart Refresh — started at ' . $startedAt->format('Y-m-d H:i:s'));

        try {
            // 1. Seed the dim_date calendar (truncate + reinsert)
            $this->info('  Seeding dim_date calendar...');
            $this->martTable('dim_date')->truncate();
            (new DimDateSeeder())->run(TenantConnectionResolver::connectionName());

            // 2. Sync dimensions
            $this->info('  Syncing dimensions...');
            $dimSync = new DimSyncService();
            $dimSync->syncAll($company);
            $counts = $dimSync->getSyncCounts();
            foreach ($counts as $table => $count) {
                $this->line("    {$table}: {$count} rows");
            }

            // 3. Map fiscal years onto dim_date
            $this->info('  Mapping fiscal years...');
            $fiscalMapper = new DimDateFiscalMapper();
            $fyCount = $fiscalMapper->mapFiscalYears();
            $this->line("    Mapped fiscal years to {$fyCount} fiscal year(s)");

            // 4. Rebuild fact tables (no outer transaction — chunk() manages its own)
            $this->info('  Rebuilding fact tables...');

            $this->martTable('fact_general_ledger')->truncate();
            $glCount = (new GeneralLedgerFactBuilder())->build($company->id);
            $this->line("    fact_general_ledger: {$glCount} rows");

            $this->martTable('fact_sales')->truncate();
            $salesCount = (new SalesFactBuilder())->build($company->id);
            $this->line("    fact_sales: {$salesCount} rows");

            $this->martTable('fact_purchases')->truncate();
            $purchasesCount = (new PurchasesFactBuilder())->build($company->id);
            $this->line("    fact_purchases: {$purchasesCount} rows");

            $this->martTable('fact_payroll')->truncate();
            $payrollCount = (new PayrollFactBuilder())->build($company->id);
            $this->line("    fact_payroll: {$payrollCount} rows");

            $this->martTable('fact_inventory_movement')->truncate();
            $inventoryCount = (new InventoryMovementFactBuilder())->build($company->id);
            $this->line("    fact_inventory_movement: {$inventoryCount} rows");

            // 5. Update refresh log
            $completedAt = now();
            $this->martTable('bi_refresh_log')
                ->where('id', $logId)
                ->update([
                    'status'         => 'completed',
                    'completed_at'   => $completedAt,
                    'rows_refreshed' => json_encode([
                        'dim_date'               => $this->martTable('dim_date')->count(),
                        ...$counts,
                        'fact_general_ledger'    => $glCount,
                        'fact_sales'             => $salesCount,
                        'fact_purchases'         => $purchasesCount,
                        'fact_payroll'           => $payrollCount,
                        'fact_inventory_movement' => $inventoryCount,
                    ]),
                    'updated_at' => $completedAt,
                ]);

            $duration = $startedAt->diffForHumans(now(), true);
            $this->newLine();
            $this->info("  Refresh completed in {$duration}.");
        } catch (\Throwable $e) {
            $this->martTable('bi_refresh_log')
                ->where('id', $logId)
                ->update([
                    'status'        => 'failed',
                    'completed_at'  => now(),
                    'error_message' => Str::limit($e->getMessage(), 2000),
                    'updated_at'    => now(),
                ]);

            throw $e;
        }
    }
}
