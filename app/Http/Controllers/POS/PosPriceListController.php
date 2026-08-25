<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosPriceList;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PosPriceListController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');
        $priceLists = PosPriceList::forCompany($companyId)->with('items.product')->latest()->paginate(25);
        return view('pos.pricelists.index', compact('priceLists'));
    }

    public function create()
    {
        $products = Product::where('company_id', session('current_company_id'))->orderBy('name')->get(['id', 'name', 'sku', 'sales_price']);
        return view('pos.pricelists.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:retail,wholesale,vip,custom',
            'applies_to' => 'required|string|max:255',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'boolean',
            'items' => 'array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.min_qty' => 'nullable|integer|min:1',
        ]);

        $companyId = session('current_company_id');
        $data['company_id'] = $companyId;

        $items = $data['items'] ?? [];
        unset($data['items']);

        $priceList = PosPriceList::create($data);

        foreach ($items as $item) {
            $priceList->items()->create($item);
        }

        return redirect()->route('pos.pricelists.index')->with('success', 'Price list created.');
    }

    public function show(PosPriceList $priceList)
    {
        $priceList->load('items.product');
        return view('pos.pricelists.show', compact('priceList'));
    }

    public function edit(PosPriceList $priceList)
    {
        abort_unless($priceList->company_id === session('current_company_id'), 403);
        $products = Product::where('company_id', session('current_company_id'))->orderBy('name')->get(['id', 'name', 'sku', 'sales_price']);
        $priceList->load('items.product');
        return view('pos.pricelists.edit', compact('priceList', 'products'));
    }

    public function update(Request $request, PosPriceList $priceList)
    {
        abort_unless($priceList->company_id === session('current_company_id'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:retail,wholesale,vip,custom',
            'applies_to' => 'required|string|max:255',
            'effective_from' => 'nullable|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'boolean',
            'items' => 'array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.min_qty' => 'nullable|integer|min:1',
        ]);

        $items = $data['items'] ?? [];
        unset($data['items']);

        $priceList->update($data);
        $priceList->items()->delete();
        foreach ($items as $item) {
            $priceList->items()->create($item);
        }

        return redirect()->route('pos.pricelists.index')->with('success', 'Price list updated.');
    }

    public function destroy(PosPriceList $priceList)
    {
        abort_unless($priceList->company_id === session('current_company_id'), 403);
        $priceList->items()->delete();
        $priceList->delete();
        return redirect()->route('pos.pricelists.index')->with('success', 'Price list deleted.');
    }
}
