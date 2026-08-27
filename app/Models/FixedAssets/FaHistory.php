<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaHistory extends Model
{
    use TenantScoped;

    protected $table = 'fa_history';

    public const EVENT_CREATED = 'created';
    public const EVENT_ACTIVATED = 'activated';
    public const EVENT_DEPRECIATED = 'depreciated';
    public const EVENT_TRANSFERRED = 'transferred';
    public const EVENT_DISPOSED = 'disposed';
    public const EVENT_IMPAIRED = 'impaired';
    public const EVENT_REVALUED = 'revalued';
    public const EVENT_VERIFIED = 'verified';
    public const EVENT_CUSTODY = 'custody';
    public const EVENT_MAINTENANCE = 'maintenance';
    public const EVENT_DOCUMENT = 'document';
    public const EVENT_ADJUSTMENT = 'adjustment';

    public const EVENT_TYPES = [
        self::EVENT_CREATED,
        self::EVENT_ACTIVATED,
        self::EVENT_DEPRECIATED,
        self::EVENT_TRANSFERRED,
        self::EVENT_DISPOSED,
        self::EVENT_IMPAIRED,
        self::EVENT_REVALUED,
        self::EVENT_VERIFIED,
        self::EVENT_CUSTODY,
        self::EVENT_MAINTENANCE,
        self::EVENT_DOCUMENT,
        self::EVENT_ADJUSTMENT,
    ];

    protected $fillable = [
        'company_id',
        'asset_id',
        'event_type',
        'description',
        'old_values',
        'new_values',
        'reference_id',
        'reference_type',
        'user_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForAsset(Builder $query, int $assetId): Builder
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopeByEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function getEventTypeLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->event_type));
    }

    public static function log(int $companyId, int $assetId, string $eventType, string $description, ?array $oldValues = null, ?array $newValues = null, ?int $userId = null, ?int $referenceId = null, ?string $referenceType = null): static
    {
        return static::create([
            'company_id' => $companyId,
            'asset_id' => $assetId,
            'event_type' => $eventType,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId ?? auth()->id(),
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);
    }
}
