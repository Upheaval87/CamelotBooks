<?php

namespace App\Services\Reporting;

use App\Models\PayrollRun;

class PensionRemittanceReportService
{
    public function generate(int $companyId): array
    {
        $runs = PayrollRun::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'fully_paid'])
            ->with(['payments' => fn ($q) => $q->where('payment_type', 'pension_remittance'), 'approver'])
            ->orderBy('period_start', 'desc')
            ->get();

        $results = $runs->map(fn ($run) => [
            'run_number' => $run->run_number,
            'period_label' => $run->period_label,
            'period_start' => $run->period_start,
            'period_end' => $run->period_end,
            'pay_date' => $run->pay_date,
            'total_pension_ee' => (float) $run->total_pension_ee,
            'total_pension_er' => (float) $run->total_pension_er,
            'total_pension' => (float) $run->total_pension_ee + (float) $run->total_pension_er,
            'status' => $run->status,
            'remitted' => $run->payments->isNotEmpty(),
            'remittance_amount' => (float) $run->payments->sum('amount'),
            'remittance_date' => $run->payments->first()?->payment_date,
        ])->toArray();

        $totalEe = $runs->sum('total_pension_ee');
        $totalEr = $runs->sum('total_pension_er');
        $totalRemitted = collect($results)->where('remitted', true)->sum('remittance_amount');
        $pendingCount = $runs->where('status', 'posted')->count();

        return [
            'runs' => $results,
            'total_ee' => $totalEe,
            'total_er' => $totalEr,
            'total_pension' => $totalEe + $totalEr,
            'total_remitted' => $totalRemitted,
            'pending_count' => $pendingCount,
        ];
    }
}
