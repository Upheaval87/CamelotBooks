<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\MobileMoneyProvider;
use App\Models\PosPaymentMethod;
use App\Models\Product;
use App\Models\SalesReceipt;
use App\Services\Accounting\SalesReceiptService;
use Illuminate\Http\Request;

class SalesReceiptController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $receipts = SalesReceipt::where('company_id', $companyId)
            ->with(['customer', 'createdByUser', 'postedByUser'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->where(function ($q2) use ($s) {
                $q2->where('receipt_number', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$s}%"));
            }))
            ->orderBy('receipt_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('accounting.sales-receipts.index', compact('receipts'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $customers = Customer::where('company_id', $companyId)->orderBy('name')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $incomeAccounts = Account::where('company_id', $companyId)->where('type', 'revenue')->where('is_active', true)->orderBy('code')->get();
        $paymentMethods = PosPaymentMethod::where('is_active', true)->orderBy('name')->get();
        $mobileProviders = MobileMoneyProvider::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $bankAccounts = Account::where('company_id', $companyId)->whereIn('type', ['asset', 'bank'])->where('is_active', true)->orderBy('code')->get();
        $itemCategories = \App\Models\ItemCategory::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        return view('accounting.sales-receipts.create', compact('customers', 'branches', 'costCenters', 'incomeAccounts', 'paymentMethods', 'mobileProviders', 'bankAccounts', 'itemCategories', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'receipt_date' => 'required|date',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.income_account_id' => 'required|exists:accounts,id',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:pos_payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ]);

        $receiptService = app(SalesReceiptService::class);
        $receipt = $receiptService->create([
            'company_id' => session('current_company_id'),
            'branch_id' => $request->branch_id,
            'cost_center_id' => $request->cost_center_id,
            'customer_id' => $request->customer_id,
            'receipt_date' => $request->receipt_date,
            'reference' => $request->reference,
            'memo' => $request->memo,
            'lines' => $request->lines,
            'payments' => $request->payments,
        ], auth()->id());

        return redirect()->route('accounting.sales-receipts.show', $receipt)
            ->with('success', "Sales Receipt {$receipt->receipt_number} created.");
    }

    public function show(SalesReceipt $salesReceipt)
    {
        $salesReceipt->load(['lines.product', 'payments.paymentMethod', 'customer', 'branch', 'costCenter', 'createdByUser', 'postedByUser', 'journalEntry']);
        return view('accounting.sales-receipts.show', compact('salesReceipt'));
    }

    public function post(SalesReceipt $salesReceipt)
    {
        $receiptService = app(SalesReceiptService::class);
        $receiptService->post($salesReceipt, auth()->id());
        return redirect()->route('accounting.sales-receipts.show', $salesReceipt)
            ->with('success', "Sales Receipt {$salesReceipt->receipt_number} posted and journal entry created.");
    }

    public function void(SalesReceipt $salesReceipt, Request $request)
    {
        $request->validate(['void_reason' => 'required|string']);
        $receiptService = app(SalesReceiptService::class);
        $receiptService->void($salesReceipt, $request->void_reason, auth()->id());
        return redirect()->route('accounting.sales-receipts.show', $salesReceipt)
            ->with('success', "Sales Receipt {$salesReceipt->receipt_number} voided.");
    }

    public function email(SalesReceipt $salesReceipt)
    {
        $salesReceipt->load(['lines.product', 'customer']);

        if (!$salesReceipt->customer || !$salesReceipt->customer->email) {
            return redirect()->route('accounting.sales-receipts.show', $salesReceipt)
                ->with('error', 'No customer email address found.');
        }

        $receiptMail = app(\App\Mail\SalesReceiptMail::class, ['receipt' => $salesReceipt]);
        \Illuminate\Support\Facades\Mail::to($salesReceipt->customer->email)->send($receiptMail);

        return redirect()->route('accounting.sales-receipts.show', $salesReceipt)
            ->with('success', "Sales Receipt {$salesReceipt->receipt_number} emailed to {$salesReceipt->customer->email}.");
    }

    public function print(SalesReceipt $salesReceipt)
    {
        $salesReceipt->load(['lines.product', 'payments.paymentMethod', 'customer', 'branch', 'costCenter', 'createdByUser']);
        return view('accounting.sales-receipts.print', compact('salesReceipt'));
    }
}
