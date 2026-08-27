<?php
namespace App\Services\Reporting;
use App\Models\FixedAssets\FaRevaluation;

class AssetRevaluationReportService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = FaRevaluation::whereHas('asset', fn($q) => $q->forCompany($companyId))
            ->with(['asset.category', 'journalEntry']);

        if ($dateFrom) $query->where('revaluation_date', '>=', $dateFrom);
        if ($dateTo) $query->where('revaluation_date', '<=', $dateTo);

        $revaluations = $query->orderBy('revaluation_date', 'desc')->get();

        $results = [];
        $totalSurplus = 0;
        foreach ($revaluations as $r) {
            $surplus = (float) $r->surplus_amount;
            $totalSurplus += $surplus;
            $results[] = [
                'date' => $r->revaluation_date,
                'asset_name' => $r->asset->name ?? 'N/A',
                'asset_code' => $r->asset->asset_code ?? '',
                'category' => $r->asset->category->name ?? '',
                'previous_fair_value' => (float) $r->previous_value,
                'new_fair_value' => (float) $r->new_value,
                'surplus_amount' => $surplus,
                'journal_entry_id' => $r->journal_entry_id,
            ];
        }

        return ['revaluations' => $results, 'total_surplus' => $totalSurplus, 'date_from' => $dateFrom, 'date_to' => $dateTo];
    }
}
