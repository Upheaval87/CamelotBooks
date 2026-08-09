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
        $sort = $request->input('sort', 'date-desc');

        $receipts = $this->baseQuery($request);
        foreach ($this->orderByFor($sort) as $column => $direction) {
            $receipts->orderBy($column, $direction);
        }
        $receipts = $receipts->paginate(20);

        $stats = SalesReceipt::forCompany($companyId)
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(total), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $statsTotal = SalesReceipt::forCompany($companyId)->count();

        return view('accounting.sales-receipts.index', compact('receipts', 'stats', 'statsTotal', 'sort'));
    }

    /**
     * Shared filtered query for the list and the CSV export.
     * Keeps the exact search/status/customer params used by the list page.
     */
    private function baseQuery(Request $request)
    {
        $companyId = session('current_company_id');

        return SalesReceipt::forCompany($companyId)
            ->with(['customer', 'createdByUser', 'postedByUser', 'payments.paymentMethod'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->customer_id, fn($q, $id) => $q->where('customer_id', $id))
            ->when($request->search, fn($q, $s) => $q->where(function ($q2) use ($s) {
                $q2->where('receipt_number', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$s}%"));
            }));
    }

    private function orderByFor(string $sort): array
    {
        return match ($sort) {
            'date-asc' => ['receipt_date' => 'asc', 'id' => 'asc'],
            'amount-desc' => ['total' => 'desc', 'receipt_date' => 'desc'],
            'amount-asc' => ['total' => 'asc', 'receipt_date' => 'asc'],
            'status' => ['status' => 'asc', 'receipt_date' => 'desc'],
            default => ['receipt_date' => 'desc', 'id' => 'desc'],
        };
    }

    public function export(Request $request)
    {
        $receipts = $this->baseQuery($request);
        foreach ($this->orderByFor($request->input('sort', 'date-desc')) as $column => $direction) {
            $receipts->orderBy($column, $direction);
        }
        $receipts = $receipts->get();

        $filename = 'sales-receipts-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($receipts) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Receipt #', 'Customer', 'Date', 'Method', 'Total', 'Status']);
            foreach ($receipts as $r) {
                fputcsv($out, [
                    $r->receipt_number,
                    $r->customer->name ?? 'Walk-in',
                    $r->receipt_date?->format('Y-m-d') ?? '',
                    $r->payments->first()?->paymentMethod?->name ?? '',
                    number_format((float) $r->total, 2, '.', ''),
                    $r->status,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
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
            'currency' => $request->currency,
            'lines' => $request->lines,
            'payments' => $request->payments,
        ], auth()->id());

        if ($request->input('action') === 'save_and_post') {
            return redirect()->route('accounting.sales-receipts.post-page', $receipt)
                ->with('success', "Sales Receipt {$receipt->receipt_number} saved. Review and post to the ledger.");
        }

        if ($request->input('action') === 'save_and_new') {
            return redirect()->route('accounting.sales-receipts.create')
                ->with('success', "Sales Receipt {$receipt->receipt_number} saved. You can add another.");
        }

        return redirect()->route('accounting.sales-receipts.show', $receipt)
            ->with('success', "Sales Receipt {$receipt->receipt_number} created.");
    }

    public function show(SalesReceipt $salesReceipt)
    {
        $salesReceipt->load(['lines.product', 'payments.paymentMethod', 'customer', 'branch', 'costCenter', 'createdByUser', 'postedByUser', 'journalEntry']);
        return view('accounting.sales-receipts.show', compact('salesReceipt'));
    }

    public function edit(SalesReceipt $salesReceipt)
    {
        if (!$salesReceipt->isDraft()) {
            return redirect()->route('accounting.sales-receipts.show', $salesReceipt)
                ->with('error', 'Only draft receipts can be edited.');
        }

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

        $salesReceipt->load(['lines.product', 'payments.paymentMethod']);

        return view('accounting.sales-receipts.edit', compact('salesReceipt', 'customers', 'branches', 'costCenters', 'incomeAccounts', 'paymentMethods', 'mobileProviders', 'bankAccounts', 'itemCategories', 'products'));
    }

    public function update(Request $request, SalesReceipt $salesReceipt)
    {
        if (!$salesReceipt->isDraft()) {
            return redirect()->route('accounting.sales-receipts.show', $salesReceipt)
                ->with('error', 'Only draft receipts can be updated.');
        }

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
        $receipt = $receiptService->update($salesReceipt, [
            'branch_id' => $request->branch_id,
            'cost_center_id' => $request->cost_center_id,
            'customer_id' => $request->customer_id,
            'receipt_date' => $request->receipt_date,
            'reference' => $request->reference,
            'memo' => $request->memo,
            'currency' => $request->currency,
            'lines' => $request->lines,
            'payments' => $request->payments,
        ], auth()->id());

        if ($request->input('action') === 'save_and_post') {
            return redirect()->route('accounting.sales-receipts.post-page', $receipt)
                ->with('success', "Sales Receipt {$receipt->receipt_number} updated. Review and post to the ledger.");
        }

        return redirect()->route('accounting.sales-receipts.show', $receipt)
            ->with('success', "Sales Receipt {$receipt->receipt_number} updated.");
    }

    public function destroy(SalesReceipt $salesReceipt)
    {
        $this->requirePermission('sales-receipts.edit');
        abort_unless((int) $salesReceipt->company_id === (int) session('current_company_id'), 403);

        if (!$salesReceipt->isDraft()) {
            return redirect()->route('accounting.sales-receipts.show', $salesReceipt)
                ->with('error', 'Only draft receipts can be deleted.');
        }

        app(SalesReceiptService::class)->destroy($salesReceipt, auth()->id());

        return redirect()->route('accounting.sales-receipts.index')
            ->with('success', "Sales Receipt {$salesReceipt->receipt_number} deleted.");
    }

    /**
     * Post-page: journal preview + posting summary before committing to the ledger.
     * The preview is built from the same context + builder the actual posting uses.
     */
    public function postPage(SalesReceipt $salesReceipt)
    {
        $this->requirePermission('sales-receipts.post');

        if (!$salesReceipt->isDraft()) {
            return redirect()->route('accounting.sales-receipts.show', $salesReceipt);
        }

        $salesReceipt->load(['lines.product', 'payments.paymentMethod', 'customer', 'branch', 'costCenter', 'createdByUser']);

        $context = app(SalesReceiptService::class)->buildPostContext($salesReceipt);
        $jeLines = app(\App\Services\Accounting\SalesPostingService::class)->buildSaleLines([
            'company_id' => $salesReceipt->company_id,
            'source_module' => 'sales_receipt',
            'document_number' => $salesReceipt->receipt_number,
            'date' => $salesReceipt->receipt_date->format('Y-m-d'),
            'memo' => $salesReceipt->memo ?? "Sales Receipt {$salesReceipt->receipt_number}",
            'lines' => $context['lines'],
            'payments' => $context['payments'],
        ]);

        $accountIds = array_unique(array_map(fn($jl) => $jl['account_id'], $jeLines));
        $accounts = Account::whereIn('id', $accountIds)->get()->keyBy('id');

        $totalDebits = array_sum(array_map(fn($jl) => $jl['debit'], $jeLines));
        $totalCredits = array_sum(array_map(fn($jl) => $jl['credit'], $jeLines));

        return view('accounting.sales-receipts.post-page', compact('salesReceipt', 'jeLines', 'accounts', 'totalDebits', 'totalCredits'));
    }

    public function post(SalesReceipt $salesReceipt)
    {
        $this->requirePermission('sales-receipts.post');
        $receiptService = app(SalesReceiptService::class);
        $receiptService->post($salesReceipt, auth()->id());
        return redirect()->route('accounting.sales-receipts.show', $salesReceipt)
            ->with('success', "Sales Receipt {$salesReceipt->receipt_number} posted and journal entry created.");
    }

    public function void(SalesReceipt $salesReceipt, Request $request)
    {
        $this->requirePermission($request, 'sales-receipts.void');
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
        \Illuminate\Support\Facades\Mail::to($salesReceipt->customer->email)->queue($receiptMail);

        return redirect()->route('accounting.sales-receipts.show', $salesReceipt)
            ->with('success', "Sales Receipt {$salesReceipt->receipt_number} emailed to {$salesReceipt->customer->email}.");
    }

    public function print(SalesReceipt $salesReceipt)
    {
        $salesReceipt->load(['lines.product', 'payments.paymentMethod', 'customer', 'branch', 'costCenter', 'createdByUser']);
        return view('accounting.sales-receipts.print', compact('salesReceipt'));
    }
}
