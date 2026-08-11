<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\PosPaymentMethod;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\Accounting\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $sort = $request->input('sort', 'date-desc');

        $orders = $this->baseQuery($request);
        foreach ($this->orderByFor($sort) as $column => $direction) {
            $orders->orderBy($column, $direction);
        }
        $orders = $orders->paginate(20);

        $stats = SalesOrder::forCompany($companyId)
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(total), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $statsTotal = SalesOrder::forCompany($companyId)->count();

        return view('accounting.sales-orders.index', compact('orders', 'stats', 'statsTotal', 'sort'));
    }

    private function baseQuery(Request $request)
    {
        $companyId = session('current_company_id');

        return SalesOrder::forCompany($companyId)
            ->with(['customer', 'createdByUser', 'postedByUser'])
            ->when($request->status === 'open', fn($q) => $q->whereIn('status', [SalesOrder::STATUS_DRAFT, SalesOrder::STATUS_SENT]))
            ->when($request->status && $request->status !== 'open', fn($q, $s) => $q->where('status', $s))
            ->when($request->customer_id, fn($q, $id) => $q->where('customer_id', $id))
            ->when($request->search, fn($q, $s) => $q->where(function ($q2) use ($s) {
                $q2->where('sales_order_number', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$s}%"));
            }));
    }

    private function orderByFor(string $sort): array
    {
        return match ($sort) {
            'date-asc' => ['order_date' => 'asc', 'id' => 'asc'],
            'amount-desc' => ['total' => 'desc', 'order_date' => 'desc'],
            'amount-asc' => ['total' => 'asc', 'order_date' => 'asc'],
            'status' => ['status' => 'asc', 'order_date' => 'desc'],
            default => ['order_date' => 'desc', 'id' => 'desc'],
        };
    }

    public function export(Request $request)
    {
        $orders = $this->baseQuery($request);
        foreach ($this->orderByFor($request->input('sort', 'date-desc')) as $column => $direction) {
            $orders->orderBy($column, $direction);
        }
        $orders = $orders->get();

        $filename = 'sales-orders-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Sales Order #', 'Customer', 'Order Date', 'Expected Delivery', 'Total', 'Status']);
            foreach ($orders as $o) {
                fputcsv($out, [
                    $o->sales_order_number,
                    $o->customer->name ?? '',
                    $o->order_date?->format('Y-m-d') ?? '',
                    $o->expected_delivery_date?->format('Y-m-d') ?? '',
                    number_format((float) $o->total, 2, '.', ''),
                    $o->status,
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
        $products = Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::query()->active()->ordered()->get();
        $defaultIncomeAccountId = $incomeAccounts->first()?->id;

        return view('accounting.sales-orders.create', compact('customers', 'branches', 'costCenters', 'incomeAccounts', 'products', 'currencies', 'defaultIncomeAccountId'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'currency' => 'nullable|string|max:10',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.income_account_id' => 'required|exists:accounts,id',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx,doc,docx,txt,csv|max:10240',
            'delete_documents' => 'nullable|array',
            'delete_documents.*' => 'integer',
        ]);

        $order = app(SalesOrderService::class)->create([
            'company_id' => $companyId,
            'branch_id' => $request->branch_id,
            'cost_center_id' => $request->cost_center_id,
            'customer_id' => $request->customer_id,
            'order_date' => $request->order_date,
            'expected_delivery_date' => $request->expected_delivery_date,
            'reference' => $request->reference,
            'memo' => $request->memo,
            'currency' => $request->currency,
            'lines' => $request->lines,
        ], auth()->id());

        $this->handleAttachments($request, $order);

        if ($request->input('action') === 'save_and_new') {
            return redirect()->route('accounting.sales-orders.create')
                ->with('success', "Sales Order {$order->sales_order_number} saved. You can add another.");
        }

        $submitted = $this->handlePostSaveAction($request, $order);

        return redirect()->route('accounting.sales-orders.show', $order)
            ->with('success', $submitted
                ? "Sales Order {$order->sales_order_number} created and sent to the customer."
                : "Sales Order {$order->sales_order_number} created.");
    }

    public function destroy(SalesOrder $order)
    {
        $this->requirePermission('sales-orders.edit');
        abort_unless((int) $order->company_id === (int) session('current_company_id'), 403);

        if (!$order->isDraft()) {
            return redirect()->route('accounting.sales-orders.show', $order)
                ->with('error', 'Only draft sales orders can be deleted.');
        }

        foreach ($order->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }
        $order->attachments()->delete();

        app(SalesOrderService::class)->destroy($order);

        return redirect()->route('accounting.sales-orders.index')
            ->with('success', "Sales Order {$order->sales_order_number} deleted.");
    }

    public function show(SalesOrder $order)
    {
        $order->load(['lines.product', 'customer', 'branch', 'costCenter', 'createdByUser', 'postedByUser', 'voidedByUser', 'attachments', 'convertedInvoice', 'convertedReceipt']);
        $paymentMethods = PosPaymentMethod::where('company_id', session('current_company_id'))->where('is_active', true)->orderBy('name')->get();
        return view('accounting.sales-orders.show', compact('order', 'paymentMethods'));
    }

    public function edit(SalesOrder $order)
    {
        if (!$order->isDraft()) {
            return redirect()->route('accounting.sales-orders.show', $order)
                ->with('error', 'Only draft sales orders can be edited.');
        }

        $companyId = session('current_company_id');
        $customers = Customer::where('company_id', $companyId)->orderBy('name')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $incomeAccounts = Account::where('company_id', $companyId)->where('type', 'revenue')->where('is_active', true)->orderBy('code')->get();
        $products = Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::query()->active()->ordered()->get();
        $defaultIncomeAccountId = $incomeAccounts->first()?->id;

        $order->load(['lines', 'attachments']);

        return view('accounting.sales-orders.edit', compact('order', 'customers', 'branches', 'costCenters', 'incomeAccounts', 'products', 'currencies', 'defaultIncomeAccountId'));
    }

    public function update(Request $request, SalesOrder $order)
    {
        if (!$order->isDraft()) {
            return redirect()->route('accounting.sales-orders.show', $order)
                ->with('error', 'Only draft sales orders can be updated.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'currency' => 'nullable|string|max:10',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.income_account_id' => 'required|exists:accounts,id',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx,doc,docx,txt,csv|max:10240',
            'delete_documents' => 'nullable|array',
            'delete_documents.*' => 'integer',
        ]);

        $order = app(SalesOrderService::class)->update($order, [
            'branch_id' => $request->branch_id,
            'cost_center_id' => $request->cost_center_id,
            'customer_id' => $request->customer_id,
            'order_date' => $request->order_date,
            'expected_delivery_date' => $request->expected_delivery_date,
            'reference' => $request->reference,
            'memo' => $request->memo,
            'currency' => $request->currency,
            'lines' => $request->lines,
        ]);

        $this->handleAttachments($request, $order);

        $submitted = $this->handlePostSaveAction($request, $order);

        return redirect()->route('accounting.sales-orders.show', $order)
            ->with('success', $submitted
                ? "Sales Order {$order->sales_order_number} updated and sent to the customer."
                : "Sales Order {$order->sales_order_number} updated.");
    }

    private function handlePostSaveAction(Request $request, SalesOrder $order): bool
    {
        if ($request->input('action') !== 'send') {
            return false;
        }

        app(SalesOrderService::class)->send($order);

        return true;
    }

    private function handleAttachments(Request $request, SalesOrder $order): void
    {
        $companyId = $order->company_id;

        foreach ((array) $request->input('delete_documents', []) as $id) {
            $attachment = Attachment::where('company_id', $companyId)
                ->where('id', (int) $id)
                ->where('attachmentable_type', SalesOrder::class)
                ->where('attachmentable_id', $order->id)
                ->first();

            if ($attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            }
        }

        foreach ($request->file('files', []) as $file) {
            $path = $file->storeAs(
                "sales-order-attachments/{$companyId}/{$order->id}",
                Str::random(24) . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            $order->attachments()->create([
                'company_id' => $companyId,
                'name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    public function send(SalesOrder $order)
    {
        app(SalesOrderService::class)->send($order);
        return redirect()->route('accounting.sales-orders.show', $order)
            ->with('success', "Sales Order {$order->sales_order_number} marked as sent.");
    }

    public function confirm(SalesOrder $order)
    {
        $this->requirePermission('sales-orders.confirm');
        app(SalesOrderService::class)->confirm($order);
        return redirect()->route('accounting.sales-orders.show', $order)
            ->with('success', "Sales Order {$order->sales_order_number} confirmed.");
    }

    public function markFulfilled(SalesOrder $order)
    {
        $this->requirePermission('sales-orders.convert');
        app(SalesOrderService::class)->markFulfilled($order);
        return redirect()->route('accounting.sales-orders.show', $order)
            ->with('success', "Sales Order {$order->sales_order_number} marked as fulfilled.");
    }

    public function cancel(SalesOrder $order)
    {
        $this->requirePermission('sales-orders.cancel');
        app(SalesOrderService::class)->cancel($order);
        return redirect()->route('accounting.sales-orders.show', $order)
            ->with('success', "Sales Order {$order->sales_order_number} cancelled.");
    }

    public function convertToInvoice(SalesOrder $order)
    {
        $this->requirePermission('sales-orders.convert');
        $invoice = app(SalesOrderService::class)->convertToInvoice($order, auth()->id());
        return redirect()->route('accounting.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created from Sales Order {$order->sales_order_number}.");
    }

    public function convertToSalesReceipt(SalesOrder $order, Request $request)
    {
        $this->requirePermission($request, 'sales-orders.convert');
        $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:pos_payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ]);

        $receipt = app(SalesOrderService::class)->convertToSalesReceipt($order, [
            'payments' => $request->payments,
        ], auth()->id());

        return redirect()->route('accounting.sales-receipts.show', $receipt)
            ->with('success', "Sales Receipt {$receipt->receipt_number} created from Sales Order {$order->sales_order_number}.");
    }

    public function void(SalesOrder $order, Request $request)
    {
        $this->requirePermission($request, 'sales-orders.void');
        $request->validate(['void_reason' => 'required|string']);
        app(SalesOrderService::class)->void($order, $request->void_reason, auth()->id());
        return redirect()->route('accounting.sales-orders.show', $order)
            ->with('success', "Sales Order {$order->sales_order_number} voided.");
    }

    public function print(SalesOrder $order)
    {
        $order->load(['lines.product', 'customer', 'branch', 'costCenter', 'createdByUser']);
        return view('accounting.sales-orders.print', compact('order'));
    }
}
