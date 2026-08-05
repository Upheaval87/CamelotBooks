<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefaultAccountMapping extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'mapping_key',
        'account_id',
    ];

    protected $cache = [];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the account ID for a mapping key. Returns null if not mapped.
     */
    public static function getAccountId(int $companyId, string $mappingKey): ?int
    {
        $mapping = static::where('company_id', $companyId)
            ->where('mapping_key', $mappingKey)
            ->first();

        return $mapping?->account_id;
    }

    /**
     * Get the full Account model for a mapping key. Returns null if not mapped.
     */
    public static function getAccount(int $companyId, string $mappingKey): ?Account
    {
        return Account::where('company_id', $companyId)
            ->where('id', static::getAccountId($companyId, $mappingKey))
            ->first();
    }

    /**
     * Set a mapping. Creates or updates.
     */
    public static function setMapping(int $companyId, string $mappingKey, int $accountId): void
    {
        static::updateOrCreate(
            ['company_id' => $companyId, 'mapping_key' => $mappingKey],
            ['account_id' => $accountId]
        );
    }

    /**
     * Get all mappings for a company as [mapping_key => account_id].
     */
    public static function getAll(int $companyId): array
    {
        return static::where('company_id', $companyId)
            ->pluck('account_id', 'mapping_key')
            ->toArray();
    }

    /**
     * All available mapping keys with human-readable labels.
     */
    public static function availableKeys(): array
    {
        return [
            'accounts_receivable' => 'Accounts Receivable',
            'accounts_payable' => 'Accounts Payable',
            'undeposited_funds' => 'Undeposited Funds',
            'default_bank' => 'Default Bank / Cash',
            'cash_in_drawer' => 'Cash in Drawer',
            'inventory_asset' => 'Inventory Asset',
            'default_revenue' => 'Default Revenue',
            'default_expense' => 'Default COGS / Expense',
            'tax_payable' => 'Tax Payable (Output Tax)',
            'tax_receivable' => 'Tax Receivable (Input Tax)',
            'accrued_purchases' => 'Accrued Purchases (GRN Clearing)',
            'purchase_price_variance' => 'Purchase Price Variance',
            'inventory_count_variance' => 'Inventory Count Variance',
            'retained_earnings' => 'Retained Earnings',
            'current_year_earnings' => 'Current Year Earnings',
            'revaluation_surplus' => 'Revaluation Surplus',
            'suspense' => 'Suspense Account',
            'rounding' => 'Rounding Differences',
            'realized_fx_gain_loss' => 'Realized FX Gain/Loss',
            'unrealized_fx_gain_loss' => 'Unrealized FX Gain/Loss',
            'cash_shortage' => 'Cash Shortage',
            'cash_overage' => 'Cash Overage',
            'merchant_fee_expense' => 'Merchant Fee Expense',
            'disposal_gain_loss' => 'Gain/Loss on Disposal',
            'salary_expense' => 'Salary Expense',
            'pension_expense' => 'Pension Expense',
            'depreciation_expense' => 'Depreciation Expense',
            'impairment_loss' => 'Impairment Loss',
            'inventory_adjustment' => 'Inventory Adjustment Expense',
            'paye_payable' => 'PAYE Payable',
            'pension_payable' => 'Pension Payable',
            'net_pay_payable' => 'Net Pay Payable',
            'petty_cash' => 'Petty Cash',
        ];
    }

    /**
     * Default mapping keys to account code seeds (used during company creation).
     */
    public static function defaultCodes(): array
    {
        return [
            'accounts_receivable' => '1100',
            'accounts_payable' => '2000',
            'undeposited_funds' => '1050',
            'default_bank' => '1000',
            'cash_in_drawer' => '1060',
            'inventory_asset' => '1200',
            'default_revenue' => '4000',
            'default_expense' => '5000',
            'tax_payable' => '2300',
            'tax_receivable' => '1150',
            'accrued_purchases' => '2150',
            'purchase_price_variance' => '6800',
            'inventory_count_variance' => '6850',
            'retained_earnings' => '3100',
            'current_year_earnings' => '3200',
            'revaluation_surplus' => '3300',
            'suspense' => null,
            'rounding' => '9999',
            'realized_fx_gain_loss' => '7200',
            'unrealized_fx_gain_loss' => '7300',
            'cash_shortage' => '6900',
            'cash_overage' => '7400',
            'merchant_fee_expense' => '6950',
            'disposal_gain_loss' => null,
            'salary_expense' => '6000',
            'pension_expense' => '6010',
            'depreciation_expense' => '6400',
            'impairment_loss' => '6500',
            'inventory_adjustment' => '6700',
            'paye_payable' => '2400',
            'pension_payable' => '2410',
            'net_pay_payable' => '2420',
            'petty_cash' => '1010',
        ];
    }
}
