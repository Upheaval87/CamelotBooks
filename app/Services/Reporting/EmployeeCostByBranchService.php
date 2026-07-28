<?php
namespace App\Services\Reporting;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;

class EmployeeCostByBranchService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = PayrollRun::forCompany($companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'fully_paid']);

        if ($dateFrom) $query->where('pay_date', '>=', $dateFrom);
        if ($dateTo) $query->where('pay_date', '<=', $dateTo);

        $runs = $query->with(['items.employee'])->get();
        $runIds = $runs->pluck('id');

        $items = PayrollRunItem::whereIn('payroll_run_id', $runIds)
            ->with('employee')
            ->get();

        $branchTotals = [];
        $ccTotals = [];

        foreach ($items as $item) {
            $emp = $item->employee;
            $branchId = $emp?->branch_id ?? 'unassigned';
            $ccId = $emp?->cost_center_id ?? 'unassigned';
            $cost = (float) $item->gross_pay + (float) $item->pension_er;

            $branchTotals[$branchId] = ($branchTotals[$branchId] ?? 0) + $cost;
            $ccTotals[$ccId] = ($ccTotals[$ccId] ?? 0) + $cost;
        }

        $branches = \App\Models\Branch::whereIn('id', array_filter(array_keys($branchTotals, fn($k) => $k !== 'unassigned')))
            ->get()->keyBy('id');
        $costCenters = \App\Models\CostCenter::whereIn('id', array_filter(array_keys($ccTotals, fn($k) => $k !== 'unassigned')))
            ->get()->keyBy('id');

        $byBranch = array_map(fn($total, $id) => [
            'branch_id' => $id,
            'branch_name' => $branches[$id]->name ?? 'Unassigned',
            'total_cost' => $total,
        ], $branchTotals, array_keys($byBranch = []));

        // Rebuild properly
        $byBranch = [];
        foreach ($branchTotals as $bid => $total) {
            $byBranch[] = [
                'branch_id' => $bid,
                'branch_name' => $bid === 'unassigned' ? 'Unassigned' : ($branches[$bid]->name ?? 'Unknown'),
                'total_cost' => $total,
            ];
        }
        usort($byBranch, fn($a, $b) => $b['total_cost'] <=> $a['total_cost']);

        $byCC = [];
        foreach ($ccTotals as $ccid => $total) {
            $byCC[] = [
                'cost_center_id' => $ccid,
                'cost_center_name' => $ccid === 'unassigned' ? 'Unassigned' : ($costCenters[$ccid]->name ?? 'Unknown'),
                'total_cost' => $total,
            ];
        }
        usort($byCC, fn($a, $b) => $b['total_cost'] <=> $a['total_cost']);

        return ['by_branch' => $byBranch, 'by_cost_center' => $byCC, 'date_from' => $dateFrom, 'date_to' => $dateTo];
    }
}
