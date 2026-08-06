<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Central reference table of currencies. Deliberately NOT TenantScoped: the
 * currency catalog is global and shared by the Super Admin panel (company
 * creation) and every tenant's Settings -> Currency screen.
 */
class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'symbol_position',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }

    public function label(): string
    {
        return $this->code . ' - ' . $this->name;
    }

    /**
     * The canonical default catalog (also seeded by the create_currencies
     * migration). Super admins can extend/reorder/toggle these.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaults(): array
    {
        $rows = [
            'MWK' => ['Malawian Kwacha', 'MK', 'before'],
            'USD' => ['US Dollar', '$', 'before'],
            'ZAR' => ['South African Rand', 'R', 'before'],
            'GBP' => ['British Pound', '£', 'before'],
            'EUR' => ['Euro', '€', 'before'],
            'ZMW' => ['Zambian Kwacha', 'ZK', 'before'],
            'ZWL' => ['Zimbabwean Dollar', 'Z$', 'before'],
            'BWP' => ['Botswana Pula', 'P', 'before'],
            'TZS' => ['Tanzanian Shilling', 'TSh', 'before'],
            'UGX' => ['Ugandan Shilling', 'USh', 'before'],
            'KES' => ['Kenyan Shilling', 'KSh', 'before'],
            'NGN' => ['Nigerian Naira', '₦', 'before'],
            'GHS' => ['Ghanaian Cedi', 'GH₵', 'before'],
            'PHP' => ['Philippine Peso', '₱', 'before'],
            'INR' => ['Indian Rupee', '₹', 'before'],
            'JPY' => ['Japanese Yen', '¥', 'before'],
            'CNY' => ['Chinese Yuan', '¥', 'before'],
            'CAD' => ['Canadian Dollar', 'C$', 'before'],
            'AUD' => ['Australian Dollar', 'A$', 'before'],
            'CHF' => ['Swiss Franc', 'Fr', 'before'],
        ];

        $now = now()->toDateTimeString();

        return collect($rows)->map(fn ($row, $code) => [
            'code' => $code,
            'name' => $row[0],
            'symbol' => $row[1],
            'symbol_position' => $row[2],
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all();
    }
}
