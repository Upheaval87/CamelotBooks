<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\MethodConversion;
use App\Services\FeatureManagement;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Cash → accrual accounting-method conversion (spec §5).
 *
 * A dated, journaled conversion — never a free toggle. The conversion journal
 * is posted through the EXISTING JournalPostingEngine (never reimplemented),
 * then the company flag, period basis flags, AR/AP/inventory activation and
 * the audit trail are written. The flow is one-way: activation is irreversible.
 */
class MethodConversionService
{
    public const TREATMENT_PROSPECTIVE = 'prospective';
    public const TREATMENT_RETROSPECTIVE = 'retrospective';

    /**
     * The conversion-target account codes (default COA, DefaultChartOfAccounts).
     * Keys are the opening-balance slugs used in the UI and the store payload.
     */
    private const TARGET_CODES = [
        'ar'  => '1100', // Accounts Receivable
        'inv' => '1200', // Inventory
        'pre' => '1300', // Prepaid Expenses
        'ap'  => '2000', // Accounts Payable
        'acc' => '2100', // Accrued Expenses
        'une' => '2200', // Unearned Revenue
        're'  => '3100', // Retained Earnings
    ];

    /**
     * Save a conversion draft (spec §5 step 4, "Save draft").
     */
    public function saveDraft(
        int $companyId,
        string $cutOffDate,
        string $treatment,
        array $openingBalances,
        int $userId
    ): MethodConversion {
        $this->assertCompanyIsCash($companyId);
        $this->validateCutOff($companyId, $cutOffDate);

        $conversion = MethodConversion::query()
            ->forCompany($companyId)
            ->active()
            ->first();

        $data = [
            'company_id' => $companyId,
            'from_method' => Company::METHOD_CASH,
            'to_method' => Company::METHOD_ACCRUAL,
            'cut_off_date' => $cutOffDate,
            'treatment' => $treatment,
            'status' => MethodConversion::STATUS_DRAFT,
            'created_by' => $userId,
        ];

        if ($conversion) {
            $conversion->update($data);
            return $conversion->fresh();
        }

        return MethodConversion::create($data);
    }

    /**
     * Persist the opening balances on an existing draft (so the journal is
     * reproducible). Balances live in `new_values`/audit only — there is no
     * dedicated column, per the Phase-1 schema.
     */
    public function persistOpeningBalances(int $conversionId, array $openingBalances): void
    {
        $conversion = MethodConversion::findOrFail($conversionId);
        $conversion->update([
            'from_method' => Company::METHOD_CASH,
            'to_method' => Company::METHOD_ACCRUAL,
        ]);
        AuditLog::log(
            $conversion->company_id,
            $conversion->created_by,
            MethodConversion::class,
            $conversion->id,
            'conversion_opening_balances',
            null,
            ['opening_balances' => $this->normalizeBalances($openingBalances)],
            'Opening balances captured for cash→accrual conversion'
        );
    }

    /**
     * Activate the conversion (spec §5 step 4, admin-only, atomic):
     *  1. posts the conversion journal via JournalPostingEngine,
     *  2. activates AR/AP/inventory (accounts + inventory feature),
     *  3. flags periods before cut-off 'cash', from the cut-off 'accrual',
     *  4. sets companies.accounting_method = 'accrual',
     *  5. writes the method_conversions row status='activated' + journal id,
     *  6. writes an audit-trail entry.
     */
    public function activate(
        int $companyId,
        string $cutOffDate,
        string $treatment,
        array $openingBalances,
        int $userId
    ): MethodConversion {
        $this->assertCompanyIsCash($companyId);
        $this->validateCutOff($companyId, $cutOffDate);

        $balances = $this->normalizeBalances($openingBalances);

        if (array_sum($balances) === 0.0) {
            throw new InvalidArgumentException('Enter at least one opening balance before activating.');
        }

        $accounts = $this->resolveTargetAccounts($companyId);

        return \DB::transaction(function () use (
            $companyId,
            $cutOffDate,
            $treatment,
            $balances,
            $userId,
            $accounts
        ) {
            $journal = $this->postConversionJournal($companyId, $cutOffDate, $balances, $accounts, $userId);

            $this->activateAccounts($companyId, $accounts);
            FeatureManagement::enable($companyId, 'inventory', $userId);

            $this->flagPeriodBases($companyId, $cutOffDate);

            $company = Company::findOrFail($companyId);
            $company->accounting_method = Company::METHOD_ACCRUAL;
            $company->save();

            $conversion = MethodConversion::query()
                ->forCompany($companyId)
                ->active()
                ->first();

            $data = [
                'company_id' => $companyId,
                'from_method' => Company::METHOD_CASH,
                'to_method' => Company::METHOD_ACCRUAL,
                'cut_off_date' => $cutOffDate,
                'treatment' => $treatment,
                'conversion_journal_id' => $journal->id,
                'status' => MethodConversion::STATUS_ACTIVATED,
                'created_by' => $userId,
                'activated_at' => now(),
                'activated_by' => $userId,
            ];

            if ($conversion) {
                $conversion->update($data);
                $conversion = $conversion->fresh();
            } else {
                $conversion = MethodConversion::create($data);
            }

            AuditLog::log(
                $companyId,
                $userId,
                MethodConversion::class,
                $conversion->id,
                'accounting_method_switched',
                ['accounting_method' => Company::METHOD_CASH],
                [
                    'accounting_method' => Company::METHOD_ACCRUAL,
                    'cut_off_date' => $cutOffDate,
                    'treatment' => $treatment,
                    'conversion_journal_id' => $journal->id,
                    'opening_balances' => $balances,
                ],
                'Company switched from cash to accrual basis'
            );

            return $conversion;
        });
    }

