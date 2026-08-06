<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\BranchRequests\BranchRequestService;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Console\Command;

class ExpireBranchQuotations extends Command
{
    protected $signature = 'branch-quotations:expire {--company= : Restrict to a single company id}';

    protected $description = 'Mark pending branch-request quotations past their valid_until as expired';

    public function handle(BranchRequestService $service, TenantConnectionResolver $resolver): int
    {
        $companies = Company::query()
            ->where('is_active', true)
            ->when($this->option('company'), fn ($q, $id) => $q->where('id', $id))
            ->orderBy('id')
            ->get();

        if ($companies->isEmpty()) {
            $this->error('No active companies to scan.');
            return static::FAILURE;
        }

        $total = 0;

        foreach ($companies as $company) {
            $provisioned = $company->provisioning_status === Company::STATUS_ACTIVE && $company->db_name;

            if ($provisioned) {
                $resolver->resolve($company);
            }

            try {
                $expired = $service->expireOverdue($company);
                $total += $expired;

                if ($expired > 0) {
                    $this->info("  #{$company->id} {$company->name}: {$expired} quotation(s) expired.");
                }
            } finally {
                $resolver->clear();
            }
        }

        $this->info("Expired {$total} quotation(s) total.");
        return static::SUCCESS;
    }
}
