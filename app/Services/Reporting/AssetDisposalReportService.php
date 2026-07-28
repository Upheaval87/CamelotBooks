<?php

namespace App\Services\Reporting;

use App\Models\AssetDisposal;

class AssetDisposalReportService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $disposals = AssetDisposal::where('company_id', $companyId)
            ->with(['asset', 'journalEntry', 'createdBy'])
            ->when($dateFrom, fn ($q) => $q->where('disposal_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('disposal_date', '<=', $dateTo))
            ->orderBy('disposal_date', 'desc')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'asset_name' => $d->asset->name ?? 'N/A',
                'asset_code' => $d->asset->asset_code ?? '',
                'disposal_date' => $d->disposal_date,
                'disposal_method' => $d->disposal_method,
                'acquisition_cost' => (float) ($d->asset->acquisition_cost ?? 0),
                'proceeds_amount' => (float) $d->proceeds_amount,
                'gain_loss_amount' => (float) $d->gain_loss_amount,
                'journal_entry_id' => $d->journal_entry_id,
                'memo' => $d->memo,
                'created_by' => $d->createdBy->name ?? '—',
            ])->toArray();

        $totalProceeds = collect($disposals)->sum('proceeds_amount');
        $totalGainLoss = collect($disposals)->sum('gain_loss_amount');

        return [
            'disposals' => $disposals,
            'total_proceeds' => $totalProceeds,
            'total_gain_loss' => $totalGainLoss,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
