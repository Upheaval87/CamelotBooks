<?php

namespace App\Services\Reporting;

use App\Models\Quotation;
use Illuminate\Support\Carbon;

class SalesPipelineService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = Quotation::forCompany($companyId)->with('customer');

        if ($dateFrom) {
            $query->where('quotation_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('quotation_date', '<=', $dateTo);
        }

        $quotations = $query->get();

        $statuses = [
            'draft' => ['label' => 'Draft', 'icon' => 'steel'],
            'sent' => ['label' => 'Sent', 'icon' => 'teal'],
            'accepted' => ['label' => 'Accepted', 'icon' => 'mint'],
            'converted' => ['label' => 'Converted', 'icon' => 'mint'],
            'declined' => ['label' => 'Declined', 'icon' => 'red'],
            'void' => ['label' => 'Void', 'icon' => 'gray'],
        ];

        $stages = [];
        $total = count($quotations);
        $openCount = 0;

        foreach ($statuses as $key => $meta) {
            $rows = $quotations->where('status', $key);
            $value = $rows->sum(fn ($q) => (float) $q->total);
            $count = $rows->count();
            $stages[$key] = [
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'count' => $count,
                'value' => $value,
                'pct' => $total > 0 ? round($count / $total * 100, 1) : 0,
            ];
            if (in_array($key, ['draft', 'sent', 'accepted'])) {
                $openCount += $count;
            }
        }

        $acceptedConverted = $stages['accepted']['count'] + $stages['converted']['count'];
        $decided = $acceptedConverted + $stages['declined']['count'];
        $winRate = $decided > 0 ? round($acceptedConverted / $decided * 100, 1) : 0;

        $today = Carbon::today();
        $agingBuckets = [
            '0-7' => ['label' => '0–7 days', 'count' => 0, 'value' => 0.0],
            '8-30' => ['label' => '8–30 days', 'count' => 0, 'value' => 0.0],
            '31-60' => ['label' => '31–60 days', 'count' => 0, 'value' => 0.0],
            '61+' => ['label' => '61+ days', 'count' => 0, 'value' => 0.0],
        ];
        $maxAging = 1;

        foreach ($quotations->whereIn('status', ['draft', 'sent', 'accepted']) as $q) {
            $age = (int) $q->quotation_date?->diffInDays($today, false) ?? 0;
            $bucket = $age <= 7 ? '0-7' : ($age <= 30 ? '8-30' : ($age <= 60 ? '31-60' : '61+'));
            $agingBuckets[$bucket]['count']++;
            $agingBuckets[$bucket]['value'] += (float) $q->total;
            if ($agingBuckets[$bucket]['count'] > $maxAging) {
                $maxAging = $agingBuckets[$bucket]['count'];
            }
        }
        foreach ($agingBuckets as $key => $bucket) {
            $agingBuckets[$key]['pct'] = $maxAging > 0 ? round($bucket['count'] / $maxAging * 100, 1) : 0;
        }

        return [
            'stages' => $stages,
            'aging' => $agingBuckets,
            'open_count' => $openCount,
            'open_value' => $quotations->whereIn('status', ['draft', 'sent', 'accepted'])->sum(fn ($q) => (float) $q->total),
            'total' => $total,
            'total_value' => $quotations->sum(fn ($q) => (float) $q->total),
            'accepted_count' => $acceptedConverted,
            'accepted_value' => $quotations->whereIn('status', ['accepted', 'converted'])->sum(fn ($q) => (float) $q->total),
            'win_rate' => $winRate,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
