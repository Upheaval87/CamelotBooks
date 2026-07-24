<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Accounting\FixedAssets\DepreciationEngine;
use Illuminate\Console\Command;

class DepreciateAssets extends Command
{
    protected $signature = 'assets:depreciate {--company= : Company ID to process} {--period= : Period in YYYY-MM format}';

    protected $description = 'Run monthly depreciation for fixed assets';

    public function handle(DepreciationEngine $depreciationEngine): int
    {
        $period = $this->option('period');

        if (!$period) {
            $period = now()->subMonth()->format('Y-m');
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            $this->error("Invalid period format '{$period}'. Expected YYYY-MM.");
            return Command::FAILURE;
        }

        $companyId = $this->option('company');

        if ($companyId) {
            $companies = Company::where('id', $companyId)->where('is_active', true)->get();

            if ($companies->isEmpty()) {
                $this->error("Company ID {$companyId} not found or inactive.");
                return Command::FAILURE;
            }
        } else {
            $companies = Company::where('is_active', true)->get();
        }

        if ($companies->isEmpty()) {
            $this->error('No active companies found.');
            return Command::FAILURE;
        }

        $totalProcessed = 0;
        $totalSkipped = 0;
        $totalDepreciation = 0;

        foreach ($companies as $company) {
            $this->info("Processing depreciation for company: {$company->name} (ID: {$company->id})");

            try {
                $run = $depreciationEngine->runDepreciation($company->id, $period, auth()->id());

                $totalProcessed += $run->assets_processed;
                $totalSkipped += $run->assets_skipped;
                $totalDepreciation += (float) $run->total_depreciation_amount;

                $this->info("  Run: {$run->run_number}");
                $this->info("  Assets processed: {$run->assets_processed}");
                $this->info("  Assets skipped: {$run->assets_skipped}");
                $this->info("  Total depreciation: " . number_format((float) $run->total_depreciation_amount, 2));
                $this->info("  Status: {$run->status}");

                if ($run->skip_reasons && count($run->skip_reasons) > 0) {
                    foreach ($run->skip_reasons as $skipReason) {
                        $this->line("    Skipped: {$skipReason['asset_code']} - {$skipReason['reason']}");
                    }
                }

                $this->newLine();
            } catch (\Exception $e) {
                $this->error("  Error processing company {$company->name}: {$e->getMessage()}");
            }
        }

        $this->info('═══════════════════════════════════════');
        $this->info("Period: {$period}");
        $this->info("Total assets processed: {$totalProcessed}");
        $this->info("Total assets skipped: {$totalSkipped}");
        $this->info("Total depreciation: " . number_format($totalDepreciation, 2));
        $this->info('═══════════════════════════════════════');

        return Command::SUCCESS;
    }
}
