<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\OrderFulfilment;
use App\Models\PosPaymentMethod;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SystemSetting;
use App\Services\Accounting\SalesOrderService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * SALES ORDERS MODULE — §5.2 sanctioned endpoints.
 *
 * Every lifecycle verb delegates to SalesOrderService so the status machine,
 * stock ledger and audit trail live in exactly one place; this class owns HTTP
 * concerns only (validation, permissions, scoping and redirects).
 *
 * Spec field names are reconciled onto the live schema in
 * 2026_12_08_000001_extend_sales_orders_for_full_module.php:
 * warehouse_id → branch_id, cost_centre → cost_center_id, subtotal → amount,
 * grand_total → total, notes_customer → memo, delivery_date →
 * expected_delivery_date, invoice_id → converted_invoice_id.
 */
class SalesOrderController extends Controller
{
    // ── Register ───────────────────────────────────────────────────────────

    /** §5.2 · GET /sales-orders (q, status, view, sort, page) */
    public function index(Request $request)
    {
        $this->requirePermission($request, 'sales-orders.view');

        $companyId = session('current_company_id');
        $sort = (string) $request->input('sort', 'date-desc');

        $orders = $this->baseQuery($request);
        foreach ($this->orderByFor($sort) as $column => $direction) {
            $orders->orderBy($column, $direction);
        }

        $total = (clone $orders)->toBase()->getCountForPagination();
        $orders = $orders->paginate(20)->withQueryString();

        // Status tiles (§4 register): one grouped query feeds every tile and the
        // Views rail counts, so the numbers can never disagree with the table.
        $stats = SalesOrder::forCompany($companyId)
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(total), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $statsTotal = (int) $stats->sum('total');

        $viewCounts = [
            'all' => $statsTotal,
            'open' => (int) SalesOrder::forCompany($companyId)->whereIn('status', SalesOrder::OPEN_STATUSES)->count(),
            'unfulfilled' => (int) SalesOrder::forCompany($companyId)
                ->whereIn('status', [SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_SENT])
                ->count(),
            'fulfilled' => (int) ($stats[SalesOrder::STATUS_FULFILLED]->total ?? 0),
            'attention' => (int) SalesOrder::forCompany($companyId)
                ->where('status', SalesOrder::STATUS_CONFIRMED)
                ->where(function ($q) {
                    $q->whereNull('delivered_on')->orWhere('delivered_on', '<', now()->subDays(7)->toDateString());
                })
                ->count(),
        ];

        $currencies = Currency::query()->active()->ordered()->get();

        return view('accounting.sales-orders.index', compact(
            'orders', 'stats', 'statsTotal', 'sort', 'viewCounts', 'currencies'
        ) + ['total' => $total]);
    }

