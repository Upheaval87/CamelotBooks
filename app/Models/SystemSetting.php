<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'group',
        'key',
        'value',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function getValue(string $group, string $key, ?int $companyId = null, mixed $default = null): mixed
    {
        $query = static::where('group', $group)->where('key', $key);

        if ($companyId) {
            $query->where('company_id', $companyId);
        } else {
            $query->whereNull('company_id');
        }

        $setting = $query->first();

        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }

    public static function setValue(string $group, string $key, mixed $value, ?int $companyId = null): void
    {
        static::updateOrCreate(
            ['company_id' => $companyId, 'group' => $group, 'key' => $key],
            ['value' => $value]
        );
    }

    public static function getMany(string $group, ?int $companyId = null): array
    {
        $query = static::where('group', $group);

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        } else {
            $query->whereNull('company_id');
        }

        return $query->pluck('value', 'key')->toArray();
    }
}
