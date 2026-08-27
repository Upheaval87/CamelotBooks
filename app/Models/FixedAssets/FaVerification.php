<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaVerification extends Model
{
    use TenantScoped;

    protected $table = 'fa_verifications';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'scheduled_date',
        'completed_date',
        'branch_id',
        'total_assets',
        'verified_count',
        'variance_count',
        'status',
        'assigned_to',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'total_assets' => 'integer',
        'verified_count' => 'integer',
        'variance_count' => 'integer',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FaVerificationLine::class, 'verification_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function completionPercentage(): float
    {
        if ($this->total_assets === 0) {
            return 0;
        }

        return round(($this->verified_count / $this->total_assets) * 100, 1);
    }

    public function getStatusLabelAttribute(): string
    {
        return str_replace('_', ' ', ucfirst($this->status));
    }
}
