<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ItemUomConversion;
use App\Models\Product;
use App\Services\Inventory\UnitOfMeasureConversionService;
use Illuminate\Http\Request;

class UomConversionController extends Controller
{
    protected UnitOfMeasureConversionService $uomService;

    public function __construct(UnitOfMeasureConversionService $uomService)
    {
        $this->uomService = $uomService;
    }

    public function index()
    {
        $companyId = session('current_company_id');

        $products = Product::where('company_id', $companyId)
            ->where('tracked_as_inventory', true)
            ->with(['uomConversions' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();

        return view('accounting.uom-conversions.index', compact('products'));
    }

    public function edit(Product $product)
    {
        if ($product->company_id !== session('current_company_id')) {
            abort(404);
        }

        $product->load(['uomConversions' => function ($q) {
            $q->where('is_active', true)->orderBy('is_base', 'desc');
        }]);

        return view('accounting.uom-conversions.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        if ($product->company_id !== session('current_company_id')) {
            abort(404);
        }

        $companyId = session('current_company_id');

        $validated = $request->validate([
            'uoms' => 'required|array|min:1',
            'uoms.*.uom_name' => 'required|string|max:50',
            'uoms.*.conversion_factor' => 'required|numeric|min:0.01',
            'uoms.*.purchase_price' => 'nullable|numeric|min:0',
            'uoms.*.sales_price' => 'nullable|numeric|min:0',
            'uoms.*.is_base' => 'boolean',
        ]);

        // Deactivate existing
        ItemUomConversion::where('company_id', $companyId)
            ->where('product_id', $product->id)
            ->update(['is_active' => false]);

        foreach ($validated['uoms'] as $uomData) {
            $existing = ItemUomConversion::where('company_id', $companyId)
                ->where('product_id', $product->id)
                ->where('uom_name', $uomData['uom_name'])
                ->first();

            if ($existing) {
                $existing->update([
                    'conversion_factor' => $uomData['conversion_factor'],
                    'purchase_price' => $uomData['purchase_price'] ?? null,
                    'sales_price' => $uomData['sales_price'] ?? null,
                    'is_base' => !empty($uomData['is_base']),
                    'is_active' => true,
                ]);
            } else {
                ItemUomConversion::create([
                    'company_id' => $companyId,
                    'product_id' => $product->id,
                    'uom_name' => $uomData['uom_name'],
                    'conversion_factor' => $uomData['conversion_factor'],
                    'purchase_price' => $uomData['purchase_price'] ?? null,
                    'sales_price' => $uomData['sales_price'] ?? null,
                    'is_base' => !empty($uomData['is_base']),
                    'is_active' => true,
                ]);
            }
        }

        return redirect()->route('accounting.uom-conversions.index')
            ->with('success', 'UOM conversions updated successfully.');
    }
}
