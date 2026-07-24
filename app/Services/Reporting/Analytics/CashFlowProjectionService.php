<?php

namespace App\Services\Reporting\Analytics;

use App\Services\Reporting\CashFlowStatementService;
use Carbon\Carbon;

class CashFlowProjectionService
{
    private CashFlowStatementService $cashFlowService;

    public function __construct()
    {
        $this->cashFlowService = app(CashFlowStatementService::class);
    }

    public function calculate(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        ?int $branchId = null,
        int $projectionMonths = 6
    ): array {
        // Historical monthly data
        $historicalLabels = [];
        $historicalOperating = [];
        $historicalInvesting = [];
        $historicalFinancing = [];
        $historicalNet = [];
        
        $start = Carbon::parse($dateFrom)->startOfMonth();
        $end = Carbon::parse($dateTo);
        
        while ($start->lte($end)) {
            $monthEnd = $start->copy()->endOfMonth();
            if ($monthEnd->gt($end)) {
                $monthEnd = $end->copy();
            }
            
            $historicalLabels[] = $start->format('M Y');
            
            $cf = $this->cashFlowService->generate($companyId, $branchId, $start->format('Y-m-d'), $monthEnd->format('Y-m-d'));
            
            $historicalOperating[] = $cf['operating_total'];
            $historicalInvesting[] = $cf['investing_total'];
            $historicalFinancing[] = $cf['financing_total'];
            $historicalNet[] = $cf['net_change'];
            
            $start->addMonth();
        }
        
        // Projection using simple moving average of last 3 months
        $projectionLabels = [];
        $projectionOperating = [];
        $projectionInvesting = [];
        $projectionFinancing = [];
        $projectionNet = [];
        
        $lastValues = [
            'operating' => array_slice($historicalOperating, -3),
            'investing' => array_slice($historicalInvesting, -3),
            'financing' => array_slice($historicalFinancing, -3),
            'net' => array_slice($historicalNet, -3),
        ];
        
        $projectionStart = $end->copy()->addMonth();
        
        for ($i = 0; $i < $projectionMonths; $i++) {
            $projectionLabels[] = $projectionStart->format('M Y');
            
            $avgOperating = count($lastValues['operating']) > 0 ? array_sum($lastValues['operating']) / count($lastValues['operating']) : 0;
            $avgInvesting = count($lastValues['investing']) > 0 ? array_sum($lastValues['investing']) / count($lastValues['investing']) : 0;
            $avgFinancing = count($lastValues['financing']) > 0 ? array_sum($lastValues['financing']) / count($lastValues['financing']) : 0;
            $avgNet = $avgOperating + $avgInvesting + $avgFinancing;
            
            $projectionOperating[] = $avgOperating;
            $projectionInvesting[] = $avgInvesting;
            $projectionFinancing[] = $avgFinancing;
            $projectionNet[] = $avgNet;
            
            // Shift window for next projection
            array_shift($lastValues['operating']);
            $lastValues['operating'][] = $avgOperating;
            array_shift($lastValues['investing']);
            $lastValues['investing'][] = $avgInvesting;
            array_shift($lastValues['financing']);
            $lastValues['financing'][] = $avgFinancing;
            array_shift($lastValues['net']);
            $lastValues['net'][] = $avgNet;
            
            $projectionStart->addMonth();
        }
        
        $allLabels = array_merge($historicalLabels, $projectionLabels);
        
        return [
            'labels' => $allLabels,
            'historical_labels' => $historicalLabels,
            'projection_labels' => $projectionLabels,
            'operating' => $historicalOperating,
            'investing' => $historicalInvesting,
            'financing' => $historicalFinancing,
            'net' => $historicalNet,
            'projection_operating' => $projectionOperating,
            'projection_investing' => $projectionInvesting,
            'projection_financing' => $projectionFinancing,
            'projection_net' => $projectionNet,
            'historical_count' => count($historicalLabels),
            'projection_count' => $projectionMonths,
            'is_projection' => true,
            'projection_note' => 'Projected values are based on trend analysis and are not a guarantee of future performance.',
        ];
    }
}
