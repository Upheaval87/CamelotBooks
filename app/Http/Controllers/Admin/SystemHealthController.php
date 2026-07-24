<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemHealthController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok', 'message' => 'Database connection successful'];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }

        $diskFree = @disk_free_space('/');
        $diskTotal = @disk_total_space('/');
        if ($diskFree !== false && $diskTotal !== false) {
            $percentUsed = round((1 - $diskFree / $diskTotal) * 100, 1);
            $checks['disk'] = [
                'status' => $percentUsed > 90 ? 'warning' : 'ok',
                'message' => "{$percentUsed}% used — " . $this->formatBytes($diskFree) . " free of " . $this->formatBytes($diskTotal),
            ];
        } else {
            $checks['disk'] = ['status' => 'unknown', 'message' => 'Unable to determine disk space'];
        }

        $scheduledJobs = [
            'recurring_invoices' => ['label' => 'Recurring Invoices', 'expected' => 'daily'],
            'recurring_bills' => ['label' => 'Recurring Bills', 'expected' => 'daily'],
            'depreciation_run' => ['label' => 'Depreciation Run', 'expected' => 'monthly'],
            'currency_revaluation' => ['label' => 'Currency Revaluation', 'expected' => 'monthly'],
            'backup' => ['label' => 'Backup', 'expected' => 'daily'],
        ];

        foreach ($scheduledJobs as $key => $job) {
            $lastRun = SystemSetting::getValue('health', "last_run.{$key}", $companyId);
            $checks["job_{$key}"] = [
                'status' => $lastRun ? 'ok' : 'warning',
                'message' => $lastRun
                    ? "Last run: " . now()->parse($lastRun)->diffForHumans()
                    : 'No run recorded — ensure Windows Task Scheduler is configured',
                'label' => $job['label'],
                'expected' => $job['expected'],
                'last_run' => $lastRun,
            ];
        }

        $recentErrors = $this->getRecentErrors();

        return view('admin.system-health.index', compact('checks', 'recentErrors'));
    }

    private function getRecentErrors(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return [];
        }

        $content = file_get_contents($logPath);
        preg_match_all('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] production\.ERROR: (.+?)(?=\\n\[|$)/s', $content, $matches);

        $errors = [];
        if (!empty($matches[0])) {
            $recent = array_slice($matches[0], -20);
            foreach ($recent as $match) {
                $errors[] = [
                    'time' => $match[1] ?? '',
                    'message' => substr($match[2] ?? '', 0, 200),
                ];
            }
        }

        return array_reverse($errors);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 1) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        return round($bytes / 1024, 1) . ' KB';
    }
}