    private function baseQuery(Request $request)
    {
        $companyId = session('current_company_id');
        $status = $request->input('status');
        $view = $request->input('view');

        $query = SalesOrder::forCompany($companyId)
            ->with(['customer', 'createdByUser', 'lines'])
            // Capture the value: when() passes the CONDITION (true) to the callback,
            // so `$status` must be captured explicitly, never taken from the
            // callback argument. (Guarded by QuotationFormTest::test_sales_order_status_filter_actually_filters.)
            ->when($status === 'open', fn ($q) => $q->whereIn('status', SalesOrder::OPEN_STATUSES))
            ->when($status && $status !== 'open', fn ($q) => $q->where('status', $status))
            ->when($request->customer_id, fn ($q, $id) => $q->where('customer_id', $id))
            ->when($request->currency, fn ($q, $code) => $q->where('currency', $code))
            // Views rail presets (§4).
            ->when($view === 'unfulfilled' && ! $status, fn ($q) => $q->whereIn('status', [SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_SENT]))
            ->when($view === 'fulfilled' && ! $status, fn ($q) => $q->where('status', SalesOrder::STATUS_FULFILLED))
            ->when($view === 'attention' && ! $status, fn ($q) => $q->where('status', SalesOrder::STATUS_CONFIRMED)->whereNull('delivered_on'))
            ->when($request->q, function ($q, $term) {
                $q->where(function ($q2) use ($term) {
                    $q2->where('sales_order_number', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
                });
            })
            // Legacy alias kept so the old `?search=` links keep working.
            ->when(! $request->q && $request->search, function ($q, $term) {
                $q->where(function ($q2) use ($term) {
                    $q2->where('sales_order_number', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
                });
            });

        return $query;
    }

    /** @return array<string,string> */
    private function orderByFor(string $sort): array
    {
        return match ($sort) {
            'date-asc' => ['order_date' => 'asc', 'id' => 'asc'],
            'amount-desc' => ['total' => 'desc', 'order_date' => 'desc'],
            'amount-asc' => ['total' => 'asc', 'order_date' => 'asc'],
            'status' => ['status' => 'asc', 'order_date' => 'desc'],
            'number-asc' => ['sales_order_number' => 'asc'],
            'number-desc' => ['sales_order_number' => 'desc'],
            'delivery-asc' => ['expected_delivery_date' => 'asc'],
            'delivery-desc' => ['expected_delivery_date' => 'desc'],
            default => ['order_date' => 'desc', 'id' => 'desc'],
        };
    }

    /** §6 · export. */
    public function export(Request $request)
    {
        $this->requirePermission($request, 'sales-orders.export');

        $orders = $this->baseQuery($request);
        foreach ($this->orderByFor((string) $request->input('sort', 'date-desc')) as $column => $direction) {
            $orders->orderBy($column, $direction);
        }
        $orders = $orders->get();

        $filename = 'sales-orders-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Sales Order #', 'Customer', 'Order Date', 'Expected Delivery',
                'Delivery Method', 'Subtotal', 'Tax', 'Total', 'Status',
                'Fulfilled', 'Delivery Note', 'Invoiced',
            ]);

            foreach ($orders as $o) {
                fputcsv($out, [
                    $o->sales_order_number,
                    $o->customer_name ?: ($o->customer->name ?? ''),
                    $o->order_date?->format('Y-m-d') ?? '',
                    $o->expected_delivery_date?->format('Y-m-d') ?? '',
                    $o->delivery_method ?? '',
                    number_format((float) $o->amount, 2, '.', ''),
                    number_format((float) $o->tax_total, 2, '.', ''),
                    number_format((float) $o->total, 2, '.', ''),
                    $o->statusLabel(),
                    $o->delivered_on?->format('Y-m-d') ?? '',
                    $o->delivery_note_no ?? '',
                    $o->converted_invoice_id ? 'yes' : 'no',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ── Create / edit ──────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $this->requirePermission($request, 'sales-orders.create');

        return view('accounting.sales-orders.create', $this->formData() + [
            'sourceQuotation' => $this->quotationSource($request),
            'preselectCustomerId' => (int) $request->input('customer_id') ?: null,
        ]);
    }

    public function edit(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.edit');

        // §5.2 · a PUT on a non-draft is a 409, not a silent redirect.
        abort_unless($order->isDraft(), 409, 'Only draft sales orders can be edited.');

        $order->load(['lines.product', 'attachments']);

        return view('accounting.sales-orders.edit', $this->formData() + [
            'order' => $order,
            'sourceQuotation' => $order->sourceQuotation,
        ]);
    }

    /** @return array<string,mixed> */
    private function formData(): array
    {
        $companyId = session('current_company_id');

        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $incomeAccounts = Account::where('company_id', $companyId)->where('type', 'revenue')->where('is_active', true)->orderBy('code')->get();
        $products = Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::query()->active()->ordered()->get();

        return [
            'branches' => $branches,
            'costCenters' => $costCenters,
            'incomeAccounts' => $incomeAccounts,
            'products' => $products,
            'currencies' => $currencies,
            'defaultIncomeAccountId' => $incomeAccounts->first()?->id,
            // Walk-in capture needs the full active registry in client mode so a
            // typed name can be matched or created inline (R10).
            'customers' => Customer::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /** §R10 · accept `?quotation=` and pre-fill from the converted quotation. */
    private function quotationSource(Request $request)
    {
        $id = (int) $request->input('quotation');

        if (! $id || ! class_exists(\App\Models\Quotation::class)) {
            return null;
        }

        return \App\Models\Quotation::where('company_id', session('current_company_id'))
            ->with('lines')
            ->find($id);
    }

    // ── Persist ────────────────────────────────────────────────────────────

    /** §5.2 · POST /sales-orders {mode: draft|release, …} */
    public function store(Request $request)
    {
        $this->requirePermission($request, 'sales-orders.create');

        $mode = $request->input('mode') === 'release'
            ? SalesOrderService::MODE_RELEASE
            : SalesOrderService::MODE_DRAFT;

        $payload = $this->validatedPayload($request, true);

        if ($mode === SalesOrderService::MODE_RELEASE) {
            // R9 · the client disables Release until the check passes; the
            // server repeats it and reports the same gap list as errors.
            $gaps = app(SalesOrderService::class)->releaseGaps($payload);

            if ($gaps) {
                return back()
                    ->withInput()
                    ->withErrors(['mode' => 'Release needs: ' . implode(', ', $gaps) . '.']);
            }
        }

        [$order, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)->create(
            $payload,
            auth()->id(),
            $mode === SalesOrderService::MODE_RELEASE
                ? $mode
                : SalesOrderService::MODE_DRAFT
        ));

        if ($refusal) {
            return $refusal;
        }

        $this->handleAttachments($request, $order);

        if ($mode === SalesOrderService::MODE_RELEASE && $request->filled('email')) {
            app(SalesOrderService::class)->recordEmailed($order, (string) $request->email, auth()->id(), 'Acknowledgement queued with release.');
        }

        if ($request->input('action') === 'save_and_new') {
            return redirect()->route('accounting.sales-orders.create')
                ->with('success', "Sales Order {$order->sales_order_number} saved as a draft.");
        }

        return redirect()->route('accounting.sales-orders.show', $order)
            ->with('success', $mode === SalesOrderService::MODE_RELEASE
                ? "Sales Order {$order->sales_order_number} released to the customer."
                : "Sales Order {$order->sales_order_number} saved as a draft.");
    }

    /** §5.2 · PUT /sales-orders/{id} (409 unless draft) */
    public function update(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.edit_draft');

        abort_unless($order->isDraft(), 409, 'Only draft sales orders can be updated.');

        $payload = $this->validatedPayload($request, false);
        $mode = $request->input('mode') === 'release'
            ? SalesOrderService::MODE_RELEASE
            : SalesOrderService::MODE_DRAFT;

        if ($mode === SalesOrderService::MODE_RELEASE) {
            $gaps = app(SalesOrderService::class)->releaseGaps($payload + ['company_id' => $order->company_id]);

            if ($gaps) {
                return back()
                    ->withInput()
                    ->withErrors(['mode' => 'Release needs: ' . implode(', ', $gaps) . '.']);
            }
        }

        [$order, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)
            ->update($order, $payload, auth()->id(), $mode));

        if ($refusal) {
            return $refusal;
        }

        $this->handleAttachments($request, $order);

        if ($mode === SalesOrderService::MODE_RELEASE && $request->filled('email')) {
            app(SalesOrderService::class)->recordEmailed($order, (string) $request->email, auth()->id(), 'Acknowledgement queued on update-and-release.');
        }

        return redirect()->route('accounting.sales-orders.show', $order)
            ->with('success', $mode === SalesOrderService::MODE_RELEASE
                ? "Sales Order {$order->sales_order_number} updated and released."
                : "Sales Order {$order->sales_order_number} updated.");
    }

    /**
     * Shared create/update validation.
     *
     * `description` is nullable by design: §8.3 / R9 require a blank
     * description to still count as a complete line, so it is NOT `required`.
     */
    private function validatedPayload(Request $request, bool $creating): array
    {
        $companyId = session('current_company_id');

        $validator = Validator::make($request->all(), [
            // Customer may arrive as a registry id, or as a walk-in name to
            // materialise (R10).
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'customer_type' => 'nullable|in:existing,walkin,walk_in',
            'customer_name' => 'nullable|string|max:200',
            'contact_person' => 'nullable|string|max:150',
            'email' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'delivery_address' => 'nullable|string|max:1000',
            'delivery_method' => 'nullable|string|max:40',
            'department' => 'nullable|string|max:120',
            'prepared_by' => 'nullable|string|max:150',
            'terms_key' => 'nullable|string|max:60',
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'cost_center_id' => ['nullable', 'integer', Rule::exists('cost_centers', 'id')->where('company_id', $companyId)],
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'reference' => 'nullable|string|max:100',
            'memo' => 'nullable|string|max:5000',
            'notes_internal' => 'nullable|string|max:5000',
            'currency' => 'nullable|string|max:10',
            'mode' => 'nullable|in:draft,release',

            'lines' => 'required|array|min:1',
            'lines.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'lines.*.item_code' => 'nullable|string|max:60',
            'lines.*.item_name' => 'nullable|string|max:200',
            // R9 · blank description still counts complete.
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.income_account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'lines.*.cost_center_id' => ['nullable', 'integer', Rule::exists('cost_centers', 'id')->where('company_id', $companyId)],

            'files' => 'nullable|array|max:10',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx,doc,docx,txt,csv|max:10240',
            'delete_documents' => 'nullable|array',
            'delete_documents.*' => 'integer',
        ], [
            'lines.*.quantity.required' => 'Enter a quantity on at least one line.',
            'lines.*.unit_price.required' => 'Enter a unit price on at least one line.',
            'lines.*.income_account_id.required' => 'Choose an income account for every line.',
        ]);

        // Cross-field rules the declarative set cannot express: the release
        // gate (R9) is the important one, and it must surface as ordinary
        // validation errors rather than a 500.
        $data = $validator->validate();

        if (! ($data['customer_id'] ?? null) && ! trim((string) ($data['customer_name'] ?? ''))) {
            // R10 · neither a registry match nor a typed name → reject, the
            // service would otherwise have nothing to resolve.
            $validator->errors()->add('customer_id', 'Select a customer from the registry, or type a walk-in name.');

            throw new ValidationException($validator);
        }

        $payload = [
            'customer_id' => $data['customer_id'] ?? null,
            'customer_type' => $data['customer_type'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'delivery_address' => $data['delivery_address'] ?? null,
            'delivery_method' => $data['delivery_method'] ?? null,
            'department' => $data['department'] ?? null,
            'prepared_by' => $data['prepared_by'] ?? null,
            'terms_key' => $data['terms_key'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'cost_center_id' => $data['cost_center_id'] ?? null,
            'order_date' => $data['order_date'],
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'reference' => $data['reference'] ?? null,
            'memo' => $data['memo'] ?? null,
            'notes_internal' => $data['notes_internal'] ?? null,
            'currency' => $data['currency'] ?? null,
            'lines' => $data['lines'],
        ];

        if ($creating) {
            $payload['company_id'] = $companyId;
        }

        return $payload;
    }

    /** Attachments are a pre-existing feature; keep the storage contract identical. */
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

    // ── View ───────────────────────────────────────────────────────────────

    /** §5.2 · GET /sales-orders/{id} */
    public function show(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.view');

        abort_unless((int) $order->company_id === (int) session('current_company_id'), 403);

        $order->load([
            'lines.product', 'customer', 'branch', 'costCenter', 'createdByUser',
            'postedByUser', 'voidedByUser', 'attachments', 'convertedInvoice',
            'convertedReceipt', 'fulfilments.createdByUser',
        ]);

        $paymentMethods = PosPaymentMethod::where('company_id', session('current_company_id'))
            ->where('is_active', true)->orderBy('name')->get();

        $viewData = [
            'order' => $order,
            'paymentMethods' => $paymentMethods,
            'currencies' => Currency::query()->active()->ordered()->get(),
            'fulfilmentLines' => $this->fulfilmentRows($order),
            'cs' => (string) SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'),
            'can' => [
                'edit' => $this->canDo('sales-orders.edit') && $order->isDraft(),
                'release' => $this->canDo('sales-orders.release') && $order->isDraft(),
                'confirm' => $this->canDo('sales-orders.confirm') && $order->status === SalesOrder::STATUS_SENT,
                'fulfil' => $this->canDo('sales-orders.fulfil') && $order->status === SalesOrder::STATUS_CONFIRMED,
                'invoice' => $this->canDo('sales-orders.invoice') && $order->status === SalesOrder::STATUS_FULFILLED,
                'send_email' => $this->canDo('sales-orders.send_email') && $order->status !== SalesOrder::STATUS_DRAFT,
                'print_pdf' => $this->canDo('sales-orders.print_pdf'),
                'cancel' => $this->canDo('sales-orders.cancel') && $order->isOpen(),
                'void' => $this->canDo('sales-orders.void') && $order->isDraft(),
            ],
        ];

        if ($request->boolean('modal')) {
            $response = response()->view('accounting.sales-orders.partials.view-modal', $viewData);

            return $response;
        }

        return view('accounting.sales-orders.show', $viewData);
    }

    /**
     * Fulfilment rows for the fulfil modal (§4): one row per line with the
     * remaining quantity and the `max` attribute §7.3 requires on the input.
     */
    private function fulfilmentRows(SalesOrder $order): array
    {
        return $order->lines->map(fn ($line) => [
            'id' => $line->id,
            'label' => $line->label(),
            'sku' => $line->item_code,
            'ordered' => (float) $line->quantity,
            'fulfilled' => (float) $line->qty_fulfilled,
            'remaining' => (float) $line->remaining(),
            'max' => (float) $line->remaining(),
        ])->values()->all();
    }

    // ── Lifecycle verbs ────────────────────────────────────────────────────

    /** §5.2 · POST /sales-orders/{id}/release */
    public function release(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.release');

        $request->validate(['email' => 'nullable|string|max:150']);

        [$order, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)->release($order, auth()->id(), [
            'auto_email' => $request->boolean('auto_email'),
            'email' => $request->input('email'),
        ]));

        if ($refusal) {
            return $refusal;
        }

        if ($request->filled('email')) {
            app(SalesOrderService::class)->recordEmailed($order, (string) $request->email, auth()->id());
        }

        return back()->with('success', "Sales Order {$order->sales_order_number} released.");
    }

    /** Legacy verb kept for the pre-existing `send` route + bookmarks. */
    public function send(Request $request, SalesOrder $order)
    {
        return $this->release($request, $order);
    }

    /** §5.2 · POST /sales-orders/{id}/confirm */
    public function confirm(SalesOrder $order)
    {
        $this->requirePermission(request(), 'sales-orders.confirm');

        [$order, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)->confirm($order, auth()->id()));

        if ($refusal) {
            return $refusal;
        }

        return back()->with('success', "Sales Order {$order->sales_order_number} confirmed and stock reserved.");
    }

    /** §5.2 · POST /sales-orders/{id}/cancel */
    public function cancel(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.cancel');

        $request->validate(['reason' => 'nullable|string|max:1000']);

        [$order, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)
            ->cancel($order, $request->input('reason'), auth()->id()));

        if ($refusal) {
            return $refusal;
        }

        return back()->with('success', "Sales Order {$order->sales_order_number} cancelled. The document is retained.");
    }

    /** §5.2 · POST /sales-orders/{id}/void */
    public function void(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.void');

        $request->validate(['reason' => 'required|string|max:1000']);

        [$order, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)
            ->void($order, (string) $request->input('reason'), auth()->id()));

        if ($refusal) {
            return $refusal;
        }

        return back()->with('success', "Sales Order {$order->sales_order_number} voided.");
    }

    /** §5.2 · POST /sales-orders/{id}/fulfilments */
    public function fulfil(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.fulfil');

        $validated = $request->validate([
            'date' => 'required|date',
            'dn_no' => 'nullable|string|max:30',
            'received_by' => 'nullable|string|max:150',
            'lines' => 'required|array|min:1',
            'lines.*.line_id' => ['required', 'integer', Rule::exists('sales_order_lines', 'id')->where('sales_order_id', $order->id)],
            // `max` is a UI affordance; the authoritative cap is enforced in the
            // service against the remaining quantity (§5.4).
            'lines.*.qty' => 'required|numeric|min:0.01',
        ]);

        [$result, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)
            ->recordFulfilment($order, $validated, auth()->id()));

        if ($refusal) {
            return $refusal;
        }

        return redirect()->route('accounting.sales-orders.show', $order)
            ->with('success', $result['complete']
                ? "Delivery {$result['delivery_note_no']} completed — Sales Order {$order->sales_order_number} is now fulfilled."
                : "Delivery {$result['delivery_note_no']} recorded. Sales Order {$order->sales_order_number} is partially fulfilled.");
    }

