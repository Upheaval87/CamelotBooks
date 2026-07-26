<?php

namespace App\Services\BI;

use App\Models\BiDigestSchedule;
use Illuminate\Support\Facades\DB;

class ExecutiveDigestService
{
    public function collectForSchedule(BiDigestSchedule $schedule): array
    {
        $companyId = $schedule->company_id;

        $period = match ($schedule->frequency) {
            BiDigestSchedule::FREQUENCY_DAILY   => ['since' => now()->subDay(), 'label' => 'Yesterday'],
            BiDigestSchedule::FREQUENCY_WEEKLY  => ['since' => now()->subWeek(), 'label' => 'Last 7 Days'],
            BiDigestSchedule::FREQUENCY_MONTHLY => ['since' => now()->subMonth(), 'label' => 'Last 30 Days'],
        };

        $dateFrom = $period['since']->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $revenue = $this->getRevenue($companyId, $dateFrom, $dateTo);
        $expenses = $this->getExpenses($companyId, $dateFrom, $dateTo);
        $topCustomers = $this->getTopCustomers($companyId, $dateFrom, $dateTo);
        $branchSummary = $this->getBranchSummary($companyId, $dateFrom, $dateTo);
        $cashPosition = $this->getCashPosition($companyId);
        $arAging = $this->getArAging($companyId);

        return [
            'period_label'   => $period['label'],
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
            'revenue'        => $revenue,
            'expenses'       => $expenses,
            'net_income'     => $revenue - $expenses,
            'top_customers'  => $topCustomers,
            'branch_summary' => $branchSummary,
            'cash_position'  => $cashPosition,
            'ar_aging'       => $arAging,
        ];
    }

    protected function getRevenue(int $companyId, string $dateFrom, string $dateTo): float
    {
        return (float) DB::table('fact_general_ledger AS fgl')
            ->join('dim_account AS da', 'da.account_key', '=', 'fgl.account_key')
            ->where('fgl.company_key', $companyId)
            ->where('fgl.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fgl.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->where('da.account_type', 'income')
            ->sum(DB::raw('fgl.credit - fgl.debit'));
    }

    protected function getExpenses(int $companyId, string $dateFrom, string $dateTo): float
    {
        return (float) DB::table('fact_general_ledger AS fgl')
            ->join('dim_account AS da', 'da.account_key', '=', 'fgl.account_key')
            ->where('fgl.company_key', $companyId)
            ->where('fgl.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fgl.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->where('da.account_type', 'expense')
            ->sum(DB::raw('fgl.debit - fgl.credit'));
    }

    protected function getTopCustomers(int $companyId, string $dateFrom, string $dateTo): array
    {
        return DB::table('fact_sales AS fs')
            ->leftJoin('dim_customer AS dc', 'dc.customer_key', '=', 'fs.customer_key')
            ->where('fs.company_key', $companyId)
            ->where('fs.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fs.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->where('fs.is_credit_note', false)
            ->select('dc.customer_name', DB::raw('SUM(fs.line_total) AS total'))
            ->groupBy('dc.customer_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->toArray();
    }

    protected function getBranchSummary(int $companyId, string $dateFrom, string $dateTo): array
    {
        return DB::table('fact_general_ledger AS fgl')
            ->leftJoin('dim_branch AS db', 'db.branch_key', '=', 'fgl.branch_key')
            ->join('dim_account AS da', 'da.account_key', '=', 'fgl.account_key')
            ->where('fgl.company_key', $companyId)
            ->where('fgl.date_key', '>=', (int) \Carbon\Carbon::parse($dateFrom)->format('Ymd'))
            ->where('fgl.date_key', '<=', (int) \Carbon\Carbon::parse($dateTo)->format('Ymd'))
            ->select(
                DB::raw("COALESCE(db.branch_name, 'Unallocated') AS branch_name"),
                DB::raw("SUM(CASE WHEN da.account_type = 'income' THEN fgl.credit - fgl.debit ELSE 0 END) AS revenue"),
                DB::raw("SUM(CASE WHEN da.account_type = 'expense' THEN fgl.debit - fgl.credit ELSE 0 END) AS expenses")
            )
            ->groupBy('db.branch_name')
            ->get()
            ->toArray();
    }

    protected function getCashPosition(int $companyId): float
    {
        return (float) DB::table('fact_general_ledger AS fgl')
            ->join('dim_account AS da', 'da.account_key', '=', 'fgl.account_key')
            ->where('fgl.company_key', $companyId)
            ->where('da.account_type', 'asset')
            ->where('da.is_bank_account', true)
            ->sum(DB::raw('fgl.debit - fgl.credit'));
    }

    protected function getArAging(int $companyId): array
    {
        $today = now()->format('Y-m-d');

        return DB::table('invoices AS i')
            ->where('i.company_id', $companyId)
            ->whereNotIn('i.status', ['paid', 'void'])
            ->select(
                DB::raw("SUM(CASE WHEN i.due_date >= '{$today}' THEN i.amount - i.amount_paid ELSE 0 END) AS current_not_due"),
                DB::raw("SUM(CASE WHEN i.due_date < '{$today}' AND i.due_date >= DATE('{$today}', '-30 days') THEN i.amount - i.amount_paid ELSE 0 END) AS days_1_30"),
                DB::raw("SUM(CASE WHEN i.due_date < DATE('{$today}', '-30 days') AND i.due_date >= DATE('{$today}', '-60 days') THEN i.amount - i.amount_paid ELSE 0 END) AS days_31_60"),
                DB::raw("SUM(CASE WHEN i.due_date < DATE('{$today}', '-60 days') THEN i.amount - i.amount_paid ELSE 0 END) AS days_61_plus")
            )
            ->first();
    }
}
