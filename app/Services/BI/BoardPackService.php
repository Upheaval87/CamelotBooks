<?php

namespace App\Services\BI;

use App\Models\BiAction;

class BoardPackService
{
    public function compile(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $overview = new OverviewInsightService();
        $kpis = $overview->calculate($companyId, $dateFrom, $dateTo, $branchId);

        $workingCapital = new WorkingCapitalService();
        $wc = $workingCapital->calculate($companyId, $dateFrom, $dateTo, $branchId);

        $abc = new ProductAbcService();
        $products = $abc->calculate($companyId, $dateFrom, $dateTo, $branchId);

        $trend = $overview->monthlyTrend($companyId, $dateTo, $branchId, null);

        $actions = BiAction::where('company_id', $companyId)
            ->where('page', 'board')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'description', 'owner', 'due_date', 'status'])
            ->map(fn ($a) => [
                'id' => (int) $a->id,
                'description' => $a->description,
                'owner' => $a->owner,
                'due_date' => $a->due_date?->toDateString(),
                'status' => $a->status,
            ])
            ->toArray();

        $outstandingActions = count(array_filter($actions, fn ($a) => !in_array($a['status'], ['done', 'cancelled'], true)));

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'kpis' => $kpis['kpis'],
            'statement' => $kpis['statement'],
            'working_capital' => $wc,
            'products' => $products,
            'trend' => $trend,
            'actions' => $actions,
            'outstanding_actions' => $outstandingActions,
        ];
    }
}