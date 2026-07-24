<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use App\Models\PosPaymentMethod;
use App\Models\Product;
use App\Services\POS\PosSaleService;
use Illuminate\Http\Request;

class PosSaleController extends Controller
{
    public function checkout()
    {
        $companyId = session('current_company_id');
        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sales_price', 'tax_rate', 'is_taxable', 'tracked_as_inventory']);

        // Attach current stock for tracked items
        $products->each(function ($product) use ($companyId) {
            if ($product->tracked_as_inventory) {
                $product->current_stock = app(\App\Services\Accounting\InventoryService::class)
                    ->getProductTotalQuantityOnHand($companyId, $product->id);
            } else {
                $product->current_stock = null; // unlimited
            }
        });

        $paymentMethods = PosPaymentMethod::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'clearing_account_id', 'requires_reference']);
        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'bank_name']);

        return view('pos.sales.checkout', compact('products', 'paymentMethods', 'customers', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = $request->user()->id;

        $validated = $request->validate([
            'terminal_id' => 'required|exists:pos_terminals,id',
            'cashier_session_id' => 'nullable|exists:pos_cashier_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'reference' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_amount' => 'nullable|numeric|min:0',
            'lines.*.discount_type' => 'nullable|string',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:pos_payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.cash_tendered' => 'nullable|numeric|min:0',
            'payments.*.change_given' => 'nullable|numeric|min:0',
            'payments.*.reference_number' => 'nullable|string|max:255',
            'payments.*.processor_name' => 'nullable|string|max:255',
        ]);

        $validated['company_id'] = $companyId;

        try {
            $sale = app(PosSaleService::class)->checkout($validated, $userId);

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'total' => $sale->total,
                'message' => "Sale {$sale->sale_number} completed.",
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function receipt(int $id)
    {
        $companyId = session('current_company_id');

        $sale = \App\Models\PosSale::where('company_id', $companyId)
            ->with(['lines.product', 'payments.paymentMethod', 'terminal', 'customer'])
            ->findOrFail($id);

        return view('pos.sales.receipt', compact('sale'));
    }
}
