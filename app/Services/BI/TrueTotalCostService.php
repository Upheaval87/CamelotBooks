<?php

namespace App\Services\BI;

use App\Services\BI\Concerns\MartConnection;
use Illuminate\Support\Facades\DB;

class TrueTotalCostService
{
    use MartConnection;

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $glCost = $this->getGlCostByBranch($companyId, $dateFrom, $dateTo, $branchId);
        $payrollCost = $this->getPayrollCostByBranch($companyId, $dateFrom, $dateTo, $branchId);

        $merged = [];

        foreach ($glCost as $row) {
            $key = $row->branch_id ?? 'unallocated';
            $merged[$key] = [
                'branch_id'   => $row->branch_id,
                'branch_name' => $row->branch_name ?? 'Unallocated',
                'opex'        => (float) $row->opex,
                'depreciation' => (float) $row->depreciation,
                'total_gl'    => (float) $row->opex + (float) $row->depreciation,
            ];
        }

        foreach ($payrollCost as $row) {
            $key = $row->branch_id ?? 'unallocated';
            if (!isset($merged[$key])) {
                $merged[$key] = [
                    'branch_id'    => $row->branch_id,
                    'branch_name'  => $row->branch_name ?? 'Unallocated',
                    'opex'         => 0,
                    'depreciation' => 0,
                    'total_gl'     => 0,
                ];
            }
            $merged[$key]['payroll'] = (float) $row->total_payroll;
            $merged[$key]['total'] = $merged[$key]['total_gl'] + (float) $row->total_payroll;
        }

        // Ensure payroll key exists on all entries
        foreach ($merged as &$entry) {
            if (!isset($entry['payroll'])) {
                $entry['payroll'] = 0;
            }
            if (!isset($entry['total'])) {
                $entry['total'] = $entry['total_gl'];
            }
        }
        unset($entry);

        usort($merged, fn ($a, $b) => $b['total'] <=> $a['total']);

        $grandTotal = array_sum(array_column($merged, 'total'));

        return [
            'branches'     => array_values($merged),
            'grand_total'  => $grandTotal,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
        ];
    }

    protected function getGlCostByBranch(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): \Illuminate\Support\Collection
    {
        return $this->martTable('fact_general_ledger AS fgl')
            ->leftJoin('dim_branch AS db', 'db.branch_key', '=', 'fgl.branch_key')
            ->join('dim_account AS da', 'da.account_key', '=', 'fgl.account_key')
            ->where('fgl.company_key', $companyId)
            ->where('fgl.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fgl.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->where('da.account_type', 'expense')
            ->whereNotIn('da.account_code', ['6000', '6010']) // Exclude payroll expense accounts
            ->when($branchId, fn ($q) => $q->where('fgl.branch_key', $branchId))
            ->select(
                'fgl.branch_key AS branch_id',
                'db.branch_name',
                DB::raw("SUM(CASE WHEN da.account_code LIKE '6%' AND da.account_code NOT LIKE '64%' AND da.account_code NOT LIKE '65%' THEN fgl.debit - fgl.credit ELSE 0 END) AS opex"),
                DB::raw("SUM(CASE WHEN da.account_code LIKE '64%' OR da.account_code LIKE '65%' THEN fgl.debit - fgl.credit ELSE 0 END) AS depreciation")
            )
            ->groupBy('fgl.branch_key', 'db.branch_name')
            ->get();
    }

    protected function getPayrollCostByBranch(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): \Illuminate\Support\Collection
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
}
