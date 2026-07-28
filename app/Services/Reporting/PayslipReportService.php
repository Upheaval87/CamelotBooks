<?php

namespace App\Services\Reporting;

use App\Models\PayrollRun;
use App\Models\PayrollRunItem;

class PayslipReportService
{
    public function generate(int $companyId, ?int $payrollRunId = null): array
    {
        $query = PayrollRun::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'fully_paid'])
            ->orderBy('period_start', 'desc');

        if ($payrollRunId) {
            $query->where('id', $payrollRunId);
        }

        $runs = $query->get();

        $items = PayrollRunItem::whereHas('payrollRun', function ($q) use ($companyId, $payrollRunId) {
            $q->where('company_id', $companyId);
            if ($payrollRunId) {
                $q->where('id', $payrollRunId);
            }
        })->with(['employee', 'payrollRun'])
            ->get()
            ->map(fn ($item) => [
                'payroll_run_id' => $item->payroll_run_id,
                'run_number' => $item->payrollRun->run_number ?? '',
                'period_label' => $item->payrollRun->period_label ?? '',
                'pay_date' => $item->payrollRun->pay_date,
                'employee_id' => $item->employee_id,
                'employee_name' => $item->employee->name ?? 'N/A',
                'basic_pay' => (float) $item->basic_pay,
                'total_allowances' => (float) $item->total_allowances,
                'gross_pay' => (float) $item->gross_pay,
                'paye' => (float) $item->paye,
                'pension_ee' => (float) $item->pension_ee,
                'total_deductions' => (float) $item->total_deductions,
                'net_pay' => (float) $item->net_pay,
            ])->toArray();

        return [
            'items' => $items,
            'runs' => $runs->pluck('run_number', 'id')->toArray(),
            'payroll_run_id' => $payrollRunId,
        ];
    }
}
