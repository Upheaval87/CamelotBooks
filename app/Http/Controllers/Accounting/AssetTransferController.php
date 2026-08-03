<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Services\Accounting\FixedAssetService;
use Illuminate\Http\Request;

class AssetTransferController extends Controller
{
    public function __construct(
        private FixedAssetService $service,
    ) {}

    public function index()
    {
        $companyId = session('current_company_id');

        $transfers = AssetTransfer::where('company_id', $companyId)
            ->with(['asset', 'fromBranch', 'toBranch', 'fromCostCenter', 'toCostCenter'])
            ->orderByDesc('transfer_date')
            ->get();

        return view('accounting.asset-transfers.index', compact('transfers'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $assets = Asset::where('company_id', $companyId)
            ->active()
            ->activeStatus()
            ->orderBy('asset_code')
            ->get();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.asset-transfers.create', compact('assets', 'branches', 'costCenters'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'transfer_date' => 'required|date',
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id',
            'from_cost_center_id' => 'required|exists:cost_centers,id',
            'to_cost_center_id' => 'required|exists:cost_centers,id',
            'memo' => 'nullable|string|max:1000',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);
        if ($asset->company_id !== $companyId) {
            abort(404);
        }

        $this->service->createTransfer($companyId, $asset, $validated, auth()->id());

        return redirect()->route('accounting.asset-transfers.index')
            ->with('success', 'Asset transfer recorded successfully.');
    }
}
