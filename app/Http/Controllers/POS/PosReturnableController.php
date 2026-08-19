<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosPaymentMethod;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosReturnableController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sales_price']);

        $paymentMethods = PosPaymentMethod::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $recentReturnables = DB::connection('tenant')
            ->table('pos_returnables')
            ->where('company_id', $companyId)
            ->latest()
            ->limit(20)
            ->get();

        $stats = [
            'today_count' => DB::connection('tenant')
                ->table('pos_returnables')
                ->where('company_id', $companyId)
                ->whereDate('created_at', today())
                ->count(),
            'today_total' => DB::connection('tenant')
                ->table('pos_returnables')
                ->where('company_id', $companyId)
                ->whereDate('created_at', today())
                ->sum('credit_amount'),
            'pending_count' => DB::connection('tenant')
                ->table('pos_returnables')
                ->where('company_id', $companyId)
                ->where('status', 'pending')
                ->count(),
            'settled_count' => DB::connection('tenant')
                ->table('pos_returnables')
                ->where('company_id', $companyId)
                ->where('status', 'settled')
                ->count(),
        ];

        return view('pos.returnables.index', compact('products', 'paymentMethods', 'recentReturnables', 'stats'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = $request->user()->id;

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:9999',
            'credit_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::connection('tenant')->table('pos_returnables')->insert([
            'company_id' => $companyId,
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'credit_amount' => $validated['credit_amount'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('pos.returnables.index')->with('success', 'Bottle return recorded. BRR issued.');
    }
}
