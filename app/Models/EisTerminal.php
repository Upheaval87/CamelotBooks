<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EisTerminal extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'company_id',
        'site_id',
        'device_serial',
        'status',
        'jwt_token',
        'secret_key',
        'validation_key',
        'activated_at',
        'last_submission_at',
        'should_block_terminal',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'last_submission_at' => 'datetime',
        'should_block_terminal' => 'boolean',
    ];

    protected $hidden = [
        'jwt_token',
        'secret_key',
        'validation_key',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(EisSubmission::class, 'eis_terminal_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && !$this->should_block_terminal;
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
