<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankDeposit;
use App\Models\BankDepositLine;
use App\Models\Company;
use App\Models\Currency;
use App\Services\Accounting\BankingDepositService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BankingDepositController extends Controller
{
    public function __construct(protected BankingDepositService $depositService)
    {
    }

    /**
     * The system-set company currency symbol, resolved from the company's base
     * currency via the central Currency catalog. Falls back to the configured
     * system_settings symbol, then to '$'.
     */
    protected function currencySymbol(int $companyId): string
    {
        $baseCurrency = Company::find($companyId)?->base_currency;
        if ($baseCurrency) {
            $symbol = Currency::where('code', $baseCurrency)->first()?->symbol;
            if ($symbol) {
                return $symbol;
            }
        }

        return \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');
    }

    public function index(Request $request)
    {
        $this->requirePermission($request, 'deposits.view');

        $companyId = (int) session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $undepositedLines = $this->depositService->undepositedLines($companyId);
        $undepositedBalance = $this->depositService->undepositedBalance($companyId);

        // Payment-method filter (server-side) + distinct method list for the select.
        $paymentMethodList = $undepositedLines
            ->pluck('payment_method')
            ->filter(fn ($m) => $m && $m !== '—')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $filteredLines = $undepositedLines;
        if ($request->has('payment_method') && $request->query('payment_method') !== '') {
            $wanted = (string) $request->query('payment_method');
            $filteredLines = $undepositedLines->filter(fn ($l) => $l['payment_method'] === $wanted);
        }

        $deposits = BankDeposit::forCompany($companyId)
            ->with('bankAccount', 'createdBy', 'postedBy', 'voidedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        // Mockup KPI row: Undeposited Funds = Σ undeposited; Bank Accounts count;
        // Deposits This Month (posted) count.
        $undepositedSum = (float) $undepositedLines->sum('amount');
        $bankAccountCount = $bankAccounts->count();
        $depositsThisMonth = $deposits
            ->where('status', BankDeposit::STATUS_POSTED)
            ->filter(fn ($d) => $d->deposit_date?->isSameMonth(now()))
            ->count();

        $cs = $this->currencySymbol($companyId);

        return view('accounting.banking.deposits', [
            'bankAccounts' => $bankAccounts,
            'undepositedLines' => $filteredLines,
            'undepositedBalance' => $undepositedBalance,
            'deposits' => $deposits,
            'undepositedSum' => $undepositedSum,
            'bankAccountCount' => $bankAccountCount,
            'depositsThisMonth' => $depositsThisMonth,
            'cs' => $cs,
            'paymentMethodList' => $paymentMethodList,
            'currentPaymentMethod' => (string) $request->query('payment_method', ''),
        ]);
    }

    public function create(Request $request)
    {
        $this->requirePermission($request, 'deposits.create');

        $companyId = (int) session('current_company_id');

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $undepositedLines = $this->depositService->undepositedLines($companyId);

        $preselected = [];
        if ($request->has('line_ids')) {
            $preselected = collect(explode(',', (string) $request->query('line_ids')))
                ->filter(fn ($id) => $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $cs = $this->currencySymbol($companyId);

        return view('accounting.banking.deposit-form', compact('bankAccounts', 'undepositedLines', 'preselected', 'cs'));
    }

    public function show(Request $request, int $deposit)
    {
        $this->requirePermission($request, 'deposits.view');

        $companyId = (int) session('current_company_id');

        $deposit = BankDeposit::forCompany($companyId)
            ->with('lines', 'lines.salesReceipt', 'bankAccount', 'createdBy', 'postedBy', 'voidedBy', 'journalEntry')
            ->findOrFail($deposit);

        return view('accounting.banking.deposit-show', compact('deposit'));
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'deposits.create');

        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:255'],
            'line_ids' => ['required', 'array', 'min:1'],
            'line_ids.*' => ['integer'],
            'action' => ['nullable', 'string', 'in:post,draft'],
        ]);

        $before = BankDepositLine::count();
        $beforeDeposits = BankDeposit::count();

        try {
            $deposit = $this->depositService->create(
                $companyId,
                (int) $validated['bank_account_id'],
                $validated['date'],
                $validated['line_ids'],
                auth()->id(),
                [
                    'description' => $validated['description'] ?? null,
                    'reference' => $validated['reference'] ?? null,
                    'post' => ($validated['action'] ?? null) === 'post',
                ],
            );

            $after = BankDepositLine::count();
            $afterDeposits = BankDeposit::count();

            logger()->info('BankDeposit.store', [
                'deposit_id' => $deposit->id,
                'deposit_no' => $deposit->deposit_no,
                'status' => $deposit->status,
                'lines_before' => $before,
                'lines_after' => $after,
                'deposits_before' => $beforeDeposits,
                'deposits_after' => $afterDeposits,
            ]);

            return redirect()->route('accounting.banking.deposits')
                ->with('success', $deposit->isPosted()
                    ? "Deposit {$deposit->deposit_no} recorded and posted."
                    : "Deposit {$deposit->deposit_no} saved as draft.");
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function void(Request $request, int $deposit)
    {
        $this->requirePermission($request, 'deposits.void');

        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $deposit = BankDeposit::forCompany($companyId)->findOrFail($deposit);

        try {
            $this->depositService->void($deposit, auth()->id(), $validated['reason'] ?? null);
            return redirect()->route('accounting.banking.deposits')
                ->with('success', "Deposit {$deposit->deposit_no} voided.");
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
