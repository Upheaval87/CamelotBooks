<?php

namespace App\Services\Admin;

use App\Models\BackupLog;
use App\Models\Company;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;

/**
 * Per-tenant database backup service.
 *
 * Generalizes the single inline mysqldump that used to live in
 * BackupController::trigger() into one dump primitive parameterized by a
 * connection config. The HTTP controller (active company, already bound) and
 * the `backup:databases` scheduler command (loop over active provisioned
 * tenants) both funnel through it, so every dump shares the same filename,
 * command and BackupLog semantics.
 *
 * BackupLog rows are written on the currently-bound connection (the tenant DB
 * for provisioned companies), matching the pre-existing web behavior.
 *
 * Restore is intentionally NOT an automated feature: it stays a documented
 * manual runbook step (create empty DB -> mysql < dump). The backup/restore
 * test exercises the full cycle against real MySQL scratch databases.
 */
class DatabaseBackupService
{
    public function __construct(private readonly TenantConnectionResolver $resolver)
    {
    }

    /**
     * The connection config a company's dump should read from: the company's
     * own tenant database when provisioned, otherwise the base MySQL config
     * (legacy mode where the company's data still lives in the shared DB).
     */
    public function backupConfigFor(Company $company): array
    {
        if ($company->isProvisioned() && $company->db_name) {
            return $this->resolver->connectionConfig($company);
        }

        return Config::get('database.connections.mysql', []);
    }

    /**
     * Run mysqldump against a connection config. Uses Symfony Process (array
     * command) so every argument is quoted correctly on Windows too — raw
     * shell commands with escapeshellarg()'d single quotes are passed through
     * cmd.exe verbatim and break the --host/--user values. The SQL is captured
     * on stdout and written to $fullPath directly (no shell redirection).
     *
     * @return array{0: int, 1: string[]} exit code + stderr lines
     */
    public function dumpToFile(array $config, string $fullPath): array
    {
        $process = new Process(
            [
                $this->mysqldumpBinary(),
                '--host=' . ($config['host'] ?? '127.0.0.1'),
                '--port=' . ($config['port'] ?? 3306),
                '--user=' . ($config['username'] ?? 'root'),
                '--password=' . ($config['password'] ?? ''),
                '--routines',
                '--triggers',
                $config['database'] ?? '',
            ],
            null,
            null,
            null,
            600,
        );

        $exitCode = $process->run();

        if ($exitCode === 0) {
            file_put_contents($fullPath, $process->getOutput());
        }

        return [$exitCode, $this->errorLines($process->getErrorOutput())];
    }

    /**
     * Restore a dump into an existing empty database (manual runbook / test use).
     * Streams the SQL file into the mysql client via Process input.
     *
     * @return array{0: int, 1: string[]} exit code + stderr lines
     */
    public function restoreFromFile(array $config, string $fullPath): array
    {
        $process = new Process(
            [
                $this->mysqlBinary(),
                '--host=' . ($config['host'] ?? '127.0.0.1'),
                '--port=' . ($config['port'] ?? 3306),
                '--user=' . ($config['username'] ?? 'root'),
                '--password=' . ($config['password'] ?? ''),
                $config['database'] ?? '',
            ],
            null,
            null,
            file_exists($fullPath) ? file_get_contents($fullPath) : null,
            600,
        );

        $exitCode = $process->run();

        return [$exitCode, $this->errorLines($process->getErrorOutput())];
    }

    /**
     * @return string[]
     */
    protected function errorLines(string $stderr): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", trim($stderr))), fn ($l) => $l !== ''));
    }

    /**
     * Full per-company backup: dump + BackupLog + status. Manages the tenant
     * binding itself (saves the prior binding, restores it in a finally), so it
     * is safe to call both from the web controller (already bound) and from the
     * scheduler command (not bound).
     */
    public function backup(Company $company, ?int $userId = null, string $triggeredBy = 'manual'): BackupLog
    {
        $resolver = $this->resolver;
        $priorBound = $resolver->boundCompanyId();
        $isProvisioned = $company->isProvisioned() && $company->db_name;

        if ($isProvisioned && $priorBound !== $company->id) {
            try {
                $resolver->resolve($company);
            } catch (\Throwable $e) {
                return $this->recordFailed($company, $userId, $triggeredBy, $e->getMessage());
            }
        }

        try {
            return $this->runDump($company, $userId, $triggeredBy);
        } finally {
            if ($isProvisioned && $priorBound !== $company->id) {
                if ($priorBound === null) {
                    $resolver->clear();
                } else {
                    try {
                        $resolver->resolveForCompanyId($priorBound);
                    } catch (\Throwable) {
                        $resolver->clear();
                    }
                }
            }
        }
    }

    public function mysqldumpBinary(): string
    {
        return Config::get('database.backup.binary', 'mysqldump');
    }

    public function mysqlBinary(): string
    {
        return Config::get('database.backup.restore_binary', 'mysql');
    }

    protected function runDump(Company $company, ?int $userId, string $triggeredBy): BackupLog
    {
        $filename = 'backup_' . now()->format('Y-m-d_His') . '_company' . $company->id . '.sql';

        $log = BackupLog::create([
            'company_id' => $company->id,
            'user_id' => $userId,
            'filename' => $filename,
            'file_size_bytes' => 0,
            'status' => 'running',
            'triggered_by' => $triggeredBy,
        ]);

        $backupPath = storage_path('app/backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $fullPath = $backupPath . DIRECTORY_SEPARATOR . $filename;

        try {
            [$exitCode, $output] = $this->dumpToFile($this->backupConfigFor($company), $fullPath);

            if ($exitCode === 0 && file_exists($fullPath) && filesize($fullPath) > 0) {
                $log->update([
                    'file_size_bytes' => filesize($fullPath),
                    'status' => 'success',
                ]);
            } else {
                $log->update([
                    'status' => 'failed',
                    'error_message' => implode("\n", $output) ?: "Exit code: {$exitCode}",
                ]);
            }
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    protected function recordFailed(Company $company, ?int $userId, string $triggeredBy, string $error): BackupLog
    {
        return BackupLog::create([
            'company_id' => $company->id,
            'user_id' => $userId,
            'filename' => 'backup_' . now()->format('Y-m-d_His') . '_company' . $company->id . '.sql',
            'file_size_bytes' => 0,
            'status' => 'failed',
            'triggered_by' => $triggeredBy,
            'error_message' => $error,
        ]);
    }
}
