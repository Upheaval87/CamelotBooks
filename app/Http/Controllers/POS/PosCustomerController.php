<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class PosCustomerController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $customers = Customer::where('company_id', $companyId)
            ->withSum(['sales' => fn ($q) => $q->where('status', 'posted')], 'total')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', "%{$request->q}%"))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->orderBy('name')
            ->paginate(25);

        return view('pos.customers.index', compact('customers'));
    }
}
