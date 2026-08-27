<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaAcquisition extends Model
{
    use TenantScoped;

    protected $table = 'fa_acquisitions';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'asset_id',
        'reference',
        'acquisition_date',
        'total_cost',
        'tax_amount',
        'net_cost',
        'vendor',
        'vendor_id',
        'invoice_number',
        'purchase_order',
        'description',
        'journal_entry_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'total_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_cost' => 'decimal:2',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function vendorRecord(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(\App\Models\JournalEntry::class, 'journal_entry_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }
}
