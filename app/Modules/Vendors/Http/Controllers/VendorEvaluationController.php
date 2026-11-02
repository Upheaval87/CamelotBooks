<?php

namespace App\Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Http\Request;

class VendorEvaluationController extends Controller
{
    public function store(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'quality_score' => 'nullable|integer|min:1|max:5',
            'delivery_score' => 'nullable|integer|min:1|max:5',
            'price_score' => 'nullable|integer|min:1|max:5',
            'communication_score' => 'nullable|integer|min:1|max:5',
            'compliance_score' => 'nullable|integer|min:1|max:5',
            'comments' => 'nullable|string',
        ]);

        $scores = array_filter([
            $data['quality_score'] ?? null,
            $data['delivery_score'] ?? null,
            $data['price_score'] ?? null,
            $data['communication_score'] ?? null,
            $data['compliance_score'] ?? null,
        ], fn($v) => $v !== null);

        $weighted = count($scores) > 0 ? array_sum($scores) / count($scores) : null;

        $vendor->evaluations()->create([
            'company_id' => $vendor->company_id,
            'period' => $request->period ?? date('Y-m'),
            'quality_score' => $data['quality_score'] ?? null,
            'delivery_score' => $data['delivery_score'] ?? null,
            'price_score' => $data['price_score'] ?? null,
            'communication_score' => $data['communication_score'] ?? null,
            'compliance_score' => $data['compliance_score'] ?? null,
            'weighted_score' => $weighted,
            'comments' => $data['comments'] ?? null,
            'status' => $request->action === 'submit' ? 'submitted' : 'draft',
            'evaluated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Evaluation saved successfully.');
    }
}
