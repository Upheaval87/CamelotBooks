<?php

namespace App\Services\Inventory;

use App\Models\ItemUomConversion;
use App\Models\Product;
use InvalidArgumentException;

class UnitOfMeasureConversionService
{
    /**
     * Convert a quantity from a transaction UOM to the product's base/stocking UOM.
     * Returns the converted quantity (base UOM) and stores the original for display.
     */
    public function convertToBase(int $companyId, int $productId, string $uomName, float $transactionQty): array
    {
        $conversion = ItemUomConversion::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('uom_name', $uomName)
            ->where('is_active', true)
            ->first();

        if (!$conversion) {
            throw new InvalidArgumentException("UOM '{$uomName}' is not configured for product ID {$productId}.");
        }

        $factor = (float) $conversion->conversion_factor;
        $baseQty = round($transactionQty * $factor, 4);

        return [
            'transaction_uom' => $uomName,
            'transaction_qty' => $transactionQty,
            'conversion_factor' => $factor,
            'base_qty' => $baseQty,
        ];
    }

    /**
     * Validate that a UOM name exists and is active for the given product.
     */
    public function isValidUom(int $companyId, int $productId, string $uomName): bool
    {
        return ItemUomConversion::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('uom_name', $uomName)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all active UOMs for a product.
     */
    public function getProductUoms(int $companyId, int $productId): \Illuminate\Support\Collection
    {
        return ItemUomConversion::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('is_base', 'desc')
            ->orderBy('conversion_factor', 'asc')
            ->get();
    }

    /**
     * Create a base UOM conversion record for a product.
     * Called when a product is created to ensure it always has at least one UOM.
     */
    public function ensureBaseUom(int $companyId, int $productId, string $uomName = 'Each'): ItemUomConversion
    {
        $existing = ItemUomConversion::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('is_base', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        return ItemUomConversion::create([
            'company_id' => $companyId,
            'product_id' => $productId,
            'uom_name' => $uomName,
            'conversion_factor' => 1.0,
            'purchase_price' => 0,
            'sales_price' => 0,
            'is_base' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Get the price for a specific UOM. Falls back to product's sales_price or purchase_price.
     */
    public function getPriceForUom(int $companyId, int $productId, string $uomName, string $priceType = 'sales'): float
    {
        $conversion = ItemUomConversion::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('uom_name', $uomName)
            ->where('is_active', true)
            ->first();

        if ($conversion) {
            $price = $priceType === 'purchase'
                ? (float) $conversion->purchase_price
                : (float) $conversion->sales_price;
            if ($price > 0) {
                return $price;
            }
        }

        $product = Product::findOrFail($productId);
        return $priceType === 'purchase'
            ? (float) $product->purchase_price
            : (float) $product->sales_price;
    }
}
