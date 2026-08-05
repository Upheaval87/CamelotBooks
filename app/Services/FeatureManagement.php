<?php

namespace App\Services;

use App\Models\CompanyModule;
use App\Models\Module;

/**
 * Feature (module) activation for a company.
 *
 * The CENTRAL `company_modules` table is the single source of truth (Phase 4):
 * only the Super Admin panel toggles modules. The tenant-side System Settings
 * "Feature Management" page is read-only. The feature codes below map 1:1 to
 * `modules.code` rows (see ModuleRegistry::catalog()) with is_core = false.
 */
class FeatureManagement
{
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
        'bi' => 'Business Intelligence',
    ];

    public static function isEnabled(int $companyId, string $feature): bool
    {
        if (!array_key_exists($feature, self::AVAILABLE_FEATURES)) {
            return false;
        }

        $row = self::companyModule($companyId, $feature);

        return $row?->is_active === true;
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

    public static function enable(int $companyId, string $feature, ?int $actorId = null): void
    {
        if (!array_key_exists($feature, self::AVAILABLE_FEATURES)) {
            return;
        }

        self::upsertCompanyModule($companyId, $feature, true, $actorId);
    }

    public static function disable(int $companyId, string $feature, ?int $actorId = null): void
    {
        if (!array_key_exists($feature, self::AVAILABLE_FEATURES)) {
            return;
        }

        self::upsertCompanyModule($companyId, $feature, false, $actorId);
    }

    private static function companyModule(int $companyId, string $feature): ?CompanyModule
    {
        $moduleId = self::moduleId($feature);

        if ($moduleId === null) {
            return null;
        }

        return CompanyModule::query()
            ->where('company_id', $companyId)
            ->where('module_id', $moduleId)
            ->first();
    }

    private static function moduleId(string $feature): ?int
    {
        return Module::query()->where('code', $feature)->value('id');
    }

    private static function upsertCompanyModule(int $companyId, string $feature, bool $active, ?int $actorId): void
    {
        $moduleId = self::moduleId($feature);

        if ($moduleId === null) {
            return;
        }

        CompanyModule::updateOrCreate(
            ['company_id' => $companyId, 'module_id' => $moduleId],
            [
                'is_active' => $active,
                'activated_at' => $active ? now() : null,
                'activated_by' => $active ? $actorId : null,
                'updated_at' => now(),
            ]
        );
    }
}
