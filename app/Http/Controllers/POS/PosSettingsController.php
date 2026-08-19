<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosPaymentMethod;
use App\Models\PosTerminal;
use App\Models\Branch;
use Illuminate\Http\Request;

class PosSettingsController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        $terminals = PosTerminal::where('company_id', $companyId)
            ->with('branch')
            ->latest()
            ->get();

        $paymentMethods = PosPaymentMethod::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pos.settings.index', compact('terminals', 'paymentMethods', 'branches'));
    }
}
