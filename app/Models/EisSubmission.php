<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EisSubmission extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ERROR = 'error';

    protected $fillable = [
        'company_id',
        'eis_terminal_id',
        'receipt_number',
        'invoice_type',
        'status',
        'request_payload',
        'response_payload',
        'validation_url',
        'error_message',
        'retry_count',
        'submitted_at',
        'accepted_at',
    ];

    protected $casts = [
        'request_payload' => 'json',
        'response_payload' => 'json',
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(EisTerminal::class, 'eis_terminal_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRetriable($query)
    {
        return $query->where('status', self::STATUS_ERROR)
            ->where('retry_count', '<', 5);
    }
}
