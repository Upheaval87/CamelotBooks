<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreAccountRequest;
use App\Http\Requests\Accounting\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        // Global stats (unfiltered) for KPI strip
        $allAccounts = Account::where('company_id', $companyId)->get();
        $stats = [
            'total'    => (int) $allAccounts->count(),
            'active'   => (int) $allAccounts->where('is_active', true)->count(),
            'inactive' => (int) $allAccounts->where('is_active', false)->count(),
        ];
        $typeCounts = $allAccounts->groupBy('type')->map(fn($g) => $g->count());

        // Filtered query for the table
        $query = Account::where('company_id', $companyId);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('code')->get();

        // Optimized balance computation — single grouped query
        $lineTotals = \App\Models\JournalEntryLine::select(
                'account_id',
                \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(debit), 0) as total_debit'),
                \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(credit), 0) as total_credit')
            )
            ->whereHas('journalEntry', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereIn('status', [\App\Models\JournalEntry::STATUS_POSTED, \App\Models\JournalEntry::STATUS_REVERSED]);
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $balances = [];
        foreach ($accounts as $account) {
            $totals = $lineTotals->get($account->id);
            $debit = (float) ($totals->total_debit ?? 0);
            $credit = (float) ($totals->total_credit ?? 0);
            $balances[$account->id] = $account->isDebitNormal()
                ? $debit - $credit + (float) $account->opening_balance
                : $credit - $debit + (float) $account->opening_balance;
        }

        $topLevel = $accounts->whereNull('parent_id')->values();
        $grouped = $topLevel->groupBy('type');

        $typeLabels = [
            'asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity',
            'income' => 'Income', 'expense' => 'Expenses',
        ];

        return view('accounting.accounts.index', compact(
            'grouped', 'typeLabels', 'accounts', 'stats', 'typeCounts', 'balances'
        ));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $parentAccounts = Account::where('company_id', $companyId)
            ->active()
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();

        return view('accounting.accounts.create', compact('parentAccounts'));
    }

    public function store(StoreAccountRequest $request)
    {
        $companyId = session('current_company_id');
        $validated = $request->validated();

        if (!empty($validated['parent_id'])) {
            $parent = Account::findOrFail($validated['parent_id']);
            abort_unless(
                $parent->company_id == $companyId && $parent->type === $validated['type'],
                422
            );
        }

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;

        Account::create($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function show(Account $account)
    {
        $account->load('parent', 'children');

        return view('accounting.accounts.show', compact('account'));
    }

    public function edit(Account $account)
    {
        $companyId = session('current_company_id');

        $parentAccounts = Account::where('company_id', $companyId)
            ->active()
            ->whereNull('parent_id')
            ->where('id', '!=', $account->id)
            ->orderBy('code')
            ->get();

        return view('accounting.accounts.edit', compact('account', 'parentAccounts'));
    }

    public function update(UpdateAccountRequest $request, Account $account)
    {
        $companyId = session('current_company_id');
        $validated = $request->validated();

        if (!empty($validated['parent_id'])) {
            $parent = Account::findOrFail($validated['parent_id']);
            abort_unless(
                $parent->company_id == $companyId && $parent->type === $validated['type'],
                422
            );
        }

        if (array_key_exists('opening_balance', $validated) && $account->opening_balance_date) {
            $hasPostings = \App\Models\JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', fn ($q) => $q
                    ->where('status', 'posted')
                    ->where('date', '>=', $account->opening_balance_date)
                )
                ->exists();

            if ($hasPostings) {
                unset($validated['opening_balance']);
                $validated['opening_balance_date'] = null;
            }
        }

        $account->update($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function toggle(Account $account)
    {
        if ($account->is_active) {
            $hasBalance = $account->current_balance != 0;
            $hasActiveChildren = $account->children()->active()->exists();
            $hasJournalLines = \App\Models\JournalEntryLine::where('account_id', $account->id)->exists();

            if ($hasBalance || $hasActiveChildren || $hasJournalLines) {
                return redirect()->route('accounting.accounts.index')
                    ->with('error', 'Cannot deactivate account with balance, active children, or existing transactions.');
            }
        }

        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'activated' : 'deactivated';

        return redirect()->route('accounting.accounts.index')
            ->with('success', "Account {$status} successfully.");
    }
}
