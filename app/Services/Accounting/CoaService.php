<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\CoaAuditLog;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoaService
{
    private int $companyId;

    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId ?? (int) session('current_company_id');
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    public function allAccounts()
    {
        return Account::where('company_id', $this->companyId)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    public function computeBalances($accounts): array
    {
        $companyId = $this->companyId;

        $lineTotals = JournalEntryLine::select(
                'account_id',
                DB::raw('COALESCE(SUM(debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(credit), 0) as total_credit')
            )
            ->whereHas('journalEntry', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereIn('status', [
                      JournalEntry::STATUS_POSTED,
                      JournalEntry::STATUS_REVERSED,
                  ]);
            })
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $balances = [];
        foreach ($accounts as $account) {
            $totals = $lineTotals->get($account->id);
            $debit = (float) ($totals->total_debit ?? 0);
            $credit = (float) ($totals->total_credit ?? 0);

            $rawBalance = $account->isDebitNormal()
                ? $debit - $credit
                : $credit - $debit;

            $balances[$account->id] = [
                'opening' => (float) $account->opening_balance,
                'current' => (float) number_format($rawBalance + (float) $account->opening_balance, 2, '.', ''),
            ];
        }
        return $balances;
    }

    public function buildTree(): array
    {
        $accounts = $this->allAccounts();
        $balances = $this->computeBalances($accounts);
        $systemCurrency = Company::find($this->companyId)?->base_currency ?? 'MWK';

        $typeOrder = ['asset', 'liability', 'equity', 'income', 'expense'];
        $typeLabels = [
            'asset' => 'Assets',
            'liability' => 'Liabilities',
            'equity' => 'Equity',
            'income' => 'Revenue',
            'expense' => 'Expenses',
        ];

        $byType = $accounts->groupBy('type');
        $tree = [];

        foreach ($typeOrder as $type) {
            if (!$byType->has($type)) continue;
            $typeAccounts = $byType->get($type);
            $bySubType = $typeAccounts->groupBy('sub_type');
            $subTypeNodes = [];

            foreach ($bySubType as $subType => $subAccounts) {
                $parentAccounts = $subAccounts->whereNull('parent_id');
                $subAccountNodes = [];
                foreach ($parentAccounts as $account) {
                    $subAccountNodes[] = $this->buildAccountNode($account, $balances);
                }
                $subTypeNodes[] = [
                    'label' => self::humanizeSubType($subType),
                    'accounts' => $subAccountNodes,
                ];
            }

            $typeTotal = 0;
            foreach ($typeAccounts as $a) {
                $typeTotal += $balances[$a->id]['current'] ?? 0;
            }

            $tree[] = [
                'type' => $type,
                'label' => $typeLabels[$type] ?? ucfirst($type),
                'sub_types' => $subTypeNodes,
                'total' => $typeTotal,
            ];
        }

        return [
            'tree' => $tree,
            'balances' => $balances,
            'system_currency' => $systemCurrency,
            'stats' => [
                'total' => $accounts->count(),
                'active' => $accounts->where('is_active', true)->count(),
                'inactive' => $accounts->where('is_active', false)->count(),
                'types' => $byType->count(),
            ],
        ];
    }

    private function buildAccountNode(Account $account, array $balances): array
    {
        $children = Account::where('company_id', $this->companyId)
            ->where('parent_id', $account->id)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        $hasChildren = $children->isNotEmpty();
        $isGroup = $account->is_group || $hasChildren;

        $childNodes = [];
        foreach ($children as $child) {
            $childNodes[] = $this->buildAccountNode($child, $balances);
        }

        $bal = $balances[$account->id] ?? ['opening' => 0, 'current' => 0];

        $status = 'active';
        if (!$account->is_active) {
            $status = 'inactive';
        } elseif ($account->isControlled()) {
            $status = 'controlled';
        }

        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'sub_type' => $account->sub_type,
            'description' => $account->description,
            'status' => $status,
            'is_group' => $isGroup,
            'is_contra' => $account->isContra(),
            'is_controlled' => $account->isControlled(),
            'is_system_account' => $account->is_system_account,
            'allow_posting' => $account->allow_posting,
            'opening' => $bal['opening'],
            'current' => $bal['current'],
            'children' => $childNodes,
        ];
    }

    public function computeEquation(): array
    {
        $accounts = $this->allAccounts();
        $balances = $this->computeBalances($accounts);

        $totals = ['asset' => 0, 'liability' => 0, 'equity' => 0, 'income' => 0, 'expense' => 0];
        foreach ($accounts as $account) {
            $totals[$account->type] += $balances[$account->id]['current'] ?? 0;
        }

        $assets = $totals['asset'];
        $liabilitiesPlusEquity = $totals['liability'] + $totals['equity'];
        $balanced = abs($assets - $liabilitiesPlusEquity) < 0.01;

        return [
            'assets' => $assets,
            'liabilities' => $totals['liability'],
            'equity' => $totals['equity'],
            'income' => $totals['income'],
            'expense' => $totals['expense'],
            'balanced' => $balanced,
            'difference' => $assets - $liabilitiesPlusEquity,
        ];
    }

    public function getViewPreference(): string
    {
        $userId = Auth::id();
        if (!$userId) return 'tree';
        $pref = UserPreference::where('user_id', $userId)->first();
        return $pref->coa_view ?? 'tree';
    }

    public function setViewPreference(string $view): void
    {
        $userId = Auth::id();
        if (!$userId) return;
        UserPreference::updateOrCreate(
            ['user_id' => $userId],
            ['coa_view' => $view]
        );
    }

    public function deactivateAccount(Account $account, string $reason): Account
    {
        $this->validateDeactivation($account);
        $old = ['is_active' => true, 'status' => 'active'];

        $account->update([
            'is_active' => false,
            'version' => $account->version + 1,
        ]);

        CoaAuditLog::log(
            $this->companyId,
            $account->id,
            CoaAuditLog::ACTION_DEACTIVATED,
            $old,
            ['is_active' => false, 'status' => 'inactive'],
            $reason
        );

        return $account->fresh();
    }

    public function reactivateAccount(Account $account): Account
    {
        $old = ['is_active' => false, 'status' => 'inactive'];

        $account->update([
            'is_active' => true,
            'version' => $account->version + 1,
        ]);

        CoaAuditLog::log(
            $this->companyId,
            $account->id,
            CoaAuditLog::ACTION_REACTIVATED,
            $old,
            ['is_active' => true, 'status' => 'active']
        );

        return $account->fresh();
    }

    private function validateDeactivation(Account $account): void
    {
        if ($account->isControlled()) {
            abort(422, 'This is a controlled system account and cannot be deactivated.');
        }

        $balances = $this->computeBalances(collect([$account]));
        $bal = $balances[$account->id]['current'] ?? 0;
        if (abs($bal) > 0.001) {
            abort(422, 'Cannot deactivate an account with a non-zero balance. Current balance: ' . number_format($bal, 2));
        }

        if ($account->children()->active()->exists()) {
            abort(422, 'Cannot deactivate an account that has active sub-accounts. Deactivate or reassign children first.');
        }

        $hasLines = JournalEntryLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) {
                $q->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED]);
            })
            ->exists();

        if ($hasLines) {
            abort(422, 'Cannot deactivate an account with posted journal entries.');
        }
    }

    public function createAccount(array $data): Account
    {
        $data['company_id'] = $this->companyId;
        $data['is_active'] = true;
        $data['is_system_account'] = $data['is_system_account'] ?? false;
        $data['is_contra'] = $data['is_contra'] ?? false;
        $data['version'] = 1;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if (!empty($data['parent_id'])) {
            $parent = Account::findOrFail($data['parent_id']);
            abort_unless(
                $parent->company_id == $this->companyId && $parent->type === $data['type'],
                422, 'Parent account must be the same type.'
            );
            $data['level'] = ($parent->level ?? 1) + 1;
        } else {
            $data['level'] = 1;
        }

        $data['is_group'] = $data['is_group'] ?? false;
        $data['allow_posting'] = !$data['is_group'];
        $data['posting_behaviour'] = $data['posting_behaviour'] ?? 'both';
        $data['allow_adjustments'] = $data['allow_adjustments'] ?? true;

        $account = Account::create($data);

        CoaAuditLog::log(
            $this->companyId,
            $account->id,
            CoaAuditLog::ACTION_CREATED,
            null,
            $account->toArray()
        );

        return $account;
    }

    public function updateAccount(Account $account, array $data): Account
    {
        $old = $account->toArray();

        $data['version'] = $account->version + 1;

        if (isset($data['parent_id']) && $data['parent_id'] !== $account->parent_id) {
            if (!empty($data['parent_id'])) {
                $parent = Account::findOrFail($data['parent_id']);
                abort_unless(
                    $parent->company_id == $this->companyId && $parent->type === $account->type,
                    422, 'Parent account must be the same type.'
                );
                $data['level'] = ($parent->level ?? 1) + 1;
            } else {
                $data['level'] = 1;
            }
        }

        $account->update($data);

        CoaAuditLog::log(
            $this->companyId,
            $account->id,
            CoaAuditLog::ACTION_UPDATED,
            $old,
            $account->fresh()->toArray()
        );

        return $account->fresh();
    }

    public static function humanizeSubType(?string $subType): string
    {
        if (!$subType) return 'Other';
        return ucwords(str_replace('_', ' ', $subType));
    }
}