<?php

namespace App\Services\BI;

use App\Services\BI\Concerns\MartConnection;
use Illuminate\Support\Facades\DB;

class EmployeeProductivityService
{
    use MartConnection;

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $payrollByBranch = $this->getPayrollByBranch($companyId, $dateFrom, $dateTo, $branchId);
        $revenueByBranch = $this->getRevenueByBranch($companyId, $dateFrom, $dateTo, $branchId);
        $headcountByBranch = $this->getHeadcountByBranch($companyId, $branchId);

        $merged = [];

        foreach ($payrollByBranch as $row) {
            $key = $row->branch_id ?? 'unallocated';
            $merged[$key] = [
                'branch_id'   => $row->branch_id,
                'branch_name' => $row->branch_name ?? 'Unallocated',
                'total_payroll' => (float) $row->total_payroll,
                'headcount'   => 0,
                'revenue'     => 0,
                'cost_per_employee' => 0,
                'revenue_per_employee' => 0,
                'ratio'       => 0,
            ];
        }

        foreach ($revenueByBranch as $row) {
            $key = $row->branch_id ?? 'unallocated';
            if (!isset($merged[$key])) {
                $merged[$key] = [
                    'branch_id'   => $row->branch_id,
                    'branch_name' => $row->branch_name ?? 'Unallocated',
                    'total_payroll' => 0,
                    'headcount'   => 0,
                    'revenue'     => 0,
                    'cost_per_employee' => 0,
                    'revenue_per_employee' => 0,
                    'ratio'       => 0,
                ];
            }
            $merged[$key]['revenue'] = (float) $row->revenue;
        }

        foreach ($headcountByBranch as $row) {
            $key = $row->branch_id ?? 'unallocated';
            if (isset($merged[$key])) {
                $merged[$key]['headcount'] = (int) $row->headcount;
            }
        }

        foreach ($merged as &$entry) {
            $hc = max(1, $entry['headcount']);
            $entry['cost_per_employee'] = $entry['total_payroll'] / $hc;
            $entry['revenue_per_employee'] = $entry['revenue'] / $hc;
            $entry['ratio'] = $entry['revenue'] > 0 ? ($entry['total_payroll'] / $entry['revenue']) * 100 : 0;
        }
        unset($entry);

        usort($merged, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return [
            'branches'     => array_values($merged),
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
        ];
    }

    protected function getPayrollByBranch(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): \Illuminate\Support\Collection
    {
        return $this->martTable('fact_payroll AS fp')
            ->leftJoin('dim_branch AS db', 'db.branch_key', '=', 'fp.branch_key')
            ->where('fp.company_key', $companyId)
            ->where('fp.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fp.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->when($branchId, fn ($q) => $q->where('fp.branch_key', $branchId))
            ->select(
                'fp.branch_key AS branch_id',
                'db.branch_name',
                DB::raw("SUM(fp.gross_pay + fp.employer_pension_expense) AS total_payroll")
            )
            ->groupBy('fp.branch_key', 'db.branch_name')
            ->get();
    }

    protected function getRevenueByBranch(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): \Illuminate\Support\Collection
    {
        return $this->martTable('fact_general_ledger AS fgl')
            ->leftJoin('dim_branch AS db', 'db.branch_key', '=', 'fgl.branch_key')
            ->join('dim_account AS da', 'da.account_key', '=', 'fgl.account_key')
            ->where('fgl.company_key', $companyId)
            ->where('fgl.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fgl.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->where('da.account_type', 'income')
            ->when($branchId, fn ($q) => $q->where('fgl.branch_key', $branchId))
            ->select(
                'fgl.branch_key AS branch_id',
                'db.branch_name',
                DB::raw("SUM(fgl.credit - fgl.debit) AS revenue")
            )
            ->groupBy('fgl.branch_key', 'db.branch_name')
            ->get();
    }

    protected function getHeadcountByBranch(int $companyId, ?int $branchId): \Illuminate\Support\Collection
    {
        return $this->martTable('dim_employee AS de')
            ->where('de.company_key', $companyId)
            ->where('de.is_active', true)
            ->when($branchId, fn ($q) => $q->where('de.branch_key', $branchId))
            ->select(
                'de.branch_key AS branch_id',
                DB::raw("COUNT(*) AS headcount")
            )
            ->groupBy('de.branch_key')
            ->get();
    }
}
