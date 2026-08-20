<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'employee_id',
        'kind',
        'field_name',
        'mime',
        'size_bytes',
        'storage_ref',
        'created_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            'photo' => 'Passport Photo',
            'national_id' => 'National ID',
            'custom' => $this->field_name ?? 'Attachment',
            default => 'Document',
        };
    }

    public function formatSize(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
