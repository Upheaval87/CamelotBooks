<?php

namespace App\Services\BI;

use App\Services\BI\Concerns\MartConnection;
use Illuminate\Support\Facades\DB;

class PayrollFactBuilder
{
    use MartConnection;

    public function build(?int $companyId = null): int
    {
        $now = now();

        $inserts = [];

        $this->martTable('payroll_run_items AS pri')
            ->join('payroll_runs AS pr', 'pr.id', '=', 'pri.payroll_run_id')
            ->join('employees AS e', 'e.id', '=', 'pri.employee_id')
            ->where('pr.status', 'posted')
            ->when($companyId, fn ($q) => $q->where('pr.company_id', $companyId))
            ->select(
                'pr.company_id',
                'pr.pay_date AS date',
                'e.branch_id',
                'e.cost_center_id',
                'pri.employee_id',
                'pr.id AS payroll_run_id',
                'pr.run_number',
                'pr.period_label',
                'pri.basic_pay',
                'pri.total_allowances',
                'pri.gross_pay',
                'pri.paye',
                'pri.pension_ee',
                'pri.pension_er',
                'pri.employer_pension_expense',
                'pri.total_deductions',
                'pri.net_pay'
            )
            ->orderBy('pri.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'                => $row->company_id,
                        'date_key'                   => (int) \Carbon\Carbon::parse($row->date)->format('Ymd'),
                        'branch_key'                 => $row->branch_id,
                        'cost_center_key'            => $row->cost_center_id,
                        'employee_key'               => $row->employee_id,
                        'payroll_run_id'             => $row->payroll_run_id,
                        'run_number'                 => $row->run_number,
                        'period_label'               => $row->period_label,
                        'basic_pay'                  => $row->basic_pay,
                        'total_allowances'           => $row->total_allowances,
                        'gross_pay'                  => $row->gross_pay,
                        'paye'                       => $row->paye,
                        'pension_ee'                 => $row->pension_ee,
                        'pension_er'                 => $row->pension_er,
                        'employer_pension_expense'   => $row->employer_pension_expense,
                        'total_deductions'           => $row->total_deductions,
                        'net_pay'                    => $row->net_pay,
                        'refreshed_at'               => $now,
                    ];
                }

                $this->martTable('fact_payroll')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            $this->martTable('fact_payroll')->insert($inserts);
        }

        return $this->martTable('fact_payroll')->count();
    }
}
