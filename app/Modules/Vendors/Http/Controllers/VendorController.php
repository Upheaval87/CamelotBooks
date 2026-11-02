<?php

namespace App\Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::with('category')->orderBy('display_name')->get();
        return view('vendors.index', compact('vendors'));
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['category', 'bills.items', 'payments.splitLines', 'payments.allocations', 'purchaseOrders', 'documents', 'messages', 'notes', 'evaluations', 'ledgerEntries']);
        
        $openBalance = $vendor->bills()->whereIn('status', ['open', 'overdue'])->sum('total_amount') - $vendor->bills()->whereIn('status', ['open', 'overdue'])->sum('amount_paid');
        if ($openBalance < 0) $openBalance = 0;
        
        $totalBilled = $vendor->bills()->whereNotIn('status', ['void'])->sum('total_amount');
        
        return view('vendors.show', compact('vendor', 'openBalance', 'totalBilled'));
    }

    public function activate(Vendor $vendor)
    {
        $vendor->update(['is_active' => true]);
        return back()->with('success', 'Vendor activated successfully.');
    }

    public function deactivate(Vendor $vendor)
    {
        $vendor->update(['is_active' => false]);
        return back()->with('success', 'Vendor deactivated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();
        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
    }
}
