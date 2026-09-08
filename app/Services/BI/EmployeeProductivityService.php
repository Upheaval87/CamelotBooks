<?php

namespace App\Services\BI;

use App\Models\Employee;
use App\Models\PayrollRunItem;
use App\Services\BI\Concerns\BiPeriodMetrics;
use Illuminate\Support\Facades\DB;

class EmployeeProductivityService
{
    use BiPeriodMetrics;

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        // Revenue per branch from the GL (income accounts only).
        $rows = $this->glRows($companyId, $dateFrom, $dateTo, $branchId, null, false, true);
        $revenueByBranch = [];
        foreach ($rows as $row) {
            if ($row->type !== 'income') {
                continue;
            }
            $bid = (int) $row->branch_id;
            $revenueByBranch[$bid] = ($revenueByBranch[$bid] ?? 0) + (float) $row->net;
        }

        // Payroll + headcount per branch.
        $payrollByBranch = $this->payrollByBranch($companyId, $dateFrom, $dateTo, $branchId);
        $headcountByBranch = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('COALESCE(branch_id, 0) as branch_id, COUNT(*) as headcount')
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $allBranches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name']);

        $merged = [];
        foreach ($allBranches as $branch) {
            $merged[(int) $branch->id] = [
                'branch_id' => (int) $branch->id,
                'branch_name' => $branch->name,
                'total_payroll' => (float) ($payrollByBranch[(int) $branch->id]['total_payroll'] ?? 0),
                'headcount' => (int) ($headcountByBranch[(int) $branch->id]->headcount ?? 0),
                'revenue' => (float) ($revenueByBranch[(int) $branch->id] ?? 0),
                'cost_per_employee' => 0,
                'revenue_per_employee' => 0,
                'ratio' => 0,
            ];
        }

        // Any branch seen in GL/payroll but missing from the active list.
        foreach ([...$payrollByBranch, ...array_map(fn ($v) => ['branch_id' => $v], array_keys($revenueByBranch))] as $entry) {
            $bid = (int) $entry['branch_id'];
            if (!isset($merged[$bid])) {
                $merged[$bid] = [
                    'branch_id' => $bid,
                    'branch_name' => $entry['branch_name'] ?? "Branch #{$bid}",
                    'total_payroll' => (float) ($payrollByBranch[$bid]['total_payroll'] ?? 0),
                    'headcount' => (int) ($headcountByBranch[$bid]->headcount ?? 0),
                    'revenue' => (float) ($revenueByBranch[$bid] ?? 0),
                    'cost_per_employee' => 0,
                    'revenue_per_employee' => 0,
                    'ratio' => 0,
                ];
            }
        }

        foreach ($merged as &$entry) {
            $hc = max(1, $entry['headcount']);
            $entry['cost_per_employee'] = round($entry['total_payroll'] / $hc, 2);
            $entry['revenue_per_employee'] = round($entry['revenue'] / $hc, 2);
            $entry['ratio'] = $entry['revenue'] > 0 ? round($entry['total_payroll'] / $entry['revenue'] * 100, 1) : 0;
        }
        unset($entry);

        usort($merged, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        // Per-employee detail: gross compensation + index (avg = 100) + utilisation.
        $employees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'position', 'department', 'branch_id']);

        $compRows = PayrollRunItem::select('employee_id', 'branch_id')
            ->addSelect(DB::raw('SUM(gross_pay + COALESCE(employer_pension_expense, 0)) as gross'))
            ->join('payroll_runs', 'payroll_run_items.payroll_run_id', '=', 'payroll_runs.id')
            ->where('payroll_runs.company_id', $companyId)
            ->whereNotIn('payroll_runs.status', ['draft', 'calculated', 'pending_approval'])
            ->where('payroll_runs.pay_date', '>=', $dateFrom)
            ->where('payroll_runs.pay_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('payroll_runs.branch_id', $branchId))
            ->groupBy('employee_id', 'branch_id')
            ->get()
            ->keyBy('employee_id');

        $gross = $compRows->pluck('gross')->filter(fn ($v) => $v > 0);
        $avgGross = $gross->count() > 0 ? $gross->avg() : 0;

        $byEmployee = [];
        foreach ($employees as $employee) {
            $g = (float) ($compRows->get($employee->id)->gross ?? 0);
            $byEmployee[] = [
                'employee_id' => (int) $employee->id,
                'employee_name' => $employee->full_name,
                'position' => $employee->position,
                'department' => $employee->department,
                'branch_id' => $employee->branch_id,
                'gross_compensation' => $g,
                'comp_index' => $avgGross > 0 ? round($g / $avgGross * 100, 1) : null,
                'utilised' => $g > 0,
            ];
        }
        usort($byEmployee, fn ($a, $b) => $b['gross_compensation'] <=> $a['gross_compensation']);

        $paidEmployees = count(array_filter($byEmployee, fn ($r) => $r['utilised']));
        $headcountTotal = $employees->count();

        return [
            'branches' => array_values($merged),
            'by_employee' => $byEmployee,
            'headcount' => $headcountTotal,
            'utilised_employees' => $paidEmployees,
            'utilisation_pct' => $headcountTotal > 0 ? round($paidEmployees / $headcountTotal * 100, 1) : 0,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    protected function payrollByBranch(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): array
    {
        $rows = PayrollRunItem::selectRaw('COALESCE(payroll_runs.branch_id, 0) as branch_id, COALESCE(branches.name, \'Unallocated\') as branch_name, SUM(payroll_run_items.gross_pay + COALESCE(payroll_run_items.employer_pension_expense, 0)) as total_payroll')
            ->join('payroll_runs', 'payroll_run_items.payroll_run_id', '=', 'payroll_runs.id')
            ->leftJoin('branches', 'payroll_runs.branch_id', '=', 'branches.id')
            ->where('payroll_runs.company_id', $companyId)
            ->whereNotIn('payroll_runs.status', ['draft', 'calculated', 'pending_approval'])
            ->where('payroll_runs.pay_date', '>=', $dateFrom)
            ->where('payroll_runs.pay_date', '<=', $dateTo)
            ->when($branchId, fn ($q) => $q->where('payroll_runs.branch_id', $branchId))
            ->groupBy('payroll_runs.branch_id', 'branches.name')
            ->get()
            ->keyBy('branch_id');

        return $rows->toArray();
    }
}