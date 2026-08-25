<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosPromotion;
use Illuminate\Http\Request;

class PosPromotionController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');
        $promotions = PosPromotion::forCompany($companyId)->latest()->paginate(25);
        return view('pos.promotions.index', compact('promotions'));
    }

    public function create()
    {
        return view('pos.promotions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:percentage,fixed_amount,buy_x_get_y,customer_discount',
            'discount_value' => 'required|numeric|min:0',
            'min_qty' => 'nullable|integer|min:1',
            'max_qty' => 'nullable|integer|min:1',
            'customer_group' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'applies_to' => 'required|string|in:all_items,specific_items,specific_categories',
        ]);

        $data['company_id'] = session('current_company_id');
        $data['created_by'] = auth()->id();

        PosPromotion::create($data);

        return redirect()->route('pos.promotions.index')->with('success', 'Promotion created.');
    }

    public function show(PosPromotion $promotion)
    {
        return view('pos.promotions.show', compact('promotion'));
    }

    public function update(Request $request, PosPromotion $promotion)
    {
        abort_unless($promotion->company_id === session('current_company_id'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:percentage,fixed_amount,buy_x_get_y,customer_discount',
            'discount_value' => 'required|numeric|min:0',
            'min_qty' => 'nullable|integer|min:1',
            'max_qty' => 'nullable|integer|min:1',
            'customer_group' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'applies_to' => 'required|string|in:all_items,specific_items,specific_categories',
        ]);

        $promotion->update($data);

        return redirect()->route('pos.promotions.index')->with('success', 'Promotion updated.');
    }

    public function destroy(PosPromotion $promotion)
    {
        abort_unless($promotion->company_id === session('current_company_id'), 403);
        $promotion->delete();
        return redirect()->route('pos.promotions.index')->with('success', 'Promotion deleted.');
    }
}
