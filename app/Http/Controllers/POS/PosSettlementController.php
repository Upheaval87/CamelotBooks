<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PosPaymentMethod;
use App\Models\PosSettlement;
use App\Services\POS\PosSettlementService;
use Illuminate\Http\Request;

class PosSettlementController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $settlements = PosSettlement::forCompany($companyId)
            ->with(['paymentMethod', 'bankAccount', 'settledBy'])
            ->latest()
            ->paginate(20);

        return view('pos.settlements.index', compact('settlements'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $paymentMethods = PosPaymentMethod::forCompany($companyId)->active()->get();
        $bankAccounts = Account::where('company_id', $companyId)
            ->whereIn('code', ['1000', '1050'])
            ->orWhere('type', 'asset')
            ->where('sub_type', 'bank')
            ->get();

        if ($bankAccounts->isEmpty()) {
            $bankAccounts = Account::where('company_id', $companyId)
                ->where('code', '1000')
                ->get();
        }

        return view('pos.settlements.create', compact('paymentMethods', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $data = $request->validate([
            'payment_method_id' => 'required|exists:pos_payment_methods,id',
            'bank_account_id' => 'required|exists:accounts,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'total_amount' => 'required|numeric|min:0.01',
            'fee_amount' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data['company_id'] = $companyId;

        $settlement = app(PosSettlementService::class)->settle($data, $userId);

        return redirect()
            ->route('pos.settlements.show', $settlement->id)
            ->with('success', "Settlement {$settlement->settlement_number} recorded successfully.");
    }

    public function show(PosSettlement $settlement)
    {
        $companyId = session('current_company_id');
        abort_unless($settlement->company_id === $companyId, 403);

        $settlement->load(['paymentMethod', 'bankAccount', 'journalEntry.lines.account', 'settledBy']);

        return view('pos.settlements.show', compact('settlement'));
    }
}
