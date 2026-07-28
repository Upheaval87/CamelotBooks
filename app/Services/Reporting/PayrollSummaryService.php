<?php
namespace App\Services\Reporting;
use App\Models\PayrollRun;

class PayrollSummaryService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = PayrollRun::forCompany($companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'fully_paid']);

        if ($dateFrom) $query->where('pay_date', '>=', $dateFrom);
        if ($dateTo) $query->where('pay_date', '<=', $dateTo);

        $runs = $query->orderBy('pay_date', 'desc')->get();

        $results = [];
        foreach ($runs as $run) {
            $results[] = [
                'run_number' => $run->run_number,
                'period_label' => $run->period_label,
                'pay_date' => $run->pay_date,
                'total_gross' => (float) $run->total_gross,
                'total_paye' => (float) $run->total_paye,
                'total_pension_ee' => (float) $run->total_pension_ee,
                'total_pension_er' => (float) $run->total_pension_er,
                'total_deductions' => (float) $run->total_deductions,
                'total_net_pay' => (float) $run->total_net_pay,
                'employer_cost' => (float) $run->total_gross + (float) $run->total_pension_er,
                'status' => $run->status,
            ];
        }

        return ['runs' => $results, 'date_from' => $dateFrom, 'date_to' => $dateTo];
    }
}
