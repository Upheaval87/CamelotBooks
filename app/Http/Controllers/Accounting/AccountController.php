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

        $query = Account::where('company_id', $companyId)->with('parent');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('code')->get();

        $topLevel = $accounts->whereNull('parent_id')->values();

        $grouped = $topLevel->groupBy('type');

        $typeLabels = [
            'asset' => 'Assets',
            'liability' => 'Liabilities',
            'equity' => 'Equity',
            'income' => 'Income',
            'expense' => 'Expenses',
        ];

        return view('accounting.accounts.index', compact('grouped', 'typeLabels', 'accounts'));
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
        $validated['current_balance'] = $validated['opening_balance'] ?? 0;
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

        $account->update($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function toggle(Account $account)
    {
        if (!$account->is_active) {
            $hasBalance = $account->current_balance != 0;
            $hasActiveChildren = $account->children()->active()->exists();

            if ($hasBalance || $hasActiveChildren) {
                return redirect()->route('accounting.accounts.index')
                    ->with('error', 'Cannot activate account with balance or active children.');
            }
        }

        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'activated' : 'deactivated';

        return redirect()->route('accounting.accounts.index')
            ->with('success', "Account {$status} successfully.");
    }
}
