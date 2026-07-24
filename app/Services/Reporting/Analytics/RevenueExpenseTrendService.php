<?php

namespace App\Services\Reporting\Analytics;

use App\Services\Reporting\IncomeStatementService;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueExpenseTrendService
{
    private IncomeStatementService $incomeStatement;

    public function __construct()
    {
        $this->incomeStatement = new IncomeStatementService();
    }

    public function calculate(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        int $periods = 12,
        ?int $branchId = null,
        ?int $costCenterId = null,
        string $dimension = 'none'
    ): array {
        $totalDays = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
        $periodLength = max(1, (int) round($totalDays / max(1, $periods)));

        $results = [];
        $labels = [];
        $revenueData = [];
        $expenseData = [];
        $netIncomeData = [];

        $start = Carbon::parse($dateFrom);

        for ($i = 0; $i < $periods; $i++) {
            $periodEnd = $start->copy()->addDays($periodLength)->subDay();
            if ($periodEnd->gt(Carbon::parse($dateTo))) {
                $periodEnd = Carbon::parse($dateTo);
            }

            $periodLabel = $start->format('M Y');
            $labels[] = $periodLabel;

            if ($dimension !== 'none') {
                $dimensionData = $this->getByDimension(
                    $companyId,
                    $start->format('Y-m-d'),
                    $periodEnd->format('Y-m-d'),
                    $dimension,
                    $branchId,
                    $costCenterId
                );
                $results[] = [
                    'period' => $periodLabel,
                    'dimensions' => $dimensionData,
                ];
                $totalRevenue = array_sum(array_column($dimensionData, 'revenue'));
                $totalExpense = array_sum(array_column($dimensionData, 'expense'));
            } else {
                $is = $this->incomeStatement->generate(
                    $companyId,
                    $branchId,
                    $start->format('Y-m-d'),
                    $periodEnd->format('Y-m-d'),
                    null,
                    $costCenterId
                );
                $totalRevenue = $is['total_income'];
                $totalExpense = $is['total_expenses'];

                $results[] = [
                    'period' => $periodLabel,
                    'revenue' => $totalRevenue,
                    'expense' => $totalExpense,
                    'net_income' => $totalRevenue - $totalExpense,
                ];
            }

            $revenueData[] = $totalRevenue;
            $expenseData[] = $totalExpense;
            $netIncomeData[] = $totalRevenue - $totalExpense;

            $start = $start->copy()->addDays($periodLength);
        }

        return [
            'results' => $results,
            'labels' => $labels,
            'revenue_data' => $revenueData,
            'expense_data' => $expenseData,
            'net_income_data' => $netIncomeData,
            'total_revenue' => array_sum($revenueData),
            'total_expense' => array_sum($expenseData),
            'periods' => $periods,
        ];
    }

    private function getByDimension(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        string $dimension,
        ?int $branchId,
        ?int $costCenterId
    ): array {
        $dimColumn = $dimension === 'branch' ? 'journal_entry_lines.branch_id' : 'journal_entry_lines.cost_center_id';

        $rows = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->where('journal_entries.date', '>=', $dateFrom)
            ->where('journal_entries.date', '<=', $dateTo)
            ->whereIn('accounts.type', ['income', 'expense'])
            ->selectRaw("
                COALESCE({$dimColumn}, 0) as dim_id,
                SUM(CASE WHEN accounts.type = 'income' THEN journal_entry_lines.credit - journal_entry_lines.debit ELSE 0 END) as revenue,
                SUM(CASE WHEN accounts.type = 'expense' THEN journal_entry_lines.debit - journal_entry_lines.credit ELSE 0 END) as expense
            ")
            ->groupBy('dim_id')
            ->get()
            ->keyBy('dim_id');

        $result = [];
        foreach ($rows as $dimId => $data) {
            $name = $dimId == 0
                ? 'Consolidated'
                : ($dimension === 'branch'
                    ? ($this->getBranchName($companyId, $dimId) ?? 'Unknown')
                    : ($this->getCostCenterName($companyId, $dimId) ?? 'Unknown'));
            $result[] = [
                'name' => $name,
                'revenue' => abs((float) $data->revenue),
                'expense' => abs((float) $data->expense),
                'net_income' => abs((float) $data->revenue) - abs((float) $data->expense),
            ];
        }

        return $result;
    }

    private function getBranchName(int $companyId, int $branchId): ?string
    {
        return Branch::where('company_id', $companyId)->where('id', $branchId)->value('name');
    }

    private function getCostCenterName(int $companyId, int $costCenterId): ?string
    {
        return CostCenter::where('company_id', $companyId)->where('id', $costCenterId)->value('name');
    }
}
