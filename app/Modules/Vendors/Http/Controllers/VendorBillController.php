<?php

namespace App\Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\Vendors\Models\VendorBill;
use Illuminate\Http\Request;

class VendorBillController extends Controller
{
    public function store(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'bill_number' => 'required|string',
            'bill_date' => 'required|date',
            'due_date' => 'required|date',
            'linked_po_number' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        $bill = $vendor->bills()->create([
            'company_id' => $vendor->company_id,
            'bill_number' => $data['bill_number'],
            'bill_date' => $data['bill_date'],
            'due_date' => $data['due_date'],
            'linked_po_number' => $data['linked_po_number'] ?? null,
            'status' => 'open',
            'subtotal' => $request->subtotal ?? 0,
            'tax_amount' => $request->tax_amount ?? 0,
            'total_amount' => $request->total_amount ?? 0,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Bill created successfully.');
    }

    public function show(VendorBill $bill)
    {
        $bill->load(['vendor', 'items']);
        return response()->json([
            'bill' => [
                'no' => $bill->bill_number,
                'date' => $bill->bill_date->format('M d, Y'),
                'due' => $bill->due_date->format('M d, Y'),
                'po' => $bill->linked_po_number ?? '—',
                'amt' => number_format($bill->total_amount, 2),
                'status' => $bill->status,
                'vendor' => $bill->vendor->display_name,
                'vendor_code' => $bill->vendor->vendor_code,
            ]
        ]);
    }
}
