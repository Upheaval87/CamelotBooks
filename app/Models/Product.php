<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'brand',
        'description',
        'sku',
        'barcode',
        'type',
        'tracked_as_inventory',
        'sales_price',
        'purchase_price',
        'reorder_point',
        'reorder_qty',
        'max_stock',
        'lead_time_days',
        'default_supplier_id',
        'costing_method',
        'low_stock_alerts',
        'batch_expiry_tracking',
        'serial_tracking',
        'price_list',
        'opening_stock',
        'opening_as_at',
        'warehouse_id',
        'unit_of_measure',
        'income_account_id',
        'expense_account_id',
        'inventory_asset_account_id',
        'tax_rate',
        'is_taxable',
        'is_active',
        'is_assembly',
    ];

    protected $casts = [
        'sales_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
        'tracked_as_inventory' => 'boolean',
        'is_assembly' => 'boolean',
        'low_stock_alerts' => 'boolean',
        'batch_expiry_tracking' => 'boolean',
        'serial_tracking' => 'boolean',
        'max_stock' => 'integer',
        'reorder_qty' => 'integer',
        'lead_time_days' => 'integer',
        'opening_stock' => 'decimal:4',
        'opening_as_at' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function inventoryAssetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_asset_account_id');
    }

    public function defaultSupplier(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'default_supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'warehouse_id');
    }

    public function returnable(): HasOne
    {
        return $this->hasOne(ItemReturnable::class, 'item_id');
    }

    public function costLayers(): HasMany
    {
        return $this->hasMany(InventoryCostLayer::class);
    }

    public function stock(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function itemCategory(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function getStockQtyAttribute(): float
    {
        return (float) $this->stock()->sum('quantity_on_hand');
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function billLines(): HasMany
    {
        return $this->hasMany(BillLine::class);
    }

    public function uomConversions(): HasMany
    {
        return $this->hasMany(ItemUomConversion::class);
    }

    public function billOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'assembly_product_id');
    }

    public function bomLines(): HasMany
    {
        return $this->hasMany(BillOfMaterialLine::class, 'component_product_id');
    }

    public function assemblyBuilds(): HasMany
    {
        return $this->hasMany(AssemblyBuild::class, 'assembly_product_id');
    }

    /**
     * Resolve effective account values: product-specific > category default.
     */
    public function getEffectiveIncomeAccountIdAttribute(): ?int
    {
        return $this->income_account_id ?? $this->category?->default_income_account_id;
    }

    public function getEffectiveCogsAccountIdAttribute(): ?int
    {
        return $this->expense_account_id ?? $this->category?->default_cogs_account_id;
    }

    public function getEffectiveInventoryAssetAccountIdAttribute(): ?int
    {
        return $this->inventory_asset_account_id ?? $this->category?->default_inventory_asset_account_id;
    }

    public function getEffectiveReorderPointAttribute(): ?float
    {
        return $this->reorder_point ?? $this->category?->default_reorder_point;
    }

    public function getEffectiveBaseUomAttribute(): ?string
    {
        return $this->unit_of_measure ?? $this->category?->default_base_uom;
    }

    public function getUomFactor(string $uomName): float
    {
        $conversion = $this->uomConversions()
            ->where('uom_name', $uomName)
            ->where('is_active', true)
            ->first();

        return $conversion ? (float) $conversion->conversion_factor : 1.0;
    }

    public function getBaseUomName(): string
    {
        $base = $this->uomConversions()
            ->where('is_base', true)
            ->where('is_active', true)
            ->first();

        return $base?->uom_name ?? $this->unit_of_measure ?? 'Each';
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInventoryTracked($query)
    {
        return $query->where(function ($q) {
            $q->where('tracked_as_inventory', true)
                ->orWhere('type', 'inventory');
        });
    }
}
