<?php

namespace App\Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function store(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'po_number' => 'required|string',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'deliver_to' => 'nullable|string',
            'priority' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        $po = $vendor->purchaseOrders()->create([
            'company_id' => $vendor->company_id,
            'po_number' => $data['po_number'],
            'order_date' => $data['order_date'],
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'deliver_to' => $data['deliver_to'] ?? null,
            'priority' => $data['priority'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $request->action === 'send' ? 'sent' : 'draft',
            'subtotal' => $request->subtotal ?? 0,
            'tax_amount' => $request->tax_amount ?? 0,
            'total_amount' => $request->total_amount ?? 0,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Purchase order created successfully.');
    }
}
