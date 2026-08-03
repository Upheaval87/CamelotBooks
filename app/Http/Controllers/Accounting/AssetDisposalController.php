<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Services\Accounting\FixedAssetService;
use Illuminate\Http\Request;

class AssetDisposalController extends Controller
{
    public function __construct(
        private FixedAssetService $service,
    ) {}

    public function index()
    {
        $companyId = session('current_company_id');

        $disposals = AssetDisposal::where('company_id', $companyId)
            ->with('asset')
            ->orderByDesc('disposal_date')
            ->get();

        return view('accounting.asset-disposals.index', compact('disposals'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $assets = Asset::where('company_id', $companyId)
            ->active()
            ->activeStatus()
            ->orderBy('asset_code')
            ->get();

        $bankAccounts = Account::where('company_id', $companyId)
            ->active()
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get();

        return view('accounting.asset-disposals.create', compact('assets', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'disposal_date' => 'required|date',
            'disposal_method' => 'required|string|max:255',
            'proceeds_amount' => 'required|numeric|min:0',
            'proceeds_account_id' => 'nullable|exists:accounts,id',
            'memo' => 'nullable|string|max:1000',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);
        if ($asset->company_id !== $companyId) {
            abort(404);
        }

        $disposal = $this->service->createDisposal($companyId, $asset, $validated, auth()->id());

        return redirect()->route('accounting.asset-disposals.index')
            ->with('success', 'Asset disposal recorded successfully.');
    }
}
