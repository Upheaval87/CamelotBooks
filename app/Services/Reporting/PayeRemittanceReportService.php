<?php

namespace App\Services\Reporting;

use App\Models\EmployeePayment;
use App\Models\PayrollRun;

class PayeRemittanceReportService
{
    public function generate(int $companyId): array
    {
        $runs = PayrollRun::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'fully_paid'])
            ->with(['payments' => fn ($q) => $q->where('payment_type', 'paye_remittance'), 'approver'])
            ->orderBy('period_start', 'desc')
            ->get();

        $results = $runs->map(fn ($run) => [
            'run_number' => $run->run_number,
            'period_label' => $run->period_label,
            'period_start' => $run->period_start,
            'period_end' => $run->period_end,
            'pay_date' => $run->pay_date,
            'total_paye' => (float) $run->total_paye,
            'status' => $run->status,
            'remitted' => $run->payments->isNotEmpty(),
            'remittance_amount' => (float) $run->payments->sum('amount'),
            'remittance_date' => $run->payments->first()?->payment_date,
        ])->toArray();

        $totalPaye = $runs->sum('total_paye');
        $totalRemitted = collect($results)->where('remitted', true)->sum('remittance_amount');
        $pendingCount = $runs->where('status', 'posted')->count();

        return [
            'runs' => $results,
            'total_paye' => $totalPaye,
            'total_remitted' => $totalRemitted,
            'pending_count' => $pendingCount,
        ];
    }
}
