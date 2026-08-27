<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FaAsset extends Model
{
    use TenantScoped;

    protected $table = 'fa_assets';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISPOSED = 'disposed';
    public const STATUS_SCRAPPED = 'scrapped';
    public const STATUS_PENDING = 'pending';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_DISPOSED,
        self::STATUS_SCRAPPED,
        self::STATUS_PENDING,
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'cost_center_id',
        'category_id',
        'class_id',
        'asset_code',
        'name',
        'description',
        'serial_number',
        'tag_number',
        'location',
        'custodian',
        'acquisition_date',
        'in_service_date',
        'disposal_date',
        'acquisition_cost',
        'accumulated_depreciation',
        'accumulated_impairment',
        'net_book_value',
        'depreciation_method',
        'useful_life',
        'residual_value',
        'depreciation_rate',
        'is_componentised',
        'is_revalued',
        'asset_account_id',
        'accum_dep_account_id',
        'dep_expense_account_id',
        'disposal_account_id',
        'journal_entry_id',
        'source_type',
        'source_id',
        'vendor_id',
        'created_by',
        'status',
        'is_active',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'in_service_date' => 'date',
        'disposal_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'accumulated_impairment' => 'decimal:2',
        'net_book_value' => 'decimal:2',
        'useful_life' => 'integer',
        'residual_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:4',
        'is_componentised' => 'boolean',
        'is_revalued' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [];

    // ── Scopes ───────────────────────────────────────

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    // ── Relationships ────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaCategory::class, 'category_id');
    }

    public function faClass(): BelongsTo
    {
        return $this->belongsTo(FaClass::class, 'class_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CostCenter::class, 'cost_center_id');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Account::class, 'asset_account_id');
    }

    public function accumDepAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Account::class, 'accum_dep_account_id');
    }

    public function depExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Account::class, 'dep_expense_account_id');
    }

    public function disposalAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Account::class, 'disposal_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Models\JournalEntry::class, 'journal_entry_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function depBooks(): HasMany
    {
        return $this->hasMany(FaDepBook::class, 'asset_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(FaComponent::class, 'asset_id');
    }

    public function acquisitions(): HasMany
    {
        return $this->hasMany(FaAcquisition::class, 'asset_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(FaTransfer::class, 'asset_id');
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(FaDisposal::class, 'asset_id');
    }

    public function impairments(): HasMany
    {
        return $this->hasMany(FaImpairment::class, 'asset_id');
    }

    public function revaluations(): HasMany
    {
        return $this->hasMany(FaRevaluation::class, 'asset_id');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(FaMaintenance::class, 'asset_id');
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(FaInsurance::class, 'asset_id');
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(FaWarranty::class, 'asset_id');
    }

    public function verificationLines(): HasMany
    {
        return $this->hasMany(FaVerificationLine::class, 'asset_id');
    }

    public function custodyRecords(): HasMany
    {
        return $this->hasMany(FaCustody::class, 'asset_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FaDocument::class, 'asset_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(FaHistory::class, 'asset_id');
    }

    public function financialBook(): HasOne
    {
        return $this->hasOne(FaDepBook::class, 'asset_id')->where('book_type', 'financial');
    }

    public function source()
    {
        return $this->morphTo();
    }

    // ── Helpers ──────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->is_active;
    }

    public function isDisposed(): bool
    {
        return $this->status === self::STATUS_DISPOSED;
    }

    public function residualAmount(): float
    {
        return (float) $this->residual_value;
    }

    public function depreciableAmount(): float
    {
        return (float) $this->acquisition_cost - $this->residualAmount();
    }

    public function fullyDepreciated(): bool
    {
        return $this->accumulated_depreciation >= $this->acquisition_cost - $this->residual_value;
    }

    public function getNetBookValueForDisplayAttribute(): string
    {
        return format_number($this->net_book_value);
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
