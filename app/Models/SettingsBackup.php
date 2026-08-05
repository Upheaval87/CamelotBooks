<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingsBackup extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'created_by',
        'label',
        'notes',
        'settings_data',
        'record_count',
    ];

    protected $casts = [
        'settings_data' => 'array',
        'record_count' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Capture a snapshot of the current company's settings.
     */
    public static function capture(int $companyId, int $userId, string $label, ?string $notes = null): self
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            'company_name' => Company::find($companyId)?->name,
            'version' => '1.0',
            'regional' => SystemSetting::getMany('regional', $companyId),
            'currency' => array_merge(
                ['base_currency' => Company::find($companyId)?->base_currency ?? ''],
                SystemSetting::getMany('currency', $companyId)
            ),
            'accounting' => SystemSetting::getMany('accounting', $companyId),
            'account_mappings' => DefaultAccountMapping::getAll($companyId),
            'approval' => ApprovalSetting::where('company_id', $companyId)->first()?->only(['requires_approval', 'threshold_amount']) ?? [],
            'numbering' => NumberingSequence::where('company_id', $companyId)
                ->get()
                ->mapWithKeys(fn($s) => [$s->document_type => $s->only(['prefix', 'padding_width', 'reset_policy', 'is_active'])])
                ->toArray(),
            'approval_thresholds' => ApprovalThreshold::where('company_id', $companyId)
                ->get()
                ->mapWithKeys(fn($t) => [$t->document_type => $t->only(['threshold_amount', 'is_active'])])
                ->toArray(),
        ];

        $recordCount = 0;
        foreach (['regional', 'currency', 'accounting', 'approval', 'numbering', 'approval_thresholds'] as $group) {
            if (is_array($data[$group] ?? null)) {
                $recordCount += count($data[$group]);
            }
        }
        $recordCount += count($data['account_mappings'] ?? []);

        return static::create([
            'company_id' => $companyId,
            'created_by' => $userId,
            'label' => $label,
            'notes' => $notes,
            'settings_data' => $data,
            'record_count' => $recordCount,
        ]);
    }

    /**
     * Restore settings from this backup snapshot.
     */
    public function restore(): int
    {
        $companyId = $this->company_id;
        $data = $this->settings_data;
        $restored = 0;

        if (!empty($data['regional'])) {
            foreach ($data['regional'] as $key => $value) {
                SystemSetting::setValue('regional', $key, $value, $companyId);
                $restored++;
            }
        }

        if (!empty($data['currency'])) {
            $baseCurrency = $data['currency']['base_currency'] ?? null;
            if ($baseCurrency) {
                Company::where('id', $companyId)->update(['base_currency' => $baseCurrency]);
            }
            foreach (['decimal_places', 'rate_source'] as $key) {
                if (isset($data['currency'][$key])) {
                    SystemSetting::setValue('currency', $key, $data['currency'][$key], $companyId);
                    $restored++;
                }
            }
        }

        if (!empty($data['accounting'])) {
            foreach ($data['accounting'] as $key => $value) {
                SystemSetting::setValue('accounting', $key, $value, $companyId);
                $restored++;
            }
        }

        if (!empty($data['account_mappings'])) {
            foreach ($data['account_mappings'] as $mappingKey => $accountId) {
                if ($accountId) {
                    $account = Account::find($accountId);
                    if ($account && $account->company_id === $companyId) {
                        DefaultAccountMapping::setMapping($companyId, $mappingKey, (int) $accountId);
                        $restored++;
                    }
                }
            }
        }

        if (!empty($data['approval'])) {
            $approvalSetting = ApprovalSetting::firstOrCreate(['company_id' => $companyId]);
            $approvalSetting->update([
                'requires_approval' => $data['approval']['requires_approval'] ?? false,
                'threshold_amount' => $data['approval']['threshold_amount'] ?? 0,
            ]);
            $restored++;
        }

        if (!empty($data['numbering'])) {
            foreach ($data['numbering'] as $docType => $seq) {
                NumberingSequence::updateOrCreate(
                    ['company_id' => $companyId, 'document_type' => $docType],
                    $seq
                );
                $restored++;
            }
        }

        if (!empty($data['approval_thresholds'])) {
            foreach ($data['approval_thresholds'] as $docType => $threshold) {
                ApprovalThreshold::updateOrCreate(
                    ['company_id' => $companyId, 'document_type' => $docType],
                    $threshold
                );
                $restored++;
            }
        }

        return $restored;
    }
}
