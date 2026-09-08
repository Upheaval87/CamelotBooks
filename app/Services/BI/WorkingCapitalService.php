<?php

namespace App\Services\BI;

use App\Models\Bill;
use App\Models\InventoryCostLayer;
use App\Models\Invoice;
use App\Services\BI\Concerns\BiPeriodMetrics;
use App\Services\Reporting\AgingReportService;
use Illuminate\Support\Facades\DB;

class WorkingCapitalService
{
    use BiPeriodMetrics;

    public function calculate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $aging = new AgingReportService();
        $ar = $aging->arAging($companyId, $branchId, $dateTo);
        $ap = $aging->apAging($companyId, $branchId, $dateTo);
        $cash = $this->cashAndBankAsOf($companyId, $dateTo);

        $inventoryValue = (float) InventoryCostLayer::where('company_id', $companyId)
            ->where('date', '<=', $dateTo)
            ->where('quantity_remaining', '>', 0)
            ->sum(DB::raw('quantity_remaining * unit_cost'));

        $total = $this->statementTotals($companyId, $dateFrom, $dateTo, $branchId);

        $periodDays = max(1, (int) \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo)) + 1);
        $creditSales = (float) Invoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'paid', 'partially_paid'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $purchases = (float) Bill::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'paid', 'overdue'])
            ->whereBetween('bill_date', [$dateFrom, $dateTo])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $arTotal = (float) $ar['totals']['total'];
        $apTotal = (float) $ap['totals']['total'];
        $cogs = $total['cogs'];

        $dso = $creditSales > 0 ? round($arTotal / $creditSales * $periodDays, 1) : 0;
        $dio = $cogs > 0 ? round($inventoryValue / $cogs * $periodDays, 1) : 0;
        $dpo = $purchases > 0 ? round($apTotal / $purchases * $periodDays, 1) : 0;

        $workingCapital = $arTotal + $inventoryValue - $apTotal;
        $liabilities = $apTotal;
        $currentAssets = $cash['total'] + $arTotal + $inventoryValue;

        return [
            'as_of' => $dateTo,
            'cash' => round($cash['total'], 2),
            'receivables' => round($arTotal, 2),
            'inventory' => round($inventoryValue, 2),
            'payables' => round($apTotal, 2),
            'working_capital' => round($workingCapital, 2),
            'current_ratio' => $liabilities > 0 ? round($currentAssets / $liabilities, 2) : null,
            'dso' => $dso,
            'dio' => $dio,
            'dpo' => $dpo,
            'cash_conversion_cycle' => round($dso + $dio - $dpo, 1),
            'ar_aging' => $ar['totals'],
            'ap_aging' => $ap['totals'],
            'cash_accounts' => $cash['accounts'],
        ];
    }
}