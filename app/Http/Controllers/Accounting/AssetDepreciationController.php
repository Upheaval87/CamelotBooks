<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetDepreciationBook;
use App\Models\UnitsOfProductionUsageEntry;
use App\Services\DepreciationEngine;
use Illuminate\Http\Request;

class AssetDepreciationController extends Controller
{
    public function __construct(
        private DepreciationEngine $engine,
    ) {}

    public function schedule(Asset $asset)
    {
        $companyId = session('current_company_id');
        if ($asset->company_id !== $companyId) {
            abort(404);
        }

        $asset->load([
            'depreciationBooks.scheduleEntries',
            'category',
        ]);

        return view('accounting.asset-depreciation.schedule', compact('asset'));
    }

    public function runHistory()
    {
        $companyId = session('current_company_id');

        $runs = \App\Models\DepreciationRun::where('company_id', $companyId)
            ->orderByDesc('period')
            ->get();

        return view('accounting.asset-depreciation.run-history', compact('runs'));
    }

    public function run(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $period = $validated['period'] . '-01';

        $this->engine->runDepreciation($companyId, $period, auth()->id());

        return redirect()->route('accounting.asset-depreciation.run-history')
            ->with('success', 'Depreciation run completed for ' . $validated['period'] . '.');
    }

    public function usageLog()
    {
        $companyId = session('current_company_id');

        $entries = UnitsOfProductionUsageEntry::where('company_id', $companyId)
            ->with('asset')
            ->orderByDesc('period_start')
            ->get();

        $assets = Asset::where('company_id', $companyId)
            ->active()
            ->activeStatus()
            ->where('depreciation_method_financial', 'units_of_production')
            ->orderBy('asset_code')
            ->get();

        return view('accounting.asset-depreciation.usage-log', compact('entries', 'assets'));
    }
}
