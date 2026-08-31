<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROVISIONING = 'provisioning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_FAILED = 'failed';

    public const METHOD_ACCRUAL = 'accrual';
    public const METHOD_CASH = 'cash';

    public const METHOD_OPTIONS = [self::METHOD_ACCRUAL, self::METHOD_CASH];

    public const REPORTING_ACCRUAL_VIEW = 'accrual_view';
    public const REPORTING_CASH_VIEW = 'cash_view';

    protected $fillable = [
        'name',
        'legal_name',
        'company_code',
        'tax_id',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'base_currency',
        'accounting_method',
        'reporting_preference',
        'fiscal_year_start_month',
        'branch_limit',
        'branch_count',
        'logo',
        'website',
        'is_active',
        'allow_negative_stock',
        'provisioning_status',
        'db_name',
        'db_host',
        'db_port',
        'db_username',
        'db_password',
        'provisioned_at',
        'last_provisioning_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'fiscal_year_start_month' => 'integer',
        'branch_limit' => 'integer',
        'branch_count' => 'integer',
        'accounting_method' => 'string',
        'reporting_preference' => 'string',
        'provisioning_status' => 'string',
        'db_port' => 'integer',
        'db_password' => 'encrypted',
        'provisioned_at' => 'datetime',
    ];

    /**
     * Sensible defaults so a company always has an accounting method even when
     * a DB column default does not apply (e.g. the sqlite test schema).
     */
    protected $attributes = [
        'accounting_method' => self::METHOD_ACCRUAL,
        'reporting_preference' => self::REPORTING_ACCRUAL_VIEW,
    ];

    /**
     * The tenant DB credentials never leave the model (JSON/array serialization,
     * log context, API payloads). Decryption happens lazily in the attribute
     * cast, used only by the connection resolver / backup service.
     */
    protected $hidden = ['db_password'];

    public function isProvisioned(): bool
    {
        return $this->provisioning_status === self::STATUS_ACTIVE;
    }

    public function isCashBasis(): bool
    {
        return $this->accounting_method === self::METHOD_CASH;
    }

    public function isAccrual(): bool
    {
        return $this->accounting_method === self::METHOD_ACCRUAL;
    }

    public function accountingMethodLabel(): string
    {
        return $this->isCashBasis() ? 'Cash' : 'Accrual';
    }

    public function reportingPreferenceLabel(): string
    {
        return $this->reporting_preference === self::REPORTING_CASH_VIEW ? 'Cash view' : 'Accrual view';
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'company_modules')
            ->withPivot('is_active', 'activated_at', 'activated_by')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function companyModules(): HasMany
    {
        return $this->hasMany(CompanyModule::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(UserCompanyAssignment::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function accountingPeriods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class);
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }

    public function approvalSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ApprovalSetting::class);
    }

    public function recurringJournalTemplates(): HasMany
    {
        return $this->hasMany(RecurringJournalTemplate::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function inventoryStock(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function inventoryCostLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayer::class);
    }

    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    public function inventoryTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class);
    }
}
