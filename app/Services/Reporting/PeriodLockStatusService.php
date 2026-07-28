<?php
namespace App\Services\Reporting;
use App\Models\FiscalYear;

class PeriodLockStatusService
{
    public function generate(int $companyId): array
    {
        $years = FiscalYear::forCompany($companyId)
            ->with(['periods', 'closedByUser'])
            ->orderBy('start_date', 'desc')
            ->get();

        $results = [];
        foreach ($years as $fy) {
            $periods = $fy->periods->map(fn($p) => [
                'label' => $p->label,
                'start_date' => $p->start_date,
                'end_date' => $p->end_date,
                'status' => $p->status,
            ])->toArray();

            $results[] = [
                'label' => $fy->label,
                'start_date' => $fy->start_date,
                'end_date' => $fy->end_date,
                'status' => $fy->status,
                'closed_by' => $fy->closedByUser?->name,
                'closed_at' => $fy->closed_at,
                'periods' => $periods,
            ];
        }

        return ['fiscal_years' => $results];
    }
}
