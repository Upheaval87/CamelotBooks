<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SystemSetting;
use App\Services\Accounting\ArInvoiceService;
use App\Services\Accounting\InvoiceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoiceService, protected ArInvoiceService $arInvoiceService)
    {
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $filters = $this->registerFilters($request);

        $query = $this->arInvoiceService->registerQuery($filters, $companyId);
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        $query->with(['lines.product', 'payments', 'sourceQuotation', 'company', 'costCenter']);
        $invoices = $query->paginate(15)->withQueryString();

        $today = Carbon::today();
        $invoices->getCollection()->transform(function ($invoice) use ($today) {
            $invoice->aging = $this->agingChipFor($invoice, $today);

            return $invoice;
        });

        // KPI note: how many open invoices feed the Outstanding AR figure.
        $openCount = $this->arInvoiceService->registerQuery([
            'q' => $filters['q'],
            'period_from' => $filters['period_from'],
            'period_to' => $filters['period_to'],
        ], $companyId)
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID, Invoice::STATUS_OVERDUE])
            ->whereRaw('(COALESCE(grand_total, amount) - amount_paid) > 0.005')
            ->count();

        // Collections queue rail (R9): worst aging first, bounded to the due-≤7d window.
        $queueRail = Invoice::where('company_id', $companyId)
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID, Invoice::STATUS_OVERDUE])
            ->whereRaw('(COALESCE(grand_total, amount) - amount_paid) > 0.005')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today->copy()->addDays(7)->toDateString())
            ->orderBy('due_date')
            ->with(['customer', 'lines.product', 'payments', 'sourceQuotation', 'company', 'costCenter'])
            ->limit(6)
            ->get()
            ->map(function (Invoice $invoice) use ($today) {
                $invoice->setAttribute('aging', $this->agingChipFor($invoice, $today));

                return $invoice;
            });

        // KPI strip (2.1): computed over the searched + period-filtered set so the
        // figures stay live without being dragged around by the tile/status filter.
        $summary = $this->arInvoiceService->registerSummary(
            $this->arInvoiceService->registerQuery([
                'q' => $filters['q'],
                'period_from' => $filters['period_from'],
                'period_to' => $filters['period_to'],
            ], $companyId)
        );

        // Status tiles + Views rail (R7): counts keyed by the STORED status, with
        // Overdue taken from the derived summary (R6 — never stored).
        $counts = Invoice::where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $draft = (int) ($counts['draft'] ?? 0);
        $sent = (int) ($counts['sent'] ?? 0);
        $partial = (int) ($counts['partially_paid'] ?? 0) + (int) ($counts['partial'] ?? 0);
        $paid = (int) ($counts['paid'] ?? 0);
        $void = (int) ($counts['void'] ?? 0);
        $all = $draft + $sent + $partial + $paid + $void;
        $overdue = (int) $summary['overdue_count'];

        $cs = (string) SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        // Spec workspace/payment payload (js.txt `IV` + `openView` + `footHTML`),
        // keyed by invoice id and covering BOTH the current page and the queue rail
        // so a rail card opens the workspace even when its row sits on another page.
        $ivPayload = $this->registerPayload(
            collect($invoices->items())->concat($queueRail->values())->unique('id')->values()->all(),
            auth()->user(),
            $cs
        );

        // Modal shows the receipt number the NEXT payment will take (re-derived
        // under lockForUpdate() at payment time).
        $nextReceipt = $this->arInvoiceService->previewReceiptNumber($companyId);

        // Views-rail "Open" count — spec label is "Open (Sent + Part)" and it must
        // agree with the `view=open` server filter (sent + partially_paid only).
        $railOpen = $sent + $partial;

        // Spec `commitInvoice` lands on this register for both draft and post;
        // the payload drives the draft toast or the "Invoice posted" success box.
        // pull() = one-shot: the outcome must not replay on a refresh/Back.
        $invOutcome = session()->pull('invOutcome');

        return view('accounting.invoices.index', compact(
            'invoices', 'summary', 'cs', 'all', 'draft', 'sent', 'partial', 'paid', 'overdue', 'void',
            'filters', 'openCount', 'queueRail', 'ivPayload', 'nextReceipt', 'railOpen', 'invOutcome'
        ));
    }

    /**
     * REGISTER PAYLOAD — the single JSON blob the spec JS reads instead of the
     * mockup's client-side `IV` map. Keyed by invoice id; each entry carries the
     * workspace modal (R4/R5/R15) plus a server-rendered modal footer built under
     * the SAME permission + SOD rules the POST routes enforce (creator ⇒ 403), so
     * the UI never offers an action the server will refuse.
     */
    private function registerPayload(array $invoices, $user, string $cs): array
    {
        $payload = [];

        foreach ($invoices as $invoice) {
            $payload[$invoice->id] = $this->invoicePayload($invoice, $user, $cs);
        }

        return $payload;
    }

    private function invoicePayload(Invoice $invoice, $user, string $cs): array
    {
        $status = $invoice->collectionStatus();
        $isCreator = $user !== null && (int) $invoice->created_by === (int) $user->id;
        $total = $invoice->documentTotal();
        $paid = round((float) $invoice->amount_paid, 2);
        $bal = round($total - $paid, 2);
        $issue = $invoice->issueDate();
        $due = $invoice->due_date;
        $overdue = $invoice->isOverdue();

        // R15: customer lives on the header chip only — the meta strip carries
        // issue/due/source/emailed/balance, never the customer name again.
        $meta = [];
        $meta[] = ['Issued ' . ($issue?->format('d M Y') ?? '—'), ''];
        $meta[] = ['Due ' . ($due?->format('d M Y') ?? '—'), ''];
        if ($overdue) {
            $meta[] = ['Overdue', 'bad'];
        }
        $sourceLabel = $invoice->sourceQuotation?->quotation_number
            ?: ($invoice->source_ref ?: ($invoice->source_type === Invoice::SOURCE_QUOTATION ? 'Quotation' : 'Manual'));
        $meta[] = ['From ' . $sourceLabel, 'mono'];
        if ($invoice->emailed_at) {
            $meta[] = ['Emailed ' . $invoice->emailed_at->format('d M Y'), 'mono'];
        }
        $meta[] = ['Balance ' . $cs . ' ' . number_format($bal, 2), 'b'];

        $days = $overdue
            ? ['Days overdue', $invoice->daysOverdue(), true]
            : ['Days to due', $due ? max(0, (int) abs($due->copy()->startOfDay()->diffInDays(now()->startOfDay(), false))) : null, false];

        $lines = $invoice->lines->map(function ($line) {
            $code = $line->product?->code;
            $name = $line->product?->name;
            $text = $line->description ?: ($name ?: 'Line item');
            if ($name && $line->description && !str_contains($line->description, $name)) {
                $text = $name . ' (' . $line->description . ')';
            }
            if ($code && !str_contains($text, $code)) {
                $text = $code . ' — ' . $text;
            }

            return [
                $text,
                (float) $line->quantity,
                number_format((float) $line->unit_price, 2),
                number_format((float) $line->amount, 2),
            ];
        })->values()->all();

        $payments = $invoice->payments
            ->sortBy('paid_on')
            ->values()
            ->map(fn ($payment) => [
                'date' => optional($payment->paid_on)->format('d M Y') ?? '—',
                'method' => $payment->method_label ?: ucwords(str_replace('_', ' ', $payment->method)),
                'ref' => $payment->receipt_no,
                'amount' => number_format((float) $payment->amount, 2),
            ])->all();

        return [
            'ref' => $invoice->invoice_number,
            'cust' => $invoice->partyName(),
            'contact' => $invoice->contact_person,
            'email' => $invoice->email,
            'phone' => $invoice->phone,
            'addr' => $invoice->bill_address,
            'status' => $status,
            'is_creator' => $isCreator,
            'issue' => $issue?->toDateString(),
            'due' => $due?->toDateString(),
            'terms' => $this->termsLabel($invoice->terms_key),
            'source' => $sourceLabel,
            'refNo' => $invoice->reference,
            'prepared' => $invoice->prepared_by,
            'notes' => $invoice->notes_customer,
            'emailed' => $invoice->emailed_at?->format('d M Y'),
            'emailed_to' => $invoice->emailed_to,
            'total' => $total,
            'sub' => $invoice->documentSubtotal(),
            'tax' => $invoice->documentTax(),
            'paid' => $paid,
            'balance' => $bal,
            'overdue' => $overdue,
            'days' => $days,
            'meta' => $meta,
            'lines' => $lines,
            'payments' => $payments,
            'receipts' => count($payments),
            'cs' => $cs,
            'cc' => $invoice->costCenter?->name ?: '—',
            'pc' => $this->printCompany($invoice),
            'foot' => $this->workspaceFooter($invoice, $user, $isCreator),
            'urls' => [
                'show' => route('accounting.invoices.show', $invoice),
                'edit' => $invoice->isDraft() ? route('accounting.invoices.edit', $invoice) : null,
                'print' => route('accounting.invoices.print', $invoice),
                'post' => route('accounting.invoices.post', $invoice),
                'void' => route('accounting.invoices.void', $invoice),
                'send' => route('accounting.invoices.send-email', $invoice),
                'pay' => route('accounting.invoices.payments', $invoice),
            ],
        ];
    }

    /**
     * Print sheet header + remittance block (spec js.txt L9-L35 hardcodes the
     * sample company): live company data with the same bank fallbacks as
     * print.blade.php, plus the currency name for the amount-in-words line.
     * The currency catalogue is plucked once per process (fixed seed data).
     */
    private function printCompany(Invoice $invoice): array
    {
        $company = $invoice->company;
        $name = $company?->name ?? config('app.company_name', 'CamelotBooks');

        static $currencyNames = null;
        if ($currencyNames === null) {
            try {
                $currencyNames = Currency::query()->pluck('name', 'code')->all();
            } catch (\Throwable $e) {
                $currencyNames = [];
            }
        }

        return [
            'mark' => strtoupper(mb_substr($name, 0, 1)),
            'name' => $name,
            'addr' => $company?->address ?: '—',
            'phone' => $company?->phone ?: '',
            'email' => $company?->email ?: '',
            'bank_name' => $company?->bank_name ?: 'National Bank - Head Office',
            'bank_branch' => $company?->bank_branch ?: '-',
            'bank_account' => $company?->bank_account ?: '0000000000',
            'currency' => $currencyNames[$company?->base_currency] ?? ($company?->base_currency ?: 'Kwacha'),
        ];
    }

    /**
     * `footHTML()` (js.txt L351-356) rendered SERVER-side: same copy and button
     * order per status, but gated by permission + SOD (creator ⇒ 403) so the UI
     * never offers an action the server will refuse. The HTML lands in the
     * payload; `openView()` injects it verbatim and appends CLOSE_BTN itself.
     */
    private function workspaceFooter(Invoice $invoice, $user, bool $isCreator): string
    {
        $status = $invoice->collectionStatus();

        $svgPen = '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4z"/></svg>';
        $svgPrint = '<svg viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>';
        $svgSend = '<svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>';
        $svgPay = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 10h8M8 14h8"/></svg>';
        $svgPost = '<svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>';

        $print = '<button class="btn btn-ghost btn-sm" data-vact="print">' . $svgPrint . 'Print / PDF</button>';
        $edit = ($invoice->isDraft() && $user?->can('invoices.edit'))
            ? '<button class="btn btn-ghost btn-sm" data-vact="edit">' . $svgPen . 'Edit invoice</button>'
            : '';
        $post = (!$isCreator && $invoice->isDraft() && $user?->can('invoices.post'))
            ? '<button class="btn btn-cta btn-sm" data-vact="post">' . $svgPost . 'Post invoice</button>'
            : '';
        $void = (!$isCreator && in_array($status, ['draft', 'sent'], true) && $user?->can('invoices.void'))
            ? '<button class="btn btn-danger-o btn-sm" data-vact="void">Void</button>'
            : '';
        $send = (!$isCreator && in_array($status, ['sent', 'partial', 'paid'], true) && $user?->can('invoices.send_email'))
            ? '<button class="btn btn-ghost btn-sm" data-vact="send">' . $svgSend . 'Send to customer</button>'
            : '';
        $pay = (!$isCreator && in_array($status, ['sent', 'partial'], true) && $invoice->balance_due > 0.005
            && $user?->can('invoices.record_payment'))
            ? '<button class="btn btn-green btn-sm" data-vact="pay">' . $svgPay . 'Record payment</button>'
            : '';

        if ($status === 'draft') {
            return '<span class="mnote">Draft — post to issue and start the payment clock.</span>'
                . $edit . $print . $void . $post;
        }

        if ($status === 'void') {
            return '<span class="mnote">Void — retained for audit only.</span>';
        }

        $count = $invoice->payments->count();
        if ($status === 'paid') {
            return '<span class="mnote">Settled in full — ' . $count . ' receipt' . ($count === 1 ? '' : 's')
                . ' recorded.</span>' . $print . $send;
        }

        // sent + partial
        return '<span class="mspacer"></span>' . $print . $send . $void . $pay;
    }

    /** `TERMS[iv.terms]` (js.txt L6) with `terms_key` variants mapped to spec labels. */
    private function termsLabel(?string $key): string
    {
        $map = [
            'receipt' => 'Due on receipt',
            'due_on_receipt' => 'Due on receipt',
            'net15' => 'Net 15',
            'net_15' => 'Net 15',
            'net30' => 'Net 30',
            'net_30' => 'Net 30',
            'net60' => 'Net 60',
            'net_60' => 'Net 60',
        ];

        if ($key === null || $key === '') {
            return '—';
        }

        return $map[Str::lower($key)] ?? ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * §5.2 register filters. Legacy list-page param names (`search`, `from_date`,
     * `to_date`, `date-*`/`amount-*` sorts) keep working beside the register's own
     * (`q`, `period_from`, `period_to`, `new`/`old`/`high`/`bal`/`due` sorts).
     *
     * R9 — the Period presets are resolved to concrete `period_from`/`period_to`
     * bounds here, server-side: the browser only ever sends `period=m0` and never
     * has to know the calendar, which keeps the URL shareable and the KPI strip,
     * tiles and table all reading from the same window.
     */
    private function registerFilters(Request $request): array
    {
        $period = trim((string) $request->input('period', ''));
        $from = trim((string) $request->input('period_from', $request->input('from_date', '')));
        $to = trim((string) $request->input('period_to', $request->input('to_date', '')));

        if ($period !== '' && $period !== 'custom') {
            [$from, $to] = $this->periodRange($period);
        } elseif ($period === '' && ($from !== '' || $to !== '')) {
            // A bare range with no preset is the Custom range selection.
            $period = 'custom';
        }

        return [
            'q' => trim((string) $request->input('q', $request->input('search', ''))),
            'status' => trim((string) $request->input('status', '')),
            'view' => trim((string) $request->input('view', '')),
            'period' => $period,
            'period_from' => $from,
            'period_to' => $to,
            'sort' => trim((string) $request->input('sort', 'new')),
            'customer_id' => $request->integer('customer_id') ?: null,
        ];
    }

    /**
     * R9 preset → inclusive `invoice_date` window. `custom` and `''` resolve to
     * no bounds so the caller's own `period_from`/`period_to` pass through.
     *
     * @return array{0: string, 1: string}
     */
    private function periodRange(string $preset): array
    {
        $today = Carbon::today();

        return match ($preset) {
            'm0' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'm-1' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'd30' => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
            'q0' => [$today->copy()->startOfQuarter()->toDateString(), $today->copy()->endOfQuarter()->toDateString()],
            'ytd' => [$today->copy()->startOfYear()->toDateString(), $today->toDateString()],
            default => ['', ''],
        };
    }

    /** R8 — the aging chip every due-date cell carries, derived server-side. */
    private function agingChipFor(Invoice $invoice, Carbon $today): array
    {
        $status = $invoice->arStatus();

        if ($status === 'void') {
            return ['tone' => 'mute', 'label' => '—'];
        }

        if ($status === 'paid' || $invoice->isSettled()) {
            return ['tone' => 'good', 'label' => 'Settled'];
        }

        if ($invoice->due_date === null) {
            return ['tone' => 'ok', 'label' => 'Current'];
        }

        // Whole-day diff between startOfDay boundaries — cast to int so the
        // `=== 0` arm matches (Carbon 3 returns a float, 0.0 !== 0).
        $diff = (int) $today->copy()->startOfDay()->diffInDays($invoice->due_date->copy()->startOfDay(), false);

        if ($diff < 0) {
            return ['tone' => 'exp', 'label' => sprintf('%dd over', abs((int) $diff))];
        }

        if ($diff === 0) {
            return ['tone' => 'soon', 'label' => 'Today'];
        }

        if ($diff <= 7) {
            return ['tone' => 'soon', 'label' => sprintf('%d days', (int) $diff)];
        }

        return ['tone' => 'ok', 'label' => 'Current'];
    }

    public function create(Request $request)
    {
        $companyId = session('current_company_id');

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $incomeAccounts = Account::where('company_id', $companyId)
            ->where('type', 'income')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $itemCategories = ItemCategory::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $copyQuote = null;
        if ($request->filled('copy_quote')) {
            $quote = Quotation::with(['customer', 'lines.product'])
                ->where('company_id', $companyId)
                ->find($request->integer('copy_quote'));

            if ($quote && in_array($quote->status, [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])) {
                $copyQuote = $this->copyQuotePayload($quote);
            }
        }

        $preselectCustomer = null;
        if (!$copyQuote && $request->filled('customer_id')) {
            $customer = $customers->firstWhere('id', (int) $request->integer('customer_id'));
            if ($customer) {
                $preselectCustomer = ['id' => $customer->id, 'name' => $customer->name];
            }
        }

        $copyQuotes = $this->copyQuotesQuery($companyId)->get();

        return view('accounting.invoices.create', compact('customers', 'products', 'incomeAccounts', 'costCenters', 'itemCategories', 'copyQuote', 'preselectCustomer', 'copyQuotes'));
    }

    public function store(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $this->prepareCustomerSelection($request, $companyId);

        // R11 — posting is a separate capability from saving a draft; SOD is not
        // applied here because no record exists yet for `created_by` to conflict
        // with (the standalone `invoices.post` route keeps maker-checker).
        if ($request->input('mode') === 'post') {
            $this->requirePermission('invoices.post');
        }

        $validated = $request->validate($this->invoiceRules($companyId));
        $validated = $this->normalizeInvoicePayload($validated, $companyId);

        try {
            $invoice = $this->arInvoiceService->create($validated, auth()->id(), $companyId);

            return $this->invoiceOutcomeRedirect($invoice, $validated['mode']);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Invoice $invoice)
    {
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        $invoice->load(['customer', 'lines.product', 'lines.costCenter', 'journalEntry', 'payments']);

        // AR module 5.1: `payments` is now the module's own immutable receipts.
        // The pre-existing sales-receipt settlement leg is still listed by the
        // legacy settlement tab, so both are passed to the view.
        $payments = $invoice->payments()->orderByDesc('paid_on')->orderByDesc('id')->get();
        $settlements = $invoice->settlementPayments()->with('allocations')->get();

        $copyQuotes = $this->copyQuotesQuery($companyId, $invoice->customer_id)->get();

        return view('accounting.invoices.show', compact('invoice', 'payments', 'settlements', 'copyQuotes'));
    }

    public function edit(Invoice $invoice)
    {
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        if (!$invoice->isDraft()) {
            abort(403, 'Only draft invoices can be edited.');
        }

        $invoice->load('lines.product');

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $incomeAccounts = Account::where('company_id', $companyId)
            ->where('type', 'income')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $itemCategories = ItemCategory::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $copyQuotes = $this->copyQuotesQuery($companyId, $invoice->customer_id)->get();

        return view('accounting.invoices.edit', compact('invoice', 'customers', 'products', 'incomeAccounts', 'costCenters', 'itemCategories', 'copyQuotes'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $companyId = (int) session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        if (!$invoice->isDraft()) {
            abort(403, 'Only draft invoices can be updated.');
        }

        $this->prepareCustomerSelection($request, $companyId);

        if ($request->input('mode') === 'post') {
            $this->requirePermission('invoices.post');
        }

        $validated = $request->validate($this->invoiceRules($companyId));
        $validated = $this->normalizeInvoicePayload($validated, $companyId);

        try {
            $invoice = $this->arInvoiceService->update($invoice, $validated, auth()->id());

            return $this->invoiceOutcomeRedirect($invoice, $validated['mode']);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * The client mirrors the spec's datalist pick by submitting a hidden
     * `customer_id`; a hand-typed registry name (or a no-JS client) sends only
     * `customer_search`, so it is resolved here against the same exact,
     * company-scoped, active match BEFORE validation. The walk-in branch is
     * deliberately not auto-resolved — R12 captures it inside the save
     * transaction — but a stale `customer_id` left from an earlier "existing"
     * selection is cleared so the walk-in name cannot be silently swallowed.
     */
    private function prepareCustomerSelection(Request $request, int $companyId): void
    {
        if (!$request->filled('customer_type')) {
            $request->merge(['customer_type' => 'existing']);
        }

        if ($request->input('customer_type') === 'walkin') {
            $request->merge(['customer_id' => null]);

            return;
        }

        if ($request->filled('customer_id') || !$request->filled('customer_search')) {
            return;
        }

        $match = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('name', $request->input('customer_search'))
            ->first();

        if ($match) {
            $request->merge(['customer_id' => $match->id]);
        }
    }

    /**
     * Rules for BOTH store and update. `$request->validate()` returns ONLY the
     * keys listed here, and the validated array is the whole payload the
     * `ArInvoiceService` receives — so every key the service reads (terms,
     * notes, cost centre, source text, mode …) must appear or it is silently
     * dropped before it reaches `arColumns()`.
     *
     * `terms_key` accepts the full canonical vocabulary (not just the four the
     * form offers) so editing a legacy invoice that carries `net_90`/`custom`
     * cannot brick on its own stored value. Email is deliberately format-free:
     * the spec posts an invoice with a junk address and merely skips R17's
     * auto-email (`emailOk`) — a format rule would block the save instead.
     *
     * @return array<string, array<int, mixed>>
     */
    private function invoiceRules(int $companyId): array
    {
        return [
            'customer_type' => ['nullable', Rule::in(['existing', 'walkin'])],
            'customer_id' => ['required_if:customer_type,existing', 'nullable', Rule::exists(Customer::class, 'id')->where('company_id', $companyId)],
            'customer_name' => ['required_if:customer_type,walkin', 'nullable', 'string', 'max:255'],
            'customer_search' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'bill_address' => ['nullable', 'string', 'max:500'],
            'terms_key' => ['nullable', Rule::in(ArInvoiceService::TERMS_KEYS)],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'source_ref' => ['nullable', 'string', 'max:60'],
            'source_quotation_id' => ['nullable', 'integer'],
            'notes_customer' => ['nullable', 'string', 'max:5000'],
            'notes_internal' => ['nullable', 'string', 'max:5000'],
            'cost_centre' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'prepared_by' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'max:10'],
            'mode' => ['nullable', Rule::in(['draft', 'post'])],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.item_code' => ['nullable', 'string', 'max:60'],
            'lines.*.item_name' => ['nullable', 'string', 'max:255'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.income_account_id' => ['nullable', 'integer'],
            'lines.*.cost_center_id' => ['nullable', 'integer'],
            'memo' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Maps the spec form's fields onto the service's payload contract:
     *
     * - `issue_date` mirrors `invoice_date` — one fact, two columns (§4.3);
     *   `InvoiceService::arColumns()` writes both from this key so they cannot
     *   drift.
     * - `memo` falls back to the visible Customer notes — the spec form has no
     *   separate memo input and the show page prints `memo` as the customer
     *   note.
     * - `currency` defaults to the company's base currency (the form does not
     *   expose it; the legacy hard-coded 'USD' was wrong for every tenant).
     * - `mode` defaults to draft so the outcome payload is never ambiguous.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeInvoicePayload(array $validated, int $companyId): array
    {
        $validated['company_id'] = $companyId;
        $validated['issue_date'] = $validated['invoice_date'];
        $validated['memo'] = $validated['memo'] ?? ($validated['notes_customer'] ?? null);
        $validated['mode'] = $validated['mode'] ?? 'draft';

        if (empty($validated['currency'])) {
            $validated['currency'] = Company::query()->whereKey($companyId)->value('base_currency') ?: null;
        }

        return $validated;
    }

    /**
     * The spec's `commitInvoice` lands on the register for BOTH draft and post
     * (`showView('list')`), where the register JS turns `invOutcome` into the
     * draft toast (js.txt L885) or the "Invoice posted" success box with the
     * print action (js.txt L867). The `success` flash is deliberately NOT set —
     * the layout's flash emitter would raise a second, off-spec toast.
     */
    private function invoiceOutcomeRedirect(Invoice $invoice, string $mode): \Illuminate\Http\RedirectResponse
    {
        $outcome = [
            'mode' => $mode === 'post' ? 'post' : 'draft',
            'id' => $invoice->id,
            'ref' => (string) $invoice->invoice_number,
            'cust' => (string) ($invoice->customer_name ?: $invoice->customer?->name ?: $invoice->customer_id ?: '—'),
        ];

        if ($outcome['mode'] === 'post') {
            $outcome += [
                'total' => round((float) ($invoice->grand_total ?: $invoice->amount), 2),
                'due' => $invoice->due_date ? Carbon::parse($invoice->due_date)->toDateString() : null,
                'source' => $invoice->source_type === Invoice::SOURCE_QUOTATION && $invoice->source_ref
                    ? (string) $invoice->source_ref
                    : null,
                'emailed' => $invoice->emailed_to ?: null,
                'printUrl' => route('accounting.invoices.print', $invoice),
            ];
        } else {
            $outcome['msg'] = '✓ ' . $outcome['ref'] . ' saved as draft · ' . $outcome['cust'];
        }

        return redirect()->route('accounting.invoices.index')->with('invOutcome', $outcome);
    }

    public function post(Request $request, Invoice $invoice)
    {
        $this->requirePermission('invoices.post');
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        try {
            $this->invoiceService->post($invoice, auth()->id());

            if ($request->wantsJson()) {
                $invoice->refresh();

                return response()->json([
                    'ok' => true,
                    'status' => $invoice->collectionStatus(),
                    'due' => $invoice->due_date?->toDateString(),
                    'total' => (float) $invoice->documentTotal(),
                ]);
            }

            return redirect()->route('accounting.invoices.show', $invoice)
                ->with('success', 'Invoice posted successfully.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function void(Invoice $invoice, Request $request)
    {
        $this->requirePermission($request, 'invoices.void');
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        $validated = $request->validate([
            'reason' => [$request->wantsJson() ? 'nullable' : 'required', 'string', 'max:500'],
        ]);

        try {
            // The register's confirm box (spec markup) carries no reason input —
            // JSON callers fall back to an auditable default.
            $this->invoiceService->void($invoice, $validated['reason'] ?? 'Voided from invoice register', auth()->id());

            if ($request->wantsJson()) {
                return response()->json(['ok' => true]);
            }

            return redirect()->route('accounting.invoices.show', $invoice)
                ->with('success', 'Invoice voided successfully.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function copyQuote(Request $request)
    {
        $companyId = session('current_company_id');

        $request->validate([
            'quotation' => ['required', 'integer'],
        ]);

        $quote = Quotation::with(['customer', 'lines.product'])
            ->where('company_id', $companyId)
            ->findOrFail($request->integer('quotation'));

        abort_unless(in_array($quote->status, [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED], true), 422, 'Only sent or accepted quotations can be copied to an invoice.');

        return response()->json($this->copyQuotePayload($quote));
    }

    /**
     * R13 — the picker lists only UNINVOICED quotations, matching
     * `ArInvoiceService::uninvoicedQuotations()`' where-clauses (`invoiced_flag`
     * false + `invoice_id` null/0 + SENT/ACCEPTED) so the list and the service's
     * save-time guard can never disagree. `lines.product` is eager-loaded
     * because `copyQuotePayload()` maps `$line->product` for every row.
     */
    protected function copyQuotesQuery(int $companyId, ?int $customerId = null)
    {
        return Quotation::with(['customer', 'lines.product'])
            ->where('company_id', $companyId)
            ->where('invoiced_flag', false)
            ->where(function (Builder $query) {
                $query->whereNull('invoice_id')
                    ->orWhere('invoice_id', 0);
            })
            ->whereIn('status', [Quotation::STATUS_SENT, Quotation::STATUS_ACCEPTED])
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->orderByDesc('quotation_date')
            ->limit(25);
    }

    protected function copyQuotePayload(Quotation $quote): array
    {
        $lines = $quote->lines;
        $accountLabels = Account::whereIn('id', $lines->pluck('income_account_id')->filter()->unique())
            ->get(['id', 'code', 'name'])
            ->keyBy('id');
        $ccLabels = CostCenter::whereIn('id', $lines->pluck('cost_center_id')->filter()->unique())
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        return [
            'quotation_id' => $quote->id,
            'quotation_number' => $quote->quotation_number,
            'customer_id' => $quote->customer_id,
            'customer_name' => $quote->customer?->name ?? '',
            'customer_contact' => $quote->customer?->display_name ?? $quote->customer?->name ?? '',
            'customer_email' => $quote->customer?->email ?? '',
            'customer_phone' => $quote->customer?->phone ?? '',
            'customer_terms' => $quote->customer?->payment_terms ?? '',
            'memo' => "Converted from Quotation {$quote->quotation_number}",
            'reference' => $quote->reference,
            'currency' => $quote->currency,
            'total' => (float) $quote->total,
            'lines' => $lines->map(function ($line) use ($accountLabels, $ccLabels) {
                $account = $accountLabels->get($line->income_account_id);

                return [
                    'product_id' => $line->product_id,
                    'label' => $line->product?->name ?? '',
                    'sku' => $line->product?->sku ?? '',
                    'description' => $line->description,
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                    'discount' => (float) $line->discount,
                    'tax_rate' => (float) $line->tax_rate,
                    'income_account_id' => $line->income_account_id,
                    'income_account_label' => $account ? "{$account->code} - {$account->name}" : '',
                    'cost_center_id' => $line->cost_center_id,
                    'cost_center_label' => ($cc = $ccLabels->get($line->cost_center_id)) ? "{$cc->code} - {$cc->name}" : '',
                    'amount' => (float) $line->amount,
                    'tax_amount' => (float) $line->tax_amount,
                    'line_total' => (float) $line->line_total,
                ];
            })->values()->all(),
        ];
    }

    public function printPdf(Request $request, Invoice $invoice)
    {
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        $invoice->load(['customer', 'lines.product']);

        $format = $request->query('format', 'html');

        if ($format === 'pdf') {
            // Placeholder for PDF rendering via PdfPresenter — keep inline with module
            // implementation when the PDF view/template is wired up.
            return view('accounting.invoices.print', compact('invoice'));
        }

        return view('accounting.invoices.print', compact('invoice'));
    }

    public function sendEmail(Request $request, Invoice $invoice)
    {
        $this->requirePermission($request, 'invoices.send_email');
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        try {
            $result = $this->arInvoiceService->sendEmail($invoice, auth()->id());

            if ($request->wantsJson()) {
                return response()->json([
                    'ok' => true,
                    'recipient' => $result['recipient'],
                ]);
            }

            return redirect()->route('accounting.invoices.show', $invoice)
                ->with('success', 'Invoice emailed to ' . $result['recipient']);
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $this->requirePermission($request, 'invoices.record_payment');
        $companyId = session('current_company_id');
        abort_unless($invoice->company_id == $companyId, 403);

        $validated = $request->validate([
            'paid_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string'],
            'method_label' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'details' => ['nullable', 'array'],
            'splits' => ['nullable', 'array'],
        ]);

        try {
            $payment = $this->arInvoiceService->recordPayment($invoice, $validated, auth()->id());

            if ($request->wantsJson()) {
                $invoice->refresh();

                return response()->json([
                    'receipt' => $payment->receipt_no,
                    'amount' => (float) $payment->amount,
                    'balance' => (float) $invoice->balance_due,
                    'status' => $invoice->collectionStatus(),
                    'paid' => (float) $invoice->amount_paid,
                ]);
            }

            return redirect()->route('accounting.invoices.show', $invoice)
                ->with('success', 'Payment recorded successfully.');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function collections(Request $request)
    {
        $this->requirePermission($request, 'invoices.collections');
        $companyId = session('current_company_id');

        $query = Invoice::query()
            ->where('company_id', $companyId)
            ->with(['lines.product', 'payments', 'sourceQuotation', 'company', 'costCenter']);
        $result = $this->arInvoiceService->collectionsQueue($query, 25, $request->integer('page', 1));

        $today = Carbon::today();
        $result['invoices']->getCollection()->transform(function ($invoice) use ($today) {
            $invoice->aging = $this->agingChipFor($invoice, $today);

            return $invoice;
        });

        $cs = (string) SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        // Same workspace/payment payload as the register so a queue row click
        // opens the spec modal and the green button records a payment (queue.html
        // tfoot). Totals are FULL-queue (services `count` is not the page count).
        $ivPayload = $this->registerPayload(
            $result['invoices']->getCollection()->all(),
            $request->user(),
            $cs
        );
        $nextReceipt = $this->arInvoiceService->previewReceiptNumber($companyId);

        return view('accounting.invoices.collections', [
            'invoices' => $result['invoices'],
            'totals' => $result['totals'],
            'cs' => $cs,
            'ivPayload' => $ivPayload,
            'nextReceipt' => $nextReceipt,
        ]);
    }

    /**
     * CSV export of the register (§2 — the toolbar's Export button). Honours the
     * exact same filter set as the on-screen table so "what I see is what I get".
     */
    public function export(Request $request)
    {
        $this->requirePermission($request, 'invoices.export');
        $companyId = session('current_company_id');

        $filters = $this->registerFilters($request);
        $query = $this->arInvoiceService->registerQuery($filters, $companyId);
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        return $this->invoiceCsv($query->get(), 'invoices');
    }

    /** CSV export of the collections queue (§3 — the queue's Export button). */
    public function collectionsExport(Request $request)
    {
        $this->requirePermission($request, 'invoices.export');
        $companyId = session('current_company_id');

        $query = Invoice::query()->where('company_id', $companyId);
        $queue = $this->arInvoiceService->collectionsQueue($query, 10000, 1)['invoices'];

        return $this->invoiceCsv($queue->getCollection()->values(), 'collections-queue');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Invoice>  $invoices
     */
    private function invoiceCsv($invoices, string $basename)
    {
        return response()->streamDownload(function () use ($invoices) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Invoice #', 'Customer', 'Issue date', 'Due date', 'Status',
                'Amount', 'Paid', 'Balance', 'Days overdue',
            ]);

            foreach ($invoices as $invoice) {
                fputcsv($out, [
                    $invoice->invoice_number,
                    $invoice->partyName(),
                    $invoice->issueDate()?->format('Y-m-d') ?? '',
                    $invoice->due_date?->format('Y-m-d') ?? '',
                    $invoice->collectionStatus(),
                    number_format((float) $invoice->documentTotal(), 2, '.', ''),
                    number_format((float) $invoice->amount_paid, 2, '.', ''),
                    number_format((float) $invoice->balance_due, 2, '.', ''),
                    $invoice->isOverdue() ? $invoice->daysOverdue() : 0,
                ]);
            }

            fclose($out);
        }, $basename.'-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
