<?php

namespace App\Services\Reporting\Analytics;

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Services\Accounting\ActualsService;
use App\Services\Reporting\IncomeStatementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseAnalyticsService
{
    private IncomeStatementService $incomeStatement;

    public function __construct()
    {
        $this->incomeStatement = new IncomeStatementService();
    }

    public function calculate(int $companyId, array $period, ?int $branchId = null, ?int $costCenterId = null): array
    {
        $is = $this->incomeStatement->generate($companyId, $branchId, $period['from'], $period['to'], null, $costCenterId);
        $isPrev = $this->incomeStatement->generate($companyId, $branchId, $period['prev_from'], $period['prev_to'], null, $costCenterId);

        $total = (float) ($is['total_expenses'] ?? 0);
        $totalPrev = (float) ($isPrev['total_expenses'] ?? 0);

        $accounts = $this->expenseAccounts($companyId, $branchId, $period['from'], $period['to'], $costCenterId);
        $mix = $this->mix($accounts, $total);
        $top = $this->topAccounts($companyId, $accounts, $period);

        $monthly = $this->monthlyTotals($companyId, $branchId, $period, $costCenterId);

        $payroll = $mix['Payroll']['value'] ?? 0;

        return [
            'kpis' => [
                'total_expense' => ['value' => $total, 'prev' => $totalPrev],
                'payroll' => ['value' => $payroll],
                'payroll_share' => ['value' => $total > 0 ? ($payroll / $total) * 100 : 0],
                'top_account_share' => ['value' => !empty($top) && $total > 0 ? ($top[0]['total'] / $total) * 100 : 0],
            ],
            'mix' => $mix,
            'top_accounts' => $top,
            'monthly_labels' => $monthly['labels'],
            'monthly_data' => $monthly['values'],
            'total' => $total,
        ];
    }

    private function expenseAccounts(int $companyId, ?int $branchId, string $from, string $to, ?int $costCenterId): array
    {
        return Account::forCompany($companyId)->whereIn('type', ['expense', 'cost_of_goods_sold'])
            ->where('is_active', true)
            ->get()
            ->map(function ($account) use ($companyId, $branchId, $from, $to, $costCenterId) {
                $q = DB::table('journal_entry_lines as l')
                    ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
                    ->where('e.company_id', $companyId)
                    ->where('l.account_id', $account->id)
                    ->whereIn('e.status', ['posted', 'reversed'])
                    ->whereBetween('e.date', [$from, $to]);

                if ($branchId) {
                    $q->where('l.branch_id', $branchId);
                }
                if ($costCenterId) {
                    $q->where('l.cost_center_id', $costCenterId);
                }

                $row = $q->selectRaw('COALESCE(SUM(l.debit),0) as d, COALESCE(SUM(l.credit),0) as c')->first();
                $net = (float) ($row->d ?? 0) - (float) ($row->c ?? 0);

                if (abs($net) < 0.001) {
                    return null;
                }

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'sub_type' => $account->sub_type,
                    'total' => $net,
                ];
            })
            ->filter()
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function mix(array $accounts, float $total): array
    {
        $buckets = ['Payroll' => 0, 'Cost of Goods Sold' => 0, 'Occupancy' => 0, 'Other' => 0];

        foreach ($accounts as $a) {
            $name = strtolower($a['name']);
            $sub = strtolower((string) $a['sub_type']);

            if (str_contains($sub, 'cost_of_goods_sold')) {
                $buckets['Cost of Goods Sold'] += $a['total'];
            } elseif (preg_match('/(salary|wage|payroll|benefit|pension)/', $name)) {
                $buckets['Payroll'] += $a['total'];
            } elseif (preg_match('/(rent|lease|utilities|occupancy|office|property)/', $name)) {
                $buckets['Occupancy'] += $a['total'];
            } else {
                $buckets['Other'] += $a['total'];
            }
        }

        $result = [];
        foreach ($buckets as $label => $value) {
            $result[$label] = [
                'value' => $value,
                'pct' => $total > 0 ? round(($value / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    private function topAccounts(int $companyId, array $accounts, array $period): array
    {
        $budgetMap = $this->budgetMap($companyId, $period);

        return array_map(function ($a) use ($budgetMap, $period) {
            $budgeted = $budgetMap[$a['id']] ?? null;
            return [
                'id' => $a['id'],
                'code' => $a['code'],
                'name' => $a['name'],
                'total' => $a['total'],
                'budgeted' => $budgeted,
                'status' => $budgeted === null ? null : ($a['total'] <= $budgeted ? 'ok' : 'over'),
            ];
        }, array_slice($accounts, 0, 8));
    }

    private function budgetMap(int $companyId, array $period): array
    {
        $lines = BudgetLine::whereHas('budget', function ($q) use ($companyId, $period) {
            $q->where('company_id', $companyId)->whereIn('status', ['approved', 'locked']);
        })->get();

        $elapsed = $this->elapsedMonthsInFy($companyId, $period['to']);

        $map = [];
        $actuals = new ActualsService();

        foreach ($lines as $line) {
            if ($line->line_type !== 'expense') {
                continue;
            }
            $annual = (float) $line->annual_amount;
            $proportional = $annual * ($elapsed / 12);
            if ($proportional > 0) {
                $map[$line->account_id] = round($proportional, 2);
            }

            if (count($map) >= 50) {
                break;
            }
        }

        return $map;
    }

    private function elapsedMonthsInFy(int $companyId, string $to): int
    {
        $company = \App\Models\Company::find($companyId);
        $fyMonth = $company->fiscal_year_start_month ?? 1;

        $date = Carbon::parse($to);
        $fyStart = $date->month >= $fyMonth
            ? $date->copy()->startOfYear()->addMonths($fyMonth - 1)
            : $date->copy()->subYear()->startOfYear()->addMonths($fyMonth - 1);

        $elapsed = $fyStart->diffInMonths($date) + 1;
        return max(1, min(12, $elapsed));
    }

    private function monthlyTotals(int $companyId, ?int $branchId, array $period, ?int $costCenterId): array
    {
        $start = Carbon::parse($period['from'])->startOfMonth();
        $end = Carbon::parse($period['to']);

        $rows = DB::table('journal_entry_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'l.account_id')
            ->where('e.company_id', $companyId)
            ->whereIn('a.type', ['expense', 'cost_of_goods_sold'])
            ->whereIn('e.status', ['posted', 'reversed'])
            ->whereBetween('e.date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->when($branchId, fn ($q) => $q->where('l.branch_id', $branchId))
            ->when($costCenterId, fn ($q) => $q->where('l.cost_center_id', $costCenterId))
            ->selectRaw("DATE_FORMAT(e.date, '%Y-%m') as ym, SUM(l.debit) - SUM(l.credit) as total")
            ->groupBy('ym')
            ->get();

        $labels = [];
        $values = [];
        $cursor = $start->copy();

        while ($cursor->format('Y-m') <= $end->format('Y-m')) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->format('M y');
            $values[] = (float) ($rows->firstWhere('ym', $key)->total ?? 0);
            $cursor->addMonth();
        }

        return ['labels' => $labels, 'values' => $values];
    }
}