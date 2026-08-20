<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoaController extends Controller
{
    private function companyId(): int
    {
        return (int) session('current_company_id');
    }

    private function allAccounts()
    {
        return Account::where('company_id', $this->companyId())->orderBy('code')->get();
    }

    private function computeBalances($accounts): array
    {
        $companyId = $this->companyId();
        $lineTotals = JournalEntryLine::select(
                'account_id',
                DB::raw('COALESCE(SUM(debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(credit), 0) as total_credit')
            )
            ->whereHas('journalEntry', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED]);
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
        return $balances;
    }

    // ─── Page 1: Dashboard ──────────────────────────────────────
    public function dashboard()
    {
        $companyId = $this->companyId();
        $accounts = $this->allAccounts();
        $balances = $this->computeBalances($accounts);

        $stats = [
            'total'    => $accounts->count(),
            'active'   => $accounts->where('is_active', true)->count(),
            'inactive' => $accounts->where('is_active', false)->count(),
            'groups'   => $accounts->where('is_group', true)->count(),
            'posting'  => $accounts->where('allow_posting', true)->count(),
        ];

        $typeCounts = $accounts->groupBy('type')->map(fn($g) => $g->count());

        $typeLabels = [
            'asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity',
            'income' => 'Income', 'expense' => 'Expenses',
        ];

        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;
        $totalIncome = 0;
        $totalExpenses = 0;
        foreach ($accounts as $account) {
            $bal = $balances[$account->id] ?? 0;
            switch ($account->type) {
                case 'asset': $totalAssets += $bal; break;
                case 'liability': $totalLiabilities += $bal; break;
                case 'equity': $totalEquity += $bal; break;
                case 'income': $totalIncome += $bal; break;
                case 'expense': $totalExpenses += $bal; break;
            }
        }

        $unmappedCount = 0;
        foreach (DefaultAccountMapping::availableKeys() as $key => $label) {
            if (!DefaultAccountMapping::getAccountId($companyId, $key)) {
                $unmappedCount++;
            }
        }

        $inactiveAccounts = $accounts->where('is_active', false);
        $unmappedAccounts = $accounts->filter(function ($a) use ($companyId) {
            $mappings = DefaultAccountMapping::where('company_id', $companyId)->pluck('account_id')->toArray();
            return !$a->is_active || in_array($a->id, $mappings);
        });

        return view('accounting.coa.dashboard', compact(
            'accounts', 'balances', 'stats', 'typeCounts', 'typeLabels',
            'totalAssets', 'totalLiabilities', 'totalEquity', 'totalIncome', 'totalExpenses',
            'inactiveAccounts', 'unmappedCount'
        ));
    }

    // ─── Page 2: Structure Setup ────────────────────────────────
    public function setup()
    {
        $companyId = $this->companyId();
        $company = Company::find($companyId);
        $accounts = $this->allAccounts();

        $accountTypes = ['asset', 'liability', 'equity', 'income', 'expense'];
        $subTypes = [
            'asset' => ['current_asset', 'fixed_asset', 'other_asset'],
            'liability' => ['current_liability', 'long_term_liability'],
            'equity' => ['equity'],
            'income' => ['revenue', 'other_income'],
            'expense' => ['cost_of_goods_sold', 'operating_expense', 'other_expense'],
        ];

        $typeCounts = $accounts->groupBy('type')->map(fn($g) => $g->count());
        $typeBalanceTotals = [];
        $balances = $this->computeBalances($accounts);
        foreach ($accounts as $account) {
            $type = $account->type;
            if (!isset($typeBalanceTotals[$type])) $typeBalanceTotals[$type] = 0;
            $typeBalanceTotals[$type] += $balances[$account->id] ?? 0;
        }

        $previewAccounts = $accounts->take(10);

        return view('accounting.coa.setup', compact(
            'company', 'accounts', 'accountTypes', 'subTypes', 'typeCounts', 'typeBalanceTotals', 'previewAccounts'
        ));
    }

    // ─── Page 3: Account List ───────────────────────────────────
    public function index(Request $request)
    {
        $companyId = $this->companyId();
        $allAccounts = $this->allAccounts();
        $balances = $this->computeBalances($allAccounts);

        $stats = [
            'total'    => $allAccounts->count(),
            'active'   => $allAccounts->where('is_active', true)->count(),
            'inactive' => $allAccounts->where('is_active', false)->count(),
        ];
        $typeCounts = $allAccounts->groupBy('type')->map(fn($g) => $g->count());

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
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $accounts = $query->orderBy('code')->get();
        $topLevel = $accounts->whereNull('parent_id')->values();
        $grouped = $topLevel->groupBy('type');

        $typeLabels = [
            'asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity',
            'income' => 'Income', 'expense' => 'Expenses',
        ];

        return view('accounting.coa.index', compact(
            'grouped', 'typeLabels', 'accounts', 'stats', 'typeCounts', 'balances'
        ));
    }

    // ─── Page 4: Create Account ─────────────────────────────────
    public function create()
    {
        $companyId = $this->companyId();
        $parentAccounts = Account::where('company_id', $companyId)
            ->active()
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();

        $accounts = Account::where('company_id', $companyId)->orderBy('code')->get();
        $balances = $this->computeBalances($accounts);

        return view('accounting.coa.create', compact('parentAccounts', 'accounts', 'balances'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyId();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:6', "unique:accounts,code,NULL,id,company_id,{$companyId}"],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:asset,liability,equity,income,expense'],
            'sub_type' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
            'currency' => ['required', 'string', 'max:10'],
            'normal_balance' => ['nullable', 'string', 'in:debit,credit'],
            'posting_behaviour' => ['nullable', 'string', 'in:both,debit_only,credit_only'],
            'is_group' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Account::findOrFail($validated['parent_id']);
            abort_unless(
                $parent->company_id == $companyId && $parent->type === $validated['type'],
                422
            );
        }

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;
        $validated['level'] = !empty($validated['parent_id']) ? 2 : 1;
        $validated['is_group'] = $validated['is_group'] ?? false;
        $validated['allow_posting'] = !$validated['is_group'];
        $validated['is_system_account'] = false;
        $validated['posting_behaviour'] = $validated['posting_behaviour'] ?? 'both';
        $validated['allow_adjustments'] = true;

        Account::create($validated);

        return redirect()->route('accounting.coa.index')
            ->with('success', 'Account created successfully.');
    }

    // ─── Page 5: Edit Account ───────────────────────────────────
    public function edit(Account $account)
    {
        $companyId = $this->companyId();
        abort_unless($account->company_id == $companyId, 404);

        $parentAccounts = Account::where('company_id', $companyId)
            ->active()
            ->whereNull('parent_id')
            ->where('id', '!=', $account->id)
            ->orderBy('code')
            ->get();

        $accounts = Account::where('company_id', $companyId)->orderBy('code')->get();
        $balances = $this->computeBalances($accounts);

        return view('accounting.coa.edit', compact('account', 'parentAccounts', 'accounts', 'balances'));
    }

    public function update(Request $request, Account $account)
    {
        $companyId = $this->companyId();
        abort_unless($account->company_id == $companyId, 404);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:6', "unique:accounts,code,{$account->id},id,company_id,{$companyId}"],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:asset,liability,equity,income,expense'],
            'sub_type' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
            'currency' => ['required', 'string', 'max:10'],
            'normal_balance' => ['nullable', 'string', 'in:debit,credit'],
            'posting_behaviour' => ['nullable', 'string', 'in:both,debit_only,credit_only'],
            'is_group' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Account::findOrFail($validated['parent_id']);
            abort_unless(
                $parent->company_id == $companyId && $parent->type === $validated['type'],
                422
            );
        }

        if (array_key_exists('opening_balance', $validated) && $account->opening_balance_date) {
            $hasPostings = JournalEntryLine::where('account_id', $account->id)
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

        $validated['level'] = !empty($validated['parent_id']) ? 2 : 1;
        $validated['is_group'] = $validated['is_group'] ?? false;
        $validated['allow_posting'] = !$validated['is_group'];

        $account->update($validated);

        return redirect()->route('accounting.coa.index')
            ->with('success', 'Account updated successfully.');
    }

    public function toggle(Account $account)
    {
        $companyId = $this->companyId();
        abort_unless($account->company_id == $companyId, 404);

        if ($account->is_active) {
            $hasBalance = $account->current_balance != 0;
            $hasActiveChildren = $account->children()->active()->exists();
            $hasJournalLines = JournalEntryLine::where('account_id', $account->id)->exists();

            if ($hasBalance || $hasActiveChildren || $hasJournalLines) {
                return redirect()->route('accounting.coa.index')
                    ->with('error', 'Cannot deactivate account with balance, active children, or existing transactions.');
            }
        }

        $account->update(['is_active' => !$account->is_active]);
        $status = $account->is_active ? 'activated' : 'deactivated';

        return redirect()->route('accounting.coa.index')
            ->with('success', "Account {$status} successfully.");
    }

    // ─── Page 6: Tree + Validation ──────────────────────────────
    public function tree()
    {
        $companyId = $this->companyId();
        $accounts = $this->allAccounts();
        $balances = $this->computeBalances($accounts);

        $tree = $accounts->where('parent_id', null)->sortBy('code')->values();
        $typeLabels = [
            'asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity',
            'income' => 'Income', 'expense' => 'Expenses',
        ];

        $issues = [];
        foreach ($accounts as $account) {
            if (!$account->is_group && $account->allow_posting) {
                $childCount = $accounts->where('parent_id', $account->id)->count();
                if ($childCount > 0) {
                    $issues[] = ['type' => 'warning', 'account' => $account, 'message' => 'Posting account has child accounts.'];
                }
            }
            if ($account->is_group && $account->allow_posting) {
                $issues[] = ['type' => 'error', 'account' => $account, 'message' => 'Group account should not allow posting.'];
            }
            if (!$account->is_active && $accounts->where('parent_id', $account->id)->where('is_active', true)->count() > 0) {
                $issues[] = ['type' => 'error', 'account' => $account, 'message' => 'Inactive account has active children.'];
            }
        }

        return view('accounting.coa.tree', compact('tree', 'accounts', 'balances', 'typeLabels', 'issues'));
    }

    // ─── Page 7: Mapping + Linkages ─────────────────────────────
    public function mapping()
    {
        $companyId = $this->companyId();
        $accounts = Account::where('company_id', $companyId)->active()->orderBy('code')->get();
        $mappings = DefaultAccountMapping::where('company_id', $companyId)->get()->keyBy('mapping_key');
        $availableKeys = DefaultAccountMapping::availableKeys();

        return view('accounting.coa.mapping', compact('accounts', 'mappings', 'availableKeys'));
    }

    public function mappingStore(Request $request)
    {
        $companyId = $this->companyId();
        $validated = $request->validate([
            'mappings' => ['required', 'array'],
            'mappings.*' => ['nullable', 'integer'],
        ]);

        foreach ($validated['mappings'] as $key => $accountId) {
            if ($accountId) {
                DefaultAccountMapping::setMapping($companyId, $key, (int) $accountId);
            } else {
                DefaultAccountMapping::where('company_id', $companyId)
                    ->where('mapping_key', $key)
                    ->delete();
            }
        }

        return redirect()->route('accounting.coa.mapping')
            ->with('success', 'Account mappings updated.');
    }

    // ─── Page 8: Opening Balances ───────────────────────────────
    public function opening()
    {
        $companyId = $this->companyId();
        $accounts = Account::where('company_id', $companyId)
            ->where('allow_posting', true)
            ->active()
            ->orderBy('code')
            ->get();
        $balances = $this->computeBalances($accounts);

        $typeLabels = [
            'asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity',
            'income' => 'Income', 'expense' => 'Expenses',
        ];

        $grandTotal = $accounts->sum(fn($a) => abs($balances[$a->id] ?? 0));

        return view('accounting.coa.opening', compact('accounts', 'balances', 'typeLabels', 'grandTotal'));
    }

    public function openingUpdate(Request $request)
    {
        $companyId = $this->companyId();

        $validated = $request->validate([
            'opening_balances' => ['required', 'array'],
            'opening_balances.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'opening_balances.*.amount' => ['required', 'numeric'],
        ]);

        foreach ($validated['opening_balances'] as $item) {
            $account = Account::where('company_id', $companyId)->findOrFail($item['account_id']);
            $account->update([
                'opening_balance' => $item['amount'],
                'opening_balance_date' => $request->input('opening_balance_date'),
            ]);
        }

        return redirect()->route('accounting.coa.opening')
            ->with('success', 'Opening balances updated.');
    }

    // ─── Page 9: Account Budgets + Journals ─────────────────────
    public function budgets()
    {
        $companyId = $this->companyId();
        $accounts = Account::where('company_id', $companyId)
            ->where('allow_posting', true)
            ->active()
            ->orderBy('code')
            ->get();
        $balances = $this->computeBalances($accounts);

        $recentJournals = JournalEntry::where('company_id', $companyId)
            ->with('journalEntryLines.account')
            ->latest('date')
            ->limit(20)
            ->get();

        $typeLabels = [
            'asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity',
            'income' => 'Income', 'expense' => 'Expenses',
        ];

        return view('accounting.coa.budgets', compact('accounts', 'balances', 'recentJournals', 'typeLabels'));
    }

    // ─── Page 10: Reports + Import/Export + Audit + Settings ────
    public function reports()
    {
        $companyId = $this->companyId();
        $accounts = $this->allAccounts();
        $stats = [
            'total' => $accounts->count(),
            'active' => $accounts->where('is_active', true)->count(),
            'inactive' => $accounts->where('is_active', false)->count(),
        ];

        return view('accounting.coa.reports', compact('accounts', 'stats'));
    }

    public function exportCsv()
    {
        $accounts = $this->allAccounts();
        $balances = $this->computeBalances($accounts);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="chart-of-accounts.csv"',
        ];

        $callback = function () use ($accounts, $balances) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code', 'Name', 'Type', 'Sub Type', 'Balance', 'Status']);
            foreach ($accounts as $account) {
                fputcsv($handle, [
                    $account->code,
                    $account->name,
                    $account->type,
                    $account->sub_type,
                    $balances[$account->id] ?? 0,
                    $account->is_active ? 'Active' : 'Inactive',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
