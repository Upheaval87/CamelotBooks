<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $backups = BackupLog::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.backups.index', compact('backups'));
    }

    public function trigger(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $filename = 'backup_' . now()->format('Y-m-d_His') . '.sql';

        $log = BackupLog::create([
            'company_id' => $companyId,
            'user_id' => $request->user()->id,
            'filename' => $filename,
            'file_size_bytes' => 0,
            'status' => 'running',
            'triggered_by' => 'manual',
        ]);

        $backupPath = storage_path('app/backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $fullPath = $backupPath . DIRECTORY_SEPARATOR . $filename;

        try {
            $dbHost = config('database.connections.mysql.host', '127.0.0.1');
            $dbPort = config('database.connections.mysql.port', '3306');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            $cmd = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s --routines --triggers %s > %s 2>&1',
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($fullPath)
            );

            exec($cmd, $output, $exitCode);

            if ($exitCode === 0 && file_exists($fullPath)) {
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

        return redirect()->route('admin.backups.index')
            ->with($log->status === 'success' ? 'success' : 'error',
                $log->status === 'success'
                    ? "Backup completed successfully: {$filename} ({$log->file_size_human})"
                    : "Backup failed: {$log->error_message}"
            );
    }
}
