<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaxReturnController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        $taxAccounts = \App\Models\Account::where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('account_type', 'current_liability')
                  ->where('code', 'like', '2300%');
            })
            ->orWhere(function ($q) {
                $q->where('account_type', 'current_asset')
                  ->where('code', 'like', '1150%');
            })
            ->get();

        return view('accounting.tax-return.index', compact('taxAccounts'));
    }
}
