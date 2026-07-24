<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetImpairment;
use App\Services\FixedAssetService;
use Illuminate\Http\Request;

class AssetImpairmentController extends Controller
{
    public function __construct(
        private FixedAssetService $service,
    ) {}

    public function index()
    {
        $companyId = session('current_company_id');

        $impairments = AssetImpairment::where('company_id', $companyId)
            ->with('asset')
            ->orderByDesc('impairment_date')
            ->get();

        return view('accounting.asset-impairments.index', compact('impairments'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $assets = Asset::where('company_id', $companyId)
            ->active()
            ->activeStatus()
            ->orderBy('asset_code')
            ->get();

        return view('accounting.asset-impairments.create', compact('assets'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'impairment_date' => 'required|date',
            'recoverable_amount' => 'required|numeric|min:0',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);
        if ($asset->company_id !== $companyId) {
            abort(404);
        }

        $this->service->createImpairment($companyId, $asset, $validated, auth()->id());

        return redirect()->route('accounting.asset-impairments.index')
            ->with('success', 'Asset impairment recorded successfully.');
    }

    public function reverse(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'impairment_id' => 'required|exists:asset_impairments,id',
            'recoverable_amount' => 'required|numeric|min:0',
            'memo' => 'nullable|string|max:1000',
        ]);

        $impairment = AssetImpairment::findOrFail($validated['impairment_id']);
        if ($impairment->company_id !== $companyId) {
            abort(404);
        }

        $this->service->reverseImpairment($companyId, $impairment, $validated, auth()->id());

        return redirect()->route('accounting.asset-impairments.index')
            ->with('success', 'Impairment reversal recorded successfully.');
    }
}
