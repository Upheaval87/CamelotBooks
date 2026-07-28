<?php

namespace App\Services\Reporting;

use App\Models\AssemblyBuild;

class AssemblyBuildHistoryService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $builds = AssemblyBuild::where('company_id', $companyId)
            ->with(['assemblyProduct', 'billOfMaterial', 'creator'])
            ->when($dateFrom, fn ($q) => $q->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('date', '<=', $dateTo))
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'build_number' => $b->build_number,
                'date' => $b->date,
                'type' => $b->type,
                'product_name' => $b->assemblyProduct->name ?? 'N/A',
                'product_sku' => $b->assemblyProduct->sku ?? '',
                'quantity' => (float) $b->quantity,
                'unit_cost' => (float) $b->unit_cost,
                'total_cost' => (float) $b->total_component_cost,
                'status' => $b->status,
                'memo' => $b->memo,
                'created_by' => $b->creator->name ?? '—',
            ])->toArray();

        $buildsOnly = collect($builds)->where('type', 'build');
        $unbuildsOnly = collect($builds)->where('type', 'unbuild');

        return [
            'builds' => $builds,
            'total_builds' => $buildsOnly->count(),
            'total_unbuilds' => $unbuildsOnly->count(),
            'total_cost_built' => $buildsOnly->sum('total_cost'),
            'total_cost_unbuilt' => $unbuildsOnly->sum('total_cost'),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
