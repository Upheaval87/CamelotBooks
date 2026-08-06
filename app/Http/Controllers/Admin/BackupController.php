<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\Company;
use App\Models\SettingsBackup;
use App\Services\Admin\DatabaseBackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $backups = BackupLog::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $snapshots = SettingsBackup::where('company_id', $companyId)
            ->with('createdByUser')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.backups.index', compact('backups', 'snapshots'));
    }

    public function trigger(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $company = Company::query()->find($companyId);

        if (!$company) {
            abort(404);
        }

        $log = app(DatabaseBackupService::class)->backup($company, $request->user()->id, 'manual');

        return redirect()->route('admin.backups.index')
            ->with($log->status === 'success' ? 'success' : 'error',
                $log->status === 'success'
                    ? "Backup completed successfully: {$log->filename} ({$log->file_size_human})"
                    : "Backup failed: {$log->error_message}"
            );
    }

    public function createSnapshot(Request $request)
    {
        $companyId = session('current_company_id');
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $backup = SettingsBackup::capture($companyId, $request->user()->id, $validated['label'], $validated['notes'] ?? null);

        AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.backup_created', null, ['backup_id' => $backup->id, 'label' => $backup->label, 'record_count' => $backup->record_count], 'Backup created: ' . $backup->label);

        return redirect()->route('admin.backups.index')
            ->with('success', "Settings snapshot \"{$backup->label}\" created with {$backup->record_count} setting(s).");
    }

    public function restoreSnapshot(SettingsBackup $backup)
    {
        $companyId = session('current_company_id');
        abort_unless($backup->company_id === $companyId, 404);

        $user = request()->user();
        abort_unless($user->hasAnyRole(['system_admin', 'company_admin']), 403);

        $restored = $backup->restore();

        AuditLog::log($companyId, $user->id, Company::class, $companyId, 'settings.backup_restored', null, ['backup_id' => $backup->id, 'label' => $backup->label, 'records_restored' => $restored], 'Settings restored from backup: ' . $backup->label);

        return redirect()->route('admin.backups.index')
            ->with('success', "Settings restored from \"{$backup->label}\". {$restored} setting(s) updated.");
    }

    public function deleteSnapshot(SettingsBackup $backup)
    {
        $companyId = session('current_company_id');
        abort_unless($backup->company_id === $companyId, 404);

        $user = request()->user();
        abort_unless($user->hasAnyRole(['system_admin', 'company_admin']), 403);

        $label = $backup->label;
        $backup->delete();

        AuditLog::log($companyId, $user->id, Company::class, $companyId, 'settings.backup_deleted', null, ['label' => $label], 'Backup deleted: ' . $label);

        return redirect()->route('admin.backups.index')
            ->with('success', "Settings snapshot \"{$label}\" deleted.");
    }
}
