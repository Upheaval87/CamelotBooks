<?php

namespace App\Services;

use App\Models\SystemSetting;

class FeatureManagement
{
    private const GROUP = 'features';

    private const AVAILABLE_FEATURES = [
        'inventory' => 'Inventory Management',
        'fixed_assets' => 'Fixed Assets',
        'payroll' => 'Payroll',
        'banking' => 'Banking',
        'multi_currency' => 'Multi-Currency',
        'purchasing' => 'Purchasing',
        'analytics' => 'Analytics',
        'budgets' => 'Budgets',
        'pos' => 'Point of Sale',
    ];

    public static function isEnabled(int $companyId, string $feature): bool
    {
        if (!in_array($feature, array_keys(self::AVAILABLE_FEATURES))) {
            return false;
        }
        return SystemSetting::getValue(self::GROUP, $feature, $companyId, 'false') === 'true';
    }

    public static function getEnabledFeatures(int $companyId): array
    {
        $enabled = [];
        foreach (self::AVAILABLE_FEATURES as $key => $label) {
            if (self::isEnabled($companyId, $key)) {
                $enabled[$key] = $label;
            }
        }
        return $enabled;
    }

    public static function getAvailableFeatures(): array
    {
        return self::AVAILABLE_FEATURES;
    }

    public static function enable(int $companyId, string $feature): void
    {
        SystemSetting::setValue(self::GROUP, $feature, 'true', $companyId);
    }

    public static function disable(int $companyId, string $feature): void
    {
        SystemSetting::setValue(self::GROUP, $feature, 'false', $companyId);
    }
}
