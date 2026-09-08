<?php

namespace App\Services\BI;

use App\Models\BiAuditLog;
use App\Models\BiSetting;
use Illuminate\Support\Facades\DB;

/**
 * Read/write access to saved BI settings (scenario multipliers, allocation
 * drivers, per-branch assumptions). Every write bumps the setting's version
 * counter and appends an insert-only bi_audit_log row.
 */
class BiSettingService
{
    public function get(string $group, string $key, int $companyId, mixed $default = null): mixed
    {
        $row = BiSetting::where('company_id', $companyId)
            ->where('group_key', $group)
            ->where('key', $key)
            ->first();

        return $row->value ?? $default;
    }

    /**
     * Factory defaults for $group merged with any saved overrides for the
     * company. Saved values win.
     */
    public function getWithDefaults(string $group, int $companyId): array
    {
        $defaults = config("bi.defaults.$group", []);
        $saved = BiSetting::where('company_id', $companyId)
            ->where('group_key', $group)
            ->get()
            ->pluck('value', 'key')
            ->filter(fn ($v) => !is_null($v))
            ->toArray();

        return $saved === [] ? $defaults : array_replace_recursive($defaults, $saved);
    }

    /**
     * @return array{version:int} the new version after the write
     */
    public function set(string $group, string $key, mixed $value, int $companyId, ?int $userId = null): array
    {
        return DB::transaction(function () use ($group, $key, $value, $companyId, $userId) {
            $row = BiSetting::lockForUpdate()
                ->where('company_id', $companyId)
                ->where('group_key', $group)
                ->where('key', $key)
                ->first();

            $old = $row?->value;

            $row = BiSetting::updateOrCreate(
                ['company_id' => $companyId, 'group_key' => $group, 'key' => $key],
                [
                    'value' => $value,
                    'version' => ($row?->version ?? 0) + 1,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]
            );

            BiAuditLog::log($companyId, 'setting.updated', $group, $key, $old, $value, $userId);

            return ['version' => $row->version];
        });
    }

    /**
     * Replace several keys of one group in a single transaction/audit row.
     *
     * @param array<string,mixed> $values
     */
    public function setMany(string $group, array $values, int $companyId, ?int $userId = null): array
    {
        $last = ['version' => 0];
        foreach ($values as $key => $value) {
            $last = $this->set($group, (string) $key, $value, $companyId, $userId);
        }

        return $last;
    }
}