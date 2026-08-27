<?php

namespace App\Models\FixedAssets;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaDocument extends Model
{
    use TenantScoped;

    protected $table = 'fa_documents';

    public const TYPE_INVOICE = 'invoice';
    public const TYPE_WARRANTY = 'warranty';
    public const TYPE_PHOTO = 'photo';
    public const TYPE_REPORT = 'report';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'company_id',
        'asset_id',
        'doc_type',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('doc_type', $type);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FaAsset::class, 'asset_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return ucfirst($this->doc_type);
    }

    public function getFormattedSizeAttribute(): string
    {
        return format_bytes($this->file_size);
    }
}
