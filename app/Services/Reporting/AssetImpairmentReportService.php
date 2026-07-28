<?php
namespace App\Services\Reporting;
use App\Models\AssetImpairment;

class AssetImpairmentReportService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = AssetImpairment::whereHas('asset', fn($q) => $q->forCompany($companyId))
            ->with(['asset.category', 'journalEntry']);

        if ($dateFrom) $query->where('impairment_date', '>=', $dateFrom);
        if ($dateTo) $query->where('impairment_date', '<=', $dateTo);

        $events = $query->orderBy('impairment_date', 'desc')->get();

        $results = [];
        $totalImpairment = 0;
        $totalReversal = 0;
        foreach ($events as $e) {
            $amount = $e->is_reversal ? (float) $e->reversal_amount : (float) $e->impairment_amount;
            if ($e->is_reversal) { $totalReversal += $amount; } else { $totalImpairment += $amount; }
            $results[] = [
                'date' => $e->impairment_date,
                'asset_name' => $e->asset->name ?? 'N/A',
                'asset_code' => $e->asset->asset_code ?? '',
                'category' => $e->asset->category->name ?? '',
                'carrying_amount' => (float) $e->carrying_amount,
                'recoverable_amount' => (float) $e->recoverable_amount,
                'amount' => $amount,
                'is_reversal' => (bool) $e->is_reversal,
                'journal_entry_id' => $e->journal_entry_id,
            ];
        }

        return ['events' => $results, 'total_impairment' => $totalImpairment, 'total_reversal' => $totalReversal, 'date_from' => $dateFrom, 'date_to' => $dateTo];
    }
}
