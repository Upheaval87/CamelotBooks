<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Admin\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupDatabases extends Command
{
    protected $signature = 'backup:databases {--company= : Restrict the backup to a single company id}';

    protected $description = 'Dump every active provisioned tenant database (per-tenant BackupLog rows)';

    public function handle(DatabaseBackupService $backups): int
    {
        $companies = Company::query()
            ->where('is_active', true)
            ->where('provisioning_status', Company::STATUS_ACTIVE)
            ->when($this->option('company'), fn ($q, $id) => $q->where('id', $id))
            ->orderBy('id')
            ->get();

        if ($companies->isEmpty()) {
            $this->error('No active provisioned companies to back up.');
            return static::FAILURE;
        }

        $failed = 0;

        foreach ($companies as $company) {
            $log = $backups->backup($company, null, 'scheduler');

            if ($log->status === 'success') {
                $this->info("  #{$company->id} {$company->name}: {$log->filename} ({$log->file_size_human})");
            } else {
                $this->error("  #{$company->id} {$company->name}: FAILED — {$log->error_message}");
                $failed++;
            }
        }

        if ($failed > 0) {
            $this->error("Completed with {$failed} failed backup(s).");
            return static::FAILURE;
        }

        $this->info('All tenant databases backed up.');
        return static::SUCCESS;
    }
}
