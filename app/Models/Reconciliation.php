<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reconciliation extends Model
{
    use TenantScoped;

    protected $table = 'bank_reconciliations';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_READY_FOR_REVIEW = 'ready_for_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RECONCILED = 'reconciled';
    public const STATUS_REVERSED = 'reversed';

    public const ACTIVE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_IN_PROGRESS,
        self::STATUS_READY_FOR_REVIEW,
        self::STATUS_APPROVED,
    ];

    public const LOCKED_STATUSES = [
        self::STATUS_RECONCILED,
        self::STATUS_REVERSED,
    ];

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'branch_id',
        'cost_center_id',
        'statement_number',
        'statement_date',
        'period_start',
        'period_end',
        'opening_balance',
        'closing_balance',
        'currency',
        'status',
        'statement_balance',
        'book_balance',
        'difference',
        'approved_by',
        'approved_at',
        'completed_by',
        'completed_at',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
        'created_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'statement_balance' => 'decimal:2',
        'book_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(ReconciliationMatch::class, 'reconciliation_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(ReconciliationAdjustment::class, 'reconciliation_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ReconciliationAuditLog::class, 'reconciliation_id');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class, 'reconciliation_id');
    }

    public function statementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class, 'reconciliation_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBankAccount(Builder $query, int $bankAccountId): Builder
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    public function scopeNotReversed(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_REVERSED);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, self::LOCKED_STATUSES, true);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isBalanced(float $tolerance = 0.005): bool
    {
        return abs((float) $this->difference) <= $tolerance;
    }

    public static function statusLabel(string $status): string
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_READY_FOR_REVIEW => 'Ready for Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_RECONCILED => 'Reconciled',
            self::STATUS_REVERSED => 'Reversed',
        ][$status] ?? ucfirst($status);
    }
}
