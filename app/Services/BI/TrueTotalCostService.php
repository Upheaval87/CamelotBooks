<?php

namespace App\Services\BI;

use App\Models\PayrollRunItem;
use App\Services\BI\Concerns\BiPeriodMetrics;

class TrueTotalCostService
{
    use BiPeriodMetrics;

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $rows = $this->glRows($companyId, $dateFrom, $dateTo, $branchId, null, false, true);

        $perBranch = [];
        foreach ($rows as $row) {
            if ($row->type !== 'expense') {
                continue;
            }
            $bid = (int) $row->branch_id;
            if (!isset($perBranch[$bid])) {
                $perBranch[$bid] = [
                    'branch_id' => $bid,
                    'branch_name' => $row->branch_name,
                    'opex' => 0.0,
                    'payroll' => 0.0,
                    'depreciation' => 0.0,
                    'gl_other' => 0.0,
                ];
            }
            $amount = (float) $row->net;
            if ($this->isPayrollAccount($row->sub_type, $row->name)) {
                $perBranch[$bid]['payroll'] += $amount;
            } elseif ($this->isDepreciationAccount($row->sub_type, $row->name)) {
                $perBranch[$bid]['depreciation'] += $amount;
            } elseif (in_array($row->sub_type, self::COGS_SUBTYPES, true)) {
                $perBranch[$bid]['gl_other'] += $amount;
            } else {
                $perBranch[$bid]['opex'] += $amount;
            }
        }

        // Payroll cost: employer-bearing payroll from posted payroll runs (gross + employer pension).
        $payrollRows = $this->payrollByBranch($companyId, $dateFrom, $dateTo, $branchId);
        foreach ($payrollRows as $row) {
            $bid = (int) $row->branch_id;
            if (!isset($perBranch[$bid])) {
                $perBranch[$bid] = [
                    'branch_id' => $bid,
                    'branch_name' => $row->branch_name,
                    'opex' => 0.0,
                    'payroll' => 0.0,
                    'depreciation' => 0.0,
                    'gl_other' => 0.0,
                ];
            }
            $perBranch[$bid]['payroll'] += (float) $row->total_payroll;
        }

        $merged = [];
        foreach ($perBranch as $entry) {
            $total = $entry['opex'] + $entry['payroll'] + $entry['depreciation'] + $entry['gl_other'];
            $merged[] = [
                'branch_id' => $entry['branch_id'],
                'branch_name' => $entry['branch_name'],
                'opex' => $entry['opex'],
                'payroll' => $entry['payroll'],
                'depreciation' => $entry['depreciation'],
                'total_gl' => $entry['opex'] + $entry['depreciation'] + $entry['gl_other'],
                'total' => $total,
            ];
        }

        // Zero rows for active branches with no activity.
        $existing = array_column($merged, 'branch_id');
        foreach (\App\Models\Branch::where('company_id', $companyId)->where('is_active', true)->get(['id', 'name']) as $branch) {
            if (!in_array((int) $branch->id, $existing, true)) {
                $merged[] = [
                    'branch_id' => (int) $branch->id,
                    'branch_name' => $branch->name,
                    'opex' => 0.0,
                    'payroll' => 0.0,
                    'depreciation' => 0.0,
                    'total_gl' => 0.0,
                    'total' => 0.0,
                ];
            }
        }

        usort($merged, fn ($a, $b) => $b['total'] <=> $a['total']);

        $grandTotal = array_sum(array_column($merged, 'total'));

        return [
            'branches' => array_values($merged),
            'grand_total' => $grandTotal,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    protected function payrollByBranch(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): \Illuminate\Support\Collection
    {
        return PayrollRunItem::selectRaw('COALESCE(payroll_runs.branch_id, 0) as branch_id, COALESCE(branches.name, \'Unallocated\') as branch_name, SUM(payroll_run_items.gross_pay + COALESCE(payroll_run_items.employer_pension_expense, 0)) as total_payroll')
            ->join('payroll_runs', 'payroll_run_items.payroll_run_id', '=', 'payroll_runs.id')
            ->leftJoin('branches', 'payroll_runs.branch_id', '=', 'branches.id')
            ->where('payroll_runs.company_id', $companyId)
            ->whereNotIn('payroll_runs.status', ['draft', 'calculated', 'pending_approval'])
            ->where('payroll_runs.pay_date', '>=', $dateFrom)
            ->where('payroll_runs.pay_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('payroll_runs.branch_id', $branchId))
            ->groupBy('payroll_runs.branch_id', 'branches.name')
            ->get();
    }
}