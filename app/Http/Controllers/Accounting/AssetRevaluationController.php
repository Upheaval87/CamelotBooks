<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetRevaluation;
use App\Services\Accounting\FixedAssetService;
use Illuminate\Http\Request;

class AssetRevaluationController extends Controller
{
    public function __construct(
        private FixedAssetService $service,
    ) {}

    public function index()
    {
        $companyId = session('current_company_id');

        $revaluations = AssetRevaluation::where('company_id', $companyId)
            ->with('asset')
            ->orderByDesc('revaluation_date')
            ->get();

        return view('accounting.asset-revaluations.index', compact('revaluations'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $assets = Asset::where('company_id', $companyId)
            ->active()
            ->activeStatus()
            ->where('is_revaluation_enabled', true)
            ->orderBy('asset_code')
            ->get();

        return view('accounting.asset-revaluations.create', compact('assets'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'revaluation_date' => 'required|date',
            'fair_value' => 'required|numeric|min:0',
            'memo' => 'nullable|string|max:1000',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);
        if ($asset->company_id !== $companyId) {
            abort(404);
        }

        $this->service->createRevaluation($companyId, $asset, $validated, auth()->id());

        return redirect()->route('accounting.asset-revaluations.index')
            ->with('success', 'Asset revaluation recorded successfully.');
    }
}
