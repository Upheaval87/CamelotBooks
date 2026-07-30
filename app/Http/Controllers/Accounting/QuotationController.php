<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\Accounting\QuotationService;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $quotations = Quotation::forCompany($companyId)
            ->with(['customer', 'createdByUser', 'postedByUser'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->customer_id, fn($q, $id) => $q->where('customer_id', $id))
            ->when($request->search, fn($q, $s) => $q->where(function ($q2) use ($s) {
                $q2->where('quotation_number', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$s}%"));
            }))
            ->orderBy('quotation_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('accounting.quotations.index', compact('quotations'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $customers = Customer::where('company_id', $companyId)->orderBy('name')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $incomeAccounts = Account::where('company_id', $companyId)->where('type', 'revenue')->where('is_active', true)->orderBy('code')->get();
        $itemCategories = \App\Models\ItemCategory::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        return view('accounting.quotations.create', compact('customers', 'branches', 'costCenters', 'incomeAccounts', 'itemCategories', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date|after:quotation_date',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.income_account_id' => 'required|exists:accounts,id',
        ]);

        $quotationService = app(QuotationService::class);
        $quotation = $quotationService->create([
            'company_id' => session('current_company_id'),
            'branch_id' => $request->branch_id,
            'cost_center_id' => $request->cost_center_id,
            'customer_id' => $request->customer_id,
            'quotation_date' => $request->quotation_date,
            'valid_until' => $request->valid_until,
            'reference' => $request->reference,
            'memo' => $request->memo,
            'lines' => $request->lines,
        ], auth()->id());

        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->quotation_number} created.");
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['lines.product', 'customer', 'branch', 'costCenter', 'createdByUser', 'postedByUser']);
        return view('accounting.quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        if (!$quotation->isDraft()) {
            return redirect()->route('accounting.quotations.show', $quotation)
                ->with('error', 'Only draft quotations can be edited.');
        }

        $companyId = session('current_company_id');
        $customers = Customer::where('company_id', $companyId)->orderBy('name')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $incomeAccounts = Account::where('company_id', $companyId)->where('type', 'revenue')->where('is_active', true)->orderBy('code')->get();
        $itemCategories = \App\Models\ItemCategory::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        $quotation->load('lines');

        return view('accounting.quotations.edit', compact('quotation', 'customers', 'branches', 'costCenters', 'incomeAccounts', 'itemCategories', 'products'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        if (!$quotation->isDraft()) {
            return redirect()->route('accounting.quotations.show', $quotation)
                ->with('error', 'Only draft quotations can be updated.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.income_account_id' => 'required|exists:accounts,id',
        ]);

        $quotationService = app(QuotationService::class);
        $quotation = $quotationService->update($quotation, [
            'branch_id' => $request->branch_id,
            'cost_center_id' => $request->cost_center_id,
            'customer_id' => $request->customer_id,
            'quotation_date' => $request->quotation_date,
            'valid_until' => $request->valid_until,
            'reference' => $request->reference,
            'memo' => $request->memo,
            'lines' => $request->lines,
        ]);

        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->quotation_number} updated.");
    }

    public function send(Quotation $quotation)
    {
        $quotationService = app(QuotationService::class);
        $quotationService->send($quotation);
        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->quotation_number} marked as sent.");
    }

    public function accept(Quotation $quotation)
    {
        $this->requirePermission('quotations.approve');
        $quotationService = app(QuotationService::class);
        $quotationService->accept($quotation);
        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->quotation_number} accepted.");
    }

    public function decline(Quotation $quotation)
    {
        $this->requirePermission('quotations.approve');
        $quotationService = app(QuotationService::class);
        $quotationService->decline($quotation);
        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->quotation_number} declined.");
    }

    public function convertToInvoice(Quotation $quotation)
    {
        $this->requirePermission('quotations.convert');
        $quotationService = app(QuotationService::class);
        $invoice = $quotationService->convertToInvoice($quotation, auth()->id());
        return redirect()->route('accounting.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created from Quotation {$quotation->quotation_number}.");
    }

    public function convertToSalesReceipt(Quotation $quotation, Request $request)
    {
        $this->requirePermission($request, 'quotations.convert');
        $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:pos_payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ]);

        $quotationService = app(QuotationService::class);
        $receipt = $quotationService->convertToSalesReceipt($quotation, [
            'payments' => $request->payments,
        ], auth()->id());

        return redirect()->route('accounting.sales-receipts.show', $receipt)
            ->with('success', "Sales Receipt {$receipt->receipt_number} created from Quotation {$quotation->quotation_number}.");
    }

    public function void(Quotation $quotation, Request $request)
    {
        $this->requirePermission($request, 'quotations.void');
        $request->validate(['void_reason' => 'required|string']);
        $quotationService = app(QuotationService::class);
        $quotationService->void($quotation, $request->void_reason, auth()->id());
        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->quotation_number} voided.");
    }

    public function email(Quotation $quotation)
    {
        $quotation->load(['lines.product', 'customer']);

        $quotationMail = app(\App\Mail\QuotationMail::class, ['quotation' => $quotation]);
        \Illuminate\Support\Facades\Mail::to($quotation->customer->email)->send($quotationMail);

        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->quotation_number} emailed to {$quotation->customer->email}.");
    }

    public function print(Quotation $quotation)
    {
        $quotation->load(['lines.product', 'customer', 'branch', 'costCenter', 'createdByUser']);
        return view('accounting.quotations.print', compact('quotation'));
    }
}
