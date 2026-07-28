<?php
namespace App\Services\Reporting;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;

class PayrollRegisterService
{
    public function generate(int $companyId, ?int $payrollRunId = null): array
    {
        if ($payrollRunId) {
            $runs = PayrollRun::forCompany($companyId)->where('id', $payrollRunId)->with(['items.employee', 'items'])->get();
        } else {
            $runs = PayrollRun::forCompany($companyId)->whereIn('status', ['posted', 'partially_paid', 'fully_paid'])
                ->with(['items.employee'])->orderBy('pay_date', 'desc')->limit(50)->get();
        }

        $items = [];
        foreach ($runs as $run) {
            foreach ($run->items as $item) {
                $emp = $item->employee;
                $items[] = [
                    'run_number' => $run->run_number,
                    'pay_date' => $run->pay_date,
                    'period_label' => $run->period_label,
                    'employee_name' => $emp ? trim($emp->first_name . ' ' . $emp->last_name) : 'N/A',
                    'basic_pay' => (float) $item->basic_pay,
                    'total_allowances' => (float) $item->total_allowances,
                    'gross_pay' => (float) $item->gross_pay,
                    'paye' => (float) $item->paye,
                    'pension_ee' => (float) $item->pension_ee,
                    'total_deductions' => (float) $item->total_deductions,
                    'net_pay' => (float) $item->net_pay,
                    'pension_er' => (float) $item->pension_er,
                    'employer_cost' => (float) $item->gross_pay + (float) $item->pension_er,
                ];
            }
        }

        return ['items' => $items, 'run_id' => $payrollRunId];
    }
}
