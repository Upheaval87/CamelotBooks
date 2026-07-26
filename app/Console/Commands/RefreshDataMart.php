<?php

namespace App\Console\Commands;

use App\Services\BI\DimDateFiscalMapper;
use App\Services\BI\DimSyncService;
use App\Services\BI\GeneralLedgerFactBuilder;
use App\Services\BI\InventoryMovementFactBuilder;
use App\Services\BI\PayrollFactBuilder;
use App\Services\BI\PurchasesFactBuilder;
use App\Services\BI\SalesFactBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshDataMart extends Command
{
    protected $signature = 'bi:refresh-data-mart {--company= : Restrict refresh to a single company}';

    protected $description = 'Truncate and rebuild all BI fact/dimension tables from source data';

    public function handle(): int
    {
        $companyId = $this->option('company');
        $startedAt = now();

        $logId = DB::table('bi_refresh_log')->insertGetId([
            'company_id'   => $companyId,
            'started_at'   => $startedAt,
            'status'       => 'running',
            'triggered_by' => 'artisan',
            'created_at'   => $startedAt,
            'updated_at'   => $startedAt,
        ]);

        $this->info('BI Data Mart Refresh — started at ' . $startedAt->format('Y-m-d H:i:s'));

        try {
            // 1. Sync dimensions
            $this->info('Syncing dimensions...');
            $dimSync = new DimSyncService();
            $dimSync->syncAll();
            $counts = $dimSync->getSyncCounts();
            foreach ($counts as $table => $count) {
                $this->line("  {$table}: {$count} rows");
            }

            // 2. Map fiscal years onto dim_date
            $this->info('Mapping fiscal years...');
            $fiscalMapper = new DimDateFiscalMapper();
            $fyCount = $fiscalMapper->mapFiscalYears();
            $this->line("  Mapped fiscal years to {$fyCount} fiscal year(s)");

            // 3. Rebuild fact tables (no outer transaction — chunk() manages its own)
            $this->info('Rebuilding fact tables...');

            // 3a. General Ledger
            DB::table('fact_general_ledger')->truncate();
            $glBuilder = new GeneralLedgerFactBuilder();
            $glCount = $glBuilder->build();
            $this->line("  fact_general_ledger: {$glCount} rows");

            // 3b. Sales
            DB::table('fact_sales')->truncate();
            $salesBuilder = new SalesFactBuilder();
            $salesCount = $salesBuilder->build();
            $this->line("  fact_sales: {$salesCount} rows");

            // 3c. Purchases
            DB::table('fact_purchases')->truncate();
            $purchasesBuilder = new PurchasesFactBuilder();
            $purchasesCount = $purchasesBuilder->build();
            $this->line("  fact_purchases: {$purchasesCount} rows");

            // 3d. Payroll
            DB::table('fact_payroll')->truncate();
            $payrollBuilder = new PayrollFactBuilder();
            $payrollCount = $payrollBuilder->build();
            $this->line("  fact_payroll: {$payrollCount} rows");

            // 3e. Inventory Movements
            DB::table('fact_inventory_movement')->truncate();
            $inventoryBuilder = new InventoryMovementFactBuilder();
            $inventoryCount = $inventoryBuilder->build();
            $this->line("  fact_inventory_movement: {$inventoryCount} rows");

            // 4. Update refresh log
            $completedAt = now();
            DB::table('bi_refresh_log')
                ->where('id', $logId)
                ->update([
                    'status'         => 'completed',
                    'completed_at'   => $completedAt,
                    'rows_refreshed' => json_encode([
                        'dim_date'               => DB::table('dim_date')->count(),
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
            $this->info("Refresh completed in {$duration}.");
            return static::SUCCESS;
        } catch (\Throwable $e) {
            DB::table('bi_refresh_log')
                ->where('id', $logId)
                ->update([
                    'status'        => 'failed',
                    'completed_at'  => now(),
                    'error_message' => $e->getMessage(),
                    'updated_at'    => now(),
                ]);

            $this->error("Refresh FAILED: {$e->getMessage()}");
            return static::FAILURE;
        }
    }
}
