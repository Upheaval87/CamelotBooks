<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\POS\PosReturnableService;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Console\Command;

class SweepExpiredBottleCredits extends Command
{
    protected $signature = 'returnables:sweep-expired {--company= : Restrict to a single company id}';

    protected $description = 'Sweep expired bottle credits: forfeit deposits to revenue for all active provisioned companies';

    public function handle(PosReturnableService $service, TenantConnectionResolver $resolver): int
    {
        $companies = Company::query()
            ->where('is_active', true)
            ->where('provisioning_status', Company::STATUS_ACTIVE)
            ->whereNotNull('db_name')
            ->when($this->option('company'), fn ($q, $id) => $q->where('id', $id))
            ->orderBy('id')
            ->get();

        if ($companies->isEmpty()) {
            $this->error('No active provisioned companies to scan.');
            return static::FAILURE;
        }

        $totalExpired = 0;
        $failed = 0;

        foreach ($companies as $company) {
            $resolver->resolve($company);

            try {
                $expired = $service->sweepExpired($company->id);
                $count = $expired->count();
                $totalExpired += $count;

                if ($count > 0) {
                    $this->info("  #{$company->id} {$company->name}: {$count} returnable(s) expired, deposits forfeited.");
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  #{$company->id} {$company->name}: FAILED — {$e->getMessage()}");
            } finally {
                $resolver->clear();
            }
        }

        $this->info("Swept {$totalExpired} expired returnable(s) total. " . ($failed > 0 ? "{$failed} company(ies) failed." : 'All OK.'));
        return $failed > 0 ? static::FAILURE : static::SUCCESS;
    }
}
