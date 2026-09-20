<?php

namespace App\Services\Accounting;

/**
 * Maps a journal entry's `source_module` (plus the adjusting flag) onto the
 * coarse "Type" buckets shown in the Journals register: General, Payments,
 * Payroll, Depreciation, Inventory and Reversal.
 *
 * Purely presentational — no persistence, no ledger impact. The same buckets
 * drive both the register's Type column and its Type filter so the two can
 * never disagree.
 */
class JournalTypeClassifier
{
    public const GENERAL = 'General';
    public const PAYMENTS = 'Payments';
    public const PAYROLL = 'Payroll';
    public const DEPRECIATION = 'Depreciation';
    public const INVENTORY = 'Inventory';
    public const REVERSAL = 'Reversal';

    /**
     * The Type filter's options, in display order.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::GENERAL,
            self::PAYMENTS,
            self::PAYROLL,
            self::DEPRECIATION,
            self::INVENTORY,
            self::REVERSAL,
        ];
    }

    /**
     * The source_module values that belong to each non-general bucket.
     *
     * @return array<string, array<int, string>>
     */
    private static function groups(): array
    {
        return [
            self::REVERSAL => ['reversal'],
            self::PAYROLL => ['payroll'],
            self::DEPRECIATION => ['fixed_assets'],
            self::INVENTORY => ['stock_count', 'assembly_build', 'assembly_unbuild', 'landed_cost', 'grn'],
            self::PAYMENTS => [
                'customer_payment',
                'vendor_payment',
                'expense_payment',
                'sales_receipt',
                'pos',
                'bill',
                'invoice',
                'cheque',
                'cheque_void',
                'make_deposit',
                'petty_cash_establish',
                'petty_cash_expense',
                'petty_cash_replenish',
                'bank_transfer',
                'bank_manual',
                'bank_reconciliation',
                'credit_note',
                'vendor_credit',
                'expense',
            ],
        ];
    }

    /**
     * Resolve the coarse Type bucket for a single entry.
     */
    public static function label(?string $sourceModule, bool $isAdjusting = false): string
    {
        foreach (self::groups() as $label => $modules) {
            if ($sourceModule !== null && in_array($sourceModule, $modules, true)) {
                return $label;
            }
        }

        return self::GENERAL;
    }

    /**
     * The source_module values a Type filter should match. A null return means
     * "the complement" — the caller should use whereNotIn against every known
     * module instead (used for General).
     *
     * @return array<int, string>|null
     */
    public static function modulesFor(string $label): ?array
    {
        if ($label === self::GENERAL) {
            return null;
        }

        return self::groups()[$label] ?? null;
    }

    /**
     * Every source_module owned by a specific bucket (used for the General
     * complement). Excludes General itself.
     *
     * @return array<int, string>
     */
    public static function allGroupedModules(): array
    {
        return array_merge(...array_values(self::groups()));
    }

    public static function isValid(string $label): bool
    {
        return in_array($label, self::options(), true);
    }
}
