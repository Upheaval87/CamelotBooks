<?php

namespace App\Services\Inventory;

use App\Models\ItemCategory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ItemCategoryService
{
    public function create(array $data, int $companyId): ItemCategory
    {
        if (ItemCategory::where('company_id', $companyId)->where('code', $data['code'])->exists()) {
            throw new InvalidArgumentException("Category code '{$data['code']}' already exists.");
        }

        return ItemCategory::create([
            'company_id' => $companyId,
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'default_income_account_id' => $data['default_income_account_id'] ?? null,
            'default_cogs_account_id' => $data['default_cogs_account_id'] ?? null,
            'default_inventory_asset_account_id' => $data['default_inventory_asset_account_id'] ?? null,
            'default_reorder_point' => $data['default_reorder_point'] ?? null,
            'default_base_uom' => $data['default_base_uom'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(ItemCategory $category, array $data): ItemCategory
    {
        $duplicateCheck = ItemCategory::where('company_id', $category->company_id)
            ->where('code', $data['code'] ?? $category->code)
            ->where('id', '!=', $category->id)
            ->exists();

        if ($duplicateCheck) {
            throw new InvalidArgumentException("Category code '{$data['code']}' already exists.");
        }

        $category->update(array_filter([
            'code' => $data['code'] ?? null,
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'default_income_account_id' => $data['default_income_account_id'] ?? null,
            'default_cogs_account_id' => $data['default_cogs_account_id'] ?? null,
            'default_inventory_asset_account_id' => $data['default_inventory_asset_account_id'] ?? null,
            'default_reorder_point' => $data['default_reorder_point'] ?? null,
            'default_base_uom' => $data['default_base_uom'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn($v) => $v !== null));

        return $category->fresh();
    }

    public function toggle(ItemCategory $category): ItemCategory
    {
        $category->update(['is_active' => !$category->is_active]);
        return $category->fresh();
    }

    /**
     * Get defaults for a product from its category. Used during product creation/editing.
     */
    public function getCategoryDefaults(?int $categoryId): ?array
    {
        if (!$categoryId) {
            return null;
        }

        $category = ItemCategory::find($categoryId);
        if (!$category) {
            return null;
        }

        return [
            'income_account_id' => $category->default_income_account_id,
            'cogs_account_id' => $category->default_cogs_account_id,
            'inventory_asset_account_id' => $category->default_inventory_asset_account_id,
            'reorder_point' => $category->default_reorder_point,
            'unit_of_measure' => $category->default_base_uom,
        ];
    }
}