    /** Legacy verb kept for the pre-existing `fulfill` route. */
    public function markFulfilled(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.fulfil');

        [$result, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)->markFulfilled($order, auth()->id()));

        if ($refusal) {
            return $refusal;
        }

        return back()->with('success', "Sales Order {$order->sales_order_number} marked as fulfilled.");
    }

    /** §5.2 · POST /sales-orders/{id}/send-email */
    public function sendEmail(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.send_email');

        $validated = $request->validate([
            // Format-validated server-side (§5.4) — the toast in the UI is only
            // a mirror of this rule. Deliberately NOT `dns`: customers on
            // internal/unresolvable domains must still receive acknowledgements.
            'email' => ['required', 'string', 'email', 'max:150'],
        ]);

        [$order, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)->recordEmailed(
            $order,
            $validated['email'],
            auth()->id(),
            $request->input('notes')
        ));

        if ($refusal) {
            return $refusal;
        }

        return back()->with('success', "Acknowledgement queued for {$validated['email']}.");
    }

    /** §5.2 · POST /sales-orders/{id}/invoice */
    public function convertToInvoice(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.invoice');

        [$invoice, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)
            ->createInvoice($order, auth()->id()));

        if ($refusal) {
            return $refusal;
        }

        return redirect()->route('accounting.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created from Sales Order {$order->sales_order_number}.");
    }

    /** §9 integration point — POS sale-off-a-fulfilled-order. Preserved. */
    public function convertToSalesReceipt(SalesOrder $order, Request $request)
    {
        $this->requirePermission($request, 'sales-orders.convert');

        $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.payment_method_id' => 'required|exists:pos_payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ]);

        [$receipt, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)->convertToSalesReceipt($order, [
            'payments' => $request->payments,
        ], auth()->id()));

        if ($refusal) {
            return $refusal;
        }

        return redirect()->route('accounting.sales-receipts.show', $receipt)
            ->with('success', "Sales Receipt {$receipt->receipt_number} created from Sales Order {$order->sales_order_number}.");
    }

    /** §4 · Duplicate is available on every retained state. */
    public function duplicate(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.create');

        [$copy, $refusal] = $this->guarded(fn () => app(SalesOrderService::class)->duplicate($order, auth()->id()));

        if ($refusal) {
            return $refusal;
        }

        return redirect()->route('accounting.sales-orders.edit', $copy)
            ->with('success', "Sales Order {$copy->sales_order_number} duplicated from {$order->sales_order_number}.");
    }

    /** Drafts only; every released document is cancelled/voided, never deleted. */
    public function destroy(SalesOrder $order)
    {
        $this->requirePermission(request(), 'sales-orders.edit');
        abort_unless((int) $order->company_id === (int) session('current_company_id'), 403);

        // §5.2 — a DELETE on a non-draft is a 409, matching PUT.
        abort_unless($order->isDraft(), 409, 'Only draft sales orders can be deleted. Cancel or void instead.');

        foreach ($order->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }
        $order->attachments()->delete();

        $number = $order->sales_order_number;
        app(SalesOrderService::class)->destroy($order, auth()->id());

        return redirect()->route('accounting.sales-orders.index')
            ->with('success', "Sales Order {$number} deleted.");
    }

    // ── Print / PDF ────────────────────────────────────────────────────────

    /** §5.2 · GET /sales-orders/{id}/print?doc=order|delivery */
    public function print(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.print_pdf');

        $order->load(['lines.product', 'customer', 'branch', 'createdByUser', 'fulfilments']);

        return view('accounting.sales-orders.print', [
            'order' => $order,
            'doc' => $request->input('doc') === 'delivery' ? 'delivery' : 'order',
            'company' => \App\Models\Company::find($order->company_id),
        ]);
    }

    /** §5.2 · GET /sales-orders/{id}/pdf?doc=order|delivery */
    public function pdf(Request $request, SalesOrder $order)
    {
        $this->requirePermission($request, 'sales-orders.print_pdf');

        $order->load(['lines.product', 'customer', 'branch', 'createdByUser', 'fulfilments']);

        $doc = $request->input('doc') === 'delivery' ? 'delivery' : 'order';
        $html = view('accounting.sales-orders.print', [
            'order' => $order,
            'doc' => $doc,
            'company' => \App\Models\Company::find($order->company_id),
        ])->render();

        $filename = ($doc === 'delivery' ? 'delivery-note-' : 'sales-order-') . $order->sales_order_number . '.pdf';

        // mPDF is already a dependency; no new package (§ HARD GUARD). Its
        // default temp dir lives inside vendor/ which is not writable on a
        // typical XAMPP install, so point it at storage instead. mPDF is used
        // (rather than DomPDF) because the print sheet relies on mPDF-only
        // @page margin boxes for its footer.
        $mpdf = new \Mpdf\Mpdf([
            'mode' => '',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 10,
            'margin_bottom' => 14,
            'tempDir' => $this->mpdfTempDir(),
        ]);
        $mpdf->WriteHTML($html);

        return response()->streamDownload(
            function () use ($mpdf) {
                echo $mpdf->Output('', 'S');
            },
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /**
     * Resolve a temp dir that mPDF will accept.
     *
     * mPDF probes its base path with is_writable() and aborts when that
     * returns false — even on Windows installs where the write itself would
     * succeed (this repo's storage/ subtree reports not-writable for every
     * path). Probing in order keeps the normal Linux/production answer
     * (storage/) and falls back to the OS temp dir only when storage is not
     * verifiably writable.
     */
    private function mpdfTempDir(): string
    {
        foreach ([storage_path('framework/mpdf'), storage_path('app/mpdf')] as $dir) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            }
        }

        $fallback = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'camelot-books-mpdf';

        if (! is_dir($fallback)) {
            @mkdir($fallback, 0775, true);
        }

        return $fallback;
    }

    /**
     * Run a service guard that signals a user-correctable refusal.
     *
     * The service layer throws InvalidArgumentException for refusals the user
     * can act on (over-fulfilment, invoicing before fulfilment, cancelling a
     * posted order…). Left uncaught those surface as HTTP 500; §5.4 requires a
     * validation message on the originating form instead.
     *
     * @return array{0: mixed, 1: ?\Illuminate\Http\RedirectResponse} [result, null]
     *                                                       or [null, redirect]
     */
    private function guarded(callable $callback): array
    {
        try {
            return [$callback(), null];
        } catch (InvalidArgumentException $e) {
            return [null, back()->withInput()->withErrors(['status' => $e->getMessage()])];
        }
    }

    private function canDo(string $permission): bool
    {
        $user = request()->user();

        return $user ? $user->can($permission) : false;
    }
}