    /**
     * Build the spec §5 conversion-journal lines (Dr AR/Inv/Pre, Cr AP/Acc/Une,
     * plug to Retained Earnings) and POST them through JournalPostingEngine.
     */
    private function postConversionJournal(
        int $companyId,
        string $cutOffDate,
        array $balances,
        array $accounts,
        int $userId
    ): JournalEntry {
        $debitSide = $balances['ar'] + $balances['inv'] + $balances['pre'];
        $creditSide = $balances['ap'] + $balances['acc'] + $balances['une'];
        $plug = $debitSide - $creditSide;

        $lines = [
            $this->line($accounts['ar'], debit: $balances['ar'], memo: 'Accounts Receivable — earned, not collected'),
            $this->line($accounts['inv'], debit: $balances['inv'], memo: 'Inventory on hand'),
            $this->line($accounts['pre'], debit: $balances['pre'], memo: 'Prepayments — paid, not yet used'),
            $this->line($accounts['ap'], credit: $balances['ap'], memo: 'Accounts Payable — incurred, not paid'),
            $this->line($accounts['acc'], credit: $balances['acc'], memo: 'Accrued expenses'),
            $this->line($accounts['une'], credit: $balances['une'], memo: 'Unearned revenue — received, not earned'),
        ];

        if ($plug >= 0) {
            $lines[] = $this->line($accounts['re'], credit: $plug, memo: 'Retained Earnings — opening adjustment (credit)');
        } elseif ($plug < 0) {
            $lines[] = $this->line($accounts['re'], debit: abs($plug), memo: 'Retained Earnings — opening adjustment (debit)');
        }

        $lines = array_values(array_filter($lines));

        return app(JournalPostingEngine::class)->post([
            'company_id' => $companyId,
            'date' => $cutOffDate,
            'memo' => 'Cash to accrual conversion — opening adjustments at cut-off',
            'reference' => 'ACC-CONV-' . $cutOffDate,
            'is_adjusting_entry' => true,
            'source_module' => 'method_conversion',
            'created_by' => $userId,
            'skip_inactive_account_check' => true,
            'lines' => $lines,
        ]);
    }

    private function line(int $accountId, float $debit = 0, float $credit = 0, string $memo = ''): ?array
    {
        if ($debit === 0.0 && $credit === 0.0) {
            return null;
        }
        return [
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
            'memo' => $memo,
        ];
    }

    private function resolveTargetAccounts(int $companyId): array
    {
        $accounts = [];
        foreach (self::TARGET_CODES as $slug => $code) {
            $account = Account::forCompany($companyId)->where('code', $code)->first();
            $accounts[$slug] = $account?->id;
        }

        if (in_array(null, $accounts, true)) {
            $missing = array_keys(array_filter($accounts, fn ($v) => $v === null));
            throw new InvalidArgumentException(
                'Cannot build conversion journal — accounts not found: ' . implode(', ', $missing) . '. Run COA setup first.'
            );
        }

        return $accounts;
    }

    private function activateAccounts(int $companyId, array $accounts): void
    {
        $ids = [$accounts['ar'], $accounts['inv'], $accounts['pre'], $accounts['ap'], $accounts['acc'], $accounts['une']];
        Account::forCompany($companyId)
            ->whereIn('id', $ids)
            ->where('is_active', false)
            ->update(['is_active' => true]);
    }

    /**
     * Flag periods strictly before the cut-off as 'cash' and periods on/after
     * as 'accrual' (spec §5 step 4 item 3).
     */
    private function flagPeriodBases(int $companyId, string $cutOffDate): void
    {
        AccountingPeriod::forCompany($companyId)
            ->where('end_date', '<', $cutOffDate)
            ->update(['basis' => 'cash']);

        AccountingPeriod::forCompany($companyId)
            ->where('end_date', '>=', $cutOffDate)
            ->update(['basis' => 'accrual']);
    }

    private function assertCompanyIsCash(int $companyId): void
    {
        $company = Company::findOrFail($companyId);
        if (!$company->isCashBasis()) {
            throw new InvalidArgumentException('This company is already on the accrual basis.');
        }
    }

    /**
     * Cut-off validation (spec §5 step 1):
     *  - cut-off ≥ last day of the last posted period,
     *  - cut-off ≥ company start date,
     *  - no posted transactions on/after the cut-off.
     */
    private function validateCutOff(int $companyId, string $cutOffDate): void
    {
        $cutOff = Carbon::parse($cutOffDate)->startOfDay();

        $company = Company::findOrFail($companyId);

        $lastPostedPeriod = AccountingPeriod::forCompany($companyId)
            ->where('status', '<>', 'open')
            ->orderByDesc('end_date')
            ->first();

        if ($lastPostedPeriod && $cutOff->lt($lastPostedPeriod->end_date->copy()->startOfDay())) {
            throw new InvalidArgumentException(
                'Cut-off must be on or after the last day of the last posted period (' .
                $lastPostedPeriod->end_date->format('Y-m-d') . ")."
            );
        }

        $postedOnOrAfter = JournalEntryLine::whereHas('journalEntry', function ($q) use ($companyId, $cutOff) {
            $q->where('company_id', $companyId)
                ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                ->where('date', '>=', $cutOff->toDateString());
        })->exists();

        if ($postedOnOrAfter) {
            throw new InvalidArgumentException(
                'Posted transactions exist on or after the chosen cut-off date. Choose a later cut-off or move those transactions first.'
            );
        }
    }

    private function normalizeBalances(array $balances): array
    {
        $keys = ['ar', 'inv', 'pre', 'ap', 'acc', 'une'];
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = (float) ($balances[$key] ?? 0);
            if ($result[$key] < 0) {
                throw new InvalidArgumentException('Opening balances cannot be negative.');
            }
        }
        return $result;
    }
}
