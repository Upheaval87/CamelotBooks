<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandedCostVoucher extends Model
{
    use TenantScoped;

    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_VOID = 'void';

    protected $fillable = [
        'company_id',
        'voucher_number',
        'vendor_id',
        'allocation_method',
        'total_amount',
        'status',
        'journal_entry_id',
        'date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(LandedCostComponent::class, 'voucher_id');
    }

    public function grns(): BelongsToMany
    {
        return $this->belongsToMany(GoodsReceivedNote::class, 'landed_cost_voucher_grns', 'voucher_id', 'grn_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
