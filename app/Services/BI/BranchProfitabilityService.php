<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\DB;

class BranchProfitabilityService
{
    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $incomeByBranch = $this->getAmountByType($companyId, $dateFrom, $dateTo, $branchId, 'income');
        $cogsByBranch = $this->getCogsByBranch($companyId, $dateFrom, $dateTo, $branchId);
        $opexByBranch = $this->getOpexByBranch($companyId, $dateFrom, $dateTo, $branchId);
        $payrollByBranch = $this->getPayrollByBranch($companyId, $dateFrom, $dateTo, $branchId);
        $depreciationByBranch = $this->getDepreciationByBranch($companyId, $dateFrom, $dateTo, $branchId);

        $branches = [];
        $allBranchIds = array_unique(array_merge(
            array_column($incomeByBranch->toArray(), 'branch_id'),
            array_column($cogsByBranch->toArray(), 'branch_id'),
            array_column($opexByBranch->toArray(), 'branch_id'),
            array_column($payrollByBranch->toArray(), 'branch_id'),
            array_column($depreciationByBranch->toArray(), 'branch_id'),
        ));

        $lookup = function ($collection, $branchId) {
            $match = $collection->first(fn ($r) => $r->branch_id === $branchId);
            return $match ? (float) $match->amount : 0;
        };

        foreach ($allBranchIds as $bid) {
            $revenue = $lookup($incomeByBranch, $bid);
            $cogs = $lookup($cogsByBranch, $bid);
            $opex = $lookup($opexByBranch, $bid);
            $payroll = $lookup($payrollByBranch, $bid);
            $depreciation = $lookup($depreciationByBranch, $bid);

            $grossProfit = $revenue - $cogs;
            $totalExpenses = $opex + $payroll + $depreciation;
            $netIncome = $grossProfit - $totalExpenses;

            $branchName = $this->getBranchName($bid);

            $branches[] = [
                'branch_id'     => $bid,
                'branch_name'   => $branchName,
                'revenue'       => $revenue,
                'cogs'          => $cogs,
                'gross_profit'  => $grossProfit,
                'gross_margin'  => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0,
                'opex'          => $opex,
                'payroll'       => $payroll,
                'depreciation'  => $depreciation,
                'total_expenses' => $totalExpenses,
                'net_income'    => $netIncome,
                'net_margin'    => $revenue > 0 ? ($netIncome / $revenue) * 100 : 0,
            ];
        }

        usort($branches, fn ($a, $b) => $b['net_income'] <=> $a['net_income']);

        return [
            'branches'   => $branches,
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
        ];
    }

    protected function getAmountByType(int $companyId, string $dateFrom, string $dateTo, ?int $branchId, string $accountType): \Illuminate\Support\Collection
    {
        return DB::table('fact_general_ledger AS fgl')
            ->leftJoin('dim_branch AS db', 'db.branch_key', '=', 'fgl.branch_key')
            ->join('dim_account AS da', 'da.account_key', '=', 'fgl.account_key')
            ->where('fgl.company_key', $companyId)
            ->where('fgl.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fgl.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->where('da.account_type', $accountType)
            ->when($branchId, fn ($q) => $q->where('fgl.branch_key', $branchId))
            ->select(
                'fgl.branch_key AS branch_id',
                DB::raw("SUM(fgl.credit - fgl.debit) AS amount")
            )
            ->groupBy('fgl.branch_key')
            ->get();
    }

    protected function getCogsByBranch(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): \Illuminate\Support\Collection
    {
        return DB::table('fact_general_ledger AS fgl')
            ->leftJoin('dim_branch AS db', 'db.branch_key', '=', 'fgl.branch_key')
            ->join('dim_account AS da', 'da.account_key', '=', 'fgl.account_key')
            ->where('fgl.company_key', $companyId)
            ->where('fgl.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fgl.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->where('da.account_code', '5000')
            ->when($branchId, fn ($q) => $q->where('fgl.branch_key', $branchId))
            ->select(
                'fgl.branch_key AS branch_id',
                DB::raw("SUM(fgl.debit - fgl.credit) AS amount")
            )
            ->groupBy('fgl.branch_key')
            ->get();
    }

    protected function getOpexByBranch(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): \Illuminate\Support\Collection
    {
        return DB::table('fact_general_ledger AS fgl')
            ->leftJoin('dim_branch AS db', 'db.branch_key', '=', 'fgl.branch_key')
            ->join('dim_account AS da', 'da.account_key', '=', 'fgl.account_key')
            ->where('fgl.company_key', $companyId)
            ->where('fgl.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fgl.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->where('da.account_type', 'expense')
            ->whereNotIn('da.account_code', ['5000', '6000', '6010', '6400', '6500'])
            ->when($branchId, fn ($q) => $q->where('fgl.branch_key', $branchId))
            ->select(
                'fgl.branch_key AS branch_id',
                DB::raw("SUM(fgl.debit - fgl.credit) AS amount")
            )
            ->groupBy('fgl.branch_key')
            ->get();
    }

    protected function getPayrollByBranch(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): \Illuminate\Support\Collection
    {
        return DB::table('fact_payroll AS fp')
            ->leftJoin('dim_branch AS db', 'db.branch_key', '=', 'fp.branch_key')
            ->where('fp.company_key', $companyId)
            ->where('fp.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fp.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->when($branchId, fn ($q) => $q->where('fp.branch_key', $branchId))
            ->select(
                'fp.branch_key AS branch_id',
                DB::raw("SUM(fp.gross_pay + fp.employer_pension_expense) AS amount")
            )
            ->groupBy('fp.branch_key')
            ->get();
    }

    protected function getDepreciationByBranch(int $companyId, string $dateFrom, string $dateTo, ?int $branchId): \Illuminate\Support\Collection
    {
        return DB::table('fact_general_ledger AS fgl')
            ->leftJoin('dim_branch AS db', 'db.branch_key', '=', 'fgl.branch_key')
            ->join('dim_account AS da', 'da.account_key', '=', 'fgl.account_key')
            ->where('fgl.company_key', $companyId)
            ->where('fgl.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fgl.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->whereIn('da.account_code', ['6400', '6500'])
            ->when($branchId, fn ($q) => $q->where('fgl.branch_key', $branchId))
            ->select(
                'fgl.branch_key AS branch_id',
                DB::raw("SUM(fgl.debit - fgl.credit) AS amount")
            )
            ->groupBy('fgl.branch_key')
            ->get();
    }

    protected function getBranchName(?int $branchId): string
    {
        if (!$branchId) {
            return 'Unallocated';
        }

        $branch = DB::table('dim_branch')->where('branch_key', $branchId)->first();
        return $branch->branch_name ?? "Branch #{$branchId}";
    }
}
