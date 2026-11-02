<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationEmailJob;
use App\Services\Accounting\QuotationEmailDispatchService;
use App\Services\Accounting\QuotationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $sort = $request->input('sort', 'date-desc');
        $activeView = $request->input('view', '');

        $quotations = $this->baseQuery($request);
        foreach ($this->orderByFor($sort) as $column => $direction) {
            $quotations->orderBy($column, $direction);
        }
        $quotations = $quotations->paginate(20)->withQueryString();

        $stats = Quotation::forCompany($companyId)
            ->selectRaw('status, COUNT(*) as total, COALESCE(SUM(total), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $statsTotal = Quotation::forCompany($companyId)->count();

        $kpis = $this->registerKpis($companyId);
        $needsAttention = $this->needsAttention($companyId);
        $emailFailures = $this->emailFailures($companyId);

        $defaultIncomeAccountId = Account::where('company_id', $companyId)
            ->where('type', 'revenue')->where('is_active', true)->orderBy('code')->value('id');

        $currencyNames = Currency::query()->pluck('name', 'code')->all();
        $company = \App\Models\Company::find($companyId);

        $viewPayload = [];
        $failedIds = $emailFailures->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach ($quotations as $quotation) {
            $viewPayload[$quotation->id] = $this->viewPayload($quotation, $currencyNames, $failedIds);
        }

        /* R6 · Needs-attention + email-failure rail items deep-link INTO the View
           modal, so their payload must exist even when the quotation is not on the
           current page of results. */
        $railIds = $needsAttention->pluck('id')
            ->merge($emailFailures->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->diff(array_map('intval', array_keys($viewPayload)))
            ->unique()
            ->values();
        if ($railIds->isNotEmpty()) {
            $railRows = Quotation::forCompany($companyId)
                ->with(['customer', 'createdByUser', 'lines', 'supersededBy', 'branch', 'costCenter'])
                ->whereIn('id', $railIds)
                ->get()
                ->keyBy('id');
            foreach ($railIds as $rid) {
                if (isset($railRows[$rid]) && !isset($viewPayload[$rid])) {
                    $viewPayload[$rid] = $this->viewPayload($railRows[$rid], $currencyNames, $failedIds);
                }
            }
        }

        return view('accounting.quotations.index', compact(
            'quotations', 'stats', 'statsTotal', 'sort', 'activeView', 'kpis', 'needsAttention', 'emailFailures', 'viewPayload', 'defaultIncomeAccountId', 'company'
        ));
    }

    /**
     * §4.1 View modal payload — meta chips, lifecycle steps, lines, totals,
     * notes, status-aware footer actions and their URLs.
     */
    private function viewPayload(Quotation $quotation, array $currencyNames = [], array $failedIds = []): array
    {
        $termsLabels = [
            'receipt' => __('Due on receipt'),
            'net7' => __('Net 7'),
            'net15' => __('Net 15'),
            'net30' => __('Net 30'),
            'net60' => __('Net 60'),
        ];

        $discount = (float) $quotation->lines->sum('discount');

        $lines = $quotation->lines->map(function ($line) {
            return [
                'desc' => $line->description ?: ($line->item_name ?: ($line->item_code ?: '—')),
                'qty' => (float) $line->quantity,
                'unit' => (float) $line->unit_price,
                'disc' => (float) $line->disc_pct,
                'tax' => (float) $line->tax_rate,
                'account' => $line->income_account_id,
                'amount' => (float) $line->amount,
            ];
        })->values()->all();

        $user = auth()->user();
        $isCreator = (int) $quotation->created_by === (int) ($user?->id);

        return [
            'id' => $quotation->id,
            'number' => $quotation->quotation_number,
            'status' => $quotation->status,
            'customer_id' => $quotation->customer_id,
            'customer' => $quotation->customer->name ?? $quotation->customer_name ?? '—',
            'contact' => $quotation->contact_person ?: ($quotation->customer->display_name ?? ''),
            'email' => $quotation->email ?: ($quotation->customer->email ?? ''),
            'phone' => $quotation->phone ?: ($quotation->customer->phone ?? ''),
            'date' => optional($quotation->quotation_date)->format('Y-m-d'),
            'valid' => optional($quotation->valid_until)->format('Y-m-d'),
            'reference' => $quotation->reference,
            'currency' => $quotation->currency,
            'terms_key' => $quotation->terms_key,
            'terms' => $termsLabels[$quotation->terms_key] ?? ($quotation->terms_key ?: __('Due on receipt')),
            'currency_name' => $currencyNames[$quotation->currency] ?? '',
            'branch' => $quotation->branch->name ?? ($quotation->branch_id ? '' : __('Head Office')),
            'cc' => $quotation->costCenter->name ?? '',
            'prepared' => $quotation->createdByUser->name ?? '',
            'ref_no' => $quotation->reference,
            'notes' => $quotation->memo,
            'internal' => $quotation->notes_internal,
            'sub' => (float) $quotation->amount,
            'disc' => $discount,
            'tax' => (float) $quotation->tax_total,
            'total' => (float) $quotation->total,
            'words' => amount_to_words((float) $quotation->total, $currencyNames[$quotation->currency] ?? $quotation->currency),
            'superseded' => $quotation->supersededBy?->quotation_number,
            // §2.1 — the dispatch columns are authoritative; the audit-derived
            // flag stays as the fallback for rows written before they existed.
            'emailed' => $quotation->hasEmailed(),
            'emailed_to' => $quotation->emailed_to,
            'emailed_at' => optional($quotation->emailed_at)->format('Y-m-d H:i:s'),
            'email_status' => $quotation->email_status,
            'fail_reason' => $quotation->fail_reason,
            'email_failed' => $quotation->emailFailed() || in_array((int) $quotation->id, $failedIds, true),
            'email_ready' => $this->emailIsValid($quotation->email ?: ($quotation->customer->email ?? null)),
            'expired' => $quotation->expiryState() === 'expired',
            'days' => $quotation->daysUntilExpiry(),
            'lines' => $lines,
            'urls' => [
                'edit' => route('accounting.quotations.edit', $quotation),
                'show' => route('accounting.quotations.show', $quotation),
                'print' => route('accounting.quotations.print', $quotation),
                'pdf' => route('accounting.quotations.pdf', $quotation),
                'void' => route('accounting.quotations.void', $quotation),
                'accept' => route('accounting.quotations.accept', $quotation),
                'decline' => route('accounting.quotations.decline', $quotation),
                'convert' => route('accounting.quotations.convert-to-invoice', $quotation),
                'requote' => route('accounting.quotations.requote', $quotation),
                'duplicate' => route('accounting.quotations.requote', $quotation),
                'email' => route('accounting.quotations.email', $quotation),
                'email_retry' => route('accounting.quotations.email-retry', $quotation),
            ],
            'can' => [
                'edit' => $isCreator && $quotation->isDraft() && (bool) ($user?->can('quotations.edit')),
                'void' => !$isCreator && in_array($quotation->status, [Quotation::STATUS_DRAFT, Quotation::STATUS_SENT], true) && (bool) ($user?->can('quotations.void')),
                'accept' => !$isCreator && $quotation->status === Quotation::STATUS_SENT && (bool) ($user?->can('quotations.approve')),
                'decline' => !$isCreator && $quotation->status === Quotation::STATUS_SENT && (bool) ($user?->can('quotations.approve')),
                // Maker-checker is enforced where it matters: accept/decline carry
                // sod:quotation, so the creator can never approve their own quote.
                // Fulfilment of an ALREADY-ACCEPTED quote is not an approval step,
                // so it is available to the creator too (it was hidden for them).
                'convert' => $quotation->status === Quotation::STATUS_ACCEPTED && (bool) ($user?->can('quotations.convert')),
                'requote' => $quotation->isRequotable() && (bool) ($user?->can('quotations.create')),
                // §2.4 — send and retry are both gated on quotations.send_email.
                'email' => !in_array($quotation->status, [Quotation::STATUS_DRAFT, Quotation::STATUS_VOID], true) && (bool) ($user?->can('quotations.send_email')),
                'email_retry' => $quotation->emailFailed() && !in_array($quotation->status, [Quotation::STATUS_DRAFT, Quotation::STATUS_VOID], true) && (bool) ($user?->can('quotations.send_email')),
            ],
        ];
    }

    /**
     * §2.1 KPI tiles — open pipeline value, win rate, expiring ≤7d, converted value.
     */
    private function registerKpis(int $companyId): array
    {
        $openRows = Quotation::forCompany($companyId)
            ->whereIn('status', [Quotation::STATUS_DRAFT, Quotation::STATUS_SENT])
            ->get(['id', 'total', 'valid_until', 'status']);

        $accepted = Quotation::forCompany($companyId)->where('status', Quotation::STATUS_ACCEPTED)->count();
        $declined = Quotation::forCompany($companyId)->where('status', Quotation::STATUS_DECLINED)->count();
        $decided = $accepted + $declined;

        $expiring = $openRows->filter(function ($q) {
            $state = $q->expiryState();
            return $state === 'expired' || $state === 'soon';
        })->count();

        $convertedValue = (float) Quotation::forCompany($companyId)->where('status', Quotation::STATUS_CONVERTED)->sum('total');
        $convertedCount = Quotation::forCompany($companyId)->where('status', Quotation::STATUS_CONVERTED)->count();

        return [
            'open_pipeline' => (float) $openRows->sum('total'),
            'open_count' => $openRows->count(),
            'win_rate' => $decided > 0 ? round(100 * $accepted / $decided, 1) : null,
            'accepted' => $accepted,
            'declined' => $declined,
            'expiring' => $expiring,
            'converted_value' => $convertedValue,
            'converted_count' => $convertedCount,
        ];
    }

    /**
     * §R6 Needs-attention rail — OPEN quotes already expired or expiring ≤7 days.
     */
    private function needsAttention(int $companyId): \Illuminate\Support\Collection
    {
        return Quotation::forCompany($companyId)
            ->with('customer')
            ->whereIn('status', [Quotation::STATUS_DRAFT, Quotation::STATUS_SENT])
            ->whereNotNull('valid_until')
            ->orderBy('valid_until')
            ->get()
            ->filter(function ($q) {
                $state = $q->expiryState();
                return $state === 'expired' || $state === 'soon';
            })
            ->take(8)
            ->values();
    }

    /**
 * §6.3 — quotations whose most recent email attempt ended in failure.
 *
 * A failed dispatch is derived from the audit trail rather than new columns:
 * only quotations whose latest `emailed`/`email_failed` audit is a failure
 * are surfaced, so a later successful retry clears the flag automatically.
 * Rows stamped by the §2.1 `email_status` column are unioned in so the rail and
 * the register chip can never disagree about what failed.
 */
    private function emailFailures(int $companyId): \Illuminate\Support\Collection
    {
        $failedIds = AuditLog::query()
            ->where('company_id', $companyId)
            ->where('auditable_type', Quotation::class)
            ->whereIn('action', ['email_failed', 'emailed'])
            ->orderByDesc('id')
            ->get(['auditable_id', 'action'])
            ->groupBy('auditable_id')
            ->filter(fn ($rows) => $rows->first()->action === 'email_failed')
            ->keys()
            ->merge(
                Quotation::forCompany($companyId)
                    ->where('email_status', 'failed')
                    ->pluck('id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($failedIds->isEmpty()) {
            return collect();
        }

        return Quotation::forCompany($companyId)
            ->with('customer')
            ->whereIn('id', $failedIds->all())
            // R15 — a draft is never emailed, and R7 — void is terminal, so
            // neither can carry an actionable delivery failure.
            ->whereNotIn('status', [Quotation::STATUS_DRAFT, Quotation::STATUS_VOID])
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Shared filtered query for the list and the CSV export.
     * Keeps the exact search/status/customer params used by the list page.
     */
    private function baseQuery(Request $request)
    {
        $companyId = session('current_company_id');
        $status = $request->status ?: $request->view;

        return Quotation::forCompany($companyId)
            ->with(['customer', 'createdByUser', 'postedByUser', 'lines', 'supersededBy', 'branch', 'costCenter'])
            ->when($status === 'open', fn($q) => $q->whereIn('status', [Quotation::STATUS_DRAFT, Quotation::STATUS_SENT]))
            // Capture $status in the closure: Builder::when() hands the callback the
            // CONDITION (true), not this variable, so using the second argument
            // silently produced `where status = 1` and every status filter matched
            // nothing.
            ->when($status && $status !== 'open', fn($q) => $q->where('status', $status))
            ->when($request->customer_id, fn($q, $id) => $q->where('customer_id', $id))
            // §3.1 — the "Email failed" chip is searchable, along with the
            // address a dispatch was recorded against, so the register can be
            // filtered straight down to delivery problems.
            ->when($request->search, fn($q, $s) => $q->where(function ($q2) use ($s) {
                $term = mb_strtolower(trim($s));

                $q2->where('quotation_number', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
                    ->orWhere('emailed_to', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$s}%"));

                foreach (['email failed', 'failed', 'not sent', 'not emailed'] as $needle) {
                    if (str_contains($term, $needle)) {
                        $q2->orWhere('email_status', 'failed');
                    }

                    if (str_contains($term, $needle) && str_contains($needle, 'not')) {
                        $q2->orWhereNull('email_status');
                    }
                }

                foreach (['emailed', 'sent', 'delivered'] as $needle) {
                    if (str_contains($term, $needle)) {
                        $q2->orWhere('email_status', 'sent');
                    }
                }
            }));
    }

    private function orderByFor(string $sort): array
    {
        /* R5 · four sorts only: newest, oldest, value high→low, expiry soonest.
           The extra `amount-asc` / `status` keys stay as harmless fallbacks so an
           old bookmarked query string still resolves instead of 500-ing. */
        return match ($sort) {
            'date-asc' => ['quotation_date' => 'asc', 'id' => 'asc'],
            'amount-desc' => ['total' => 'desc', 'quotation_date' => 'desc'],
            'amount-asc' => ['total' => 'asc', 'quotation_date' => 'asc'],
            'expiry-asc' => ['valid_until' => 'asc', 'id' => 'asc'],
            'status' => ['status' => 'asc', 'quotation_date' => 'desc'],
            default => ['quotation_date' => 'desc', 'id' => 'desc'],
        };
    }

    public function export(Request $request)
    {
        $quotations = $this->baseQuery($request);
        foreach ($this->orderByFor($request->input('sort', 'date-desc')) as $column => $direction) {
            $quotations->orderBy($column, $direction);
        }
        $quotations = $quotations->get();

        $filename = 'quotations-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($quotations) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Quotation #', 'Customer', 'Date', 'Valid Until', 'Total', 'Status']);
            foreach ($quotations as $q) {
                fputcsv($out, [
                    $q->quotation_number,
                    $q->customer->name ?? '',
                    $q->quotation_date?->format('Y-m-d') ?? '',
                    $q->valid_until?->format('Y-m-d') ?? '',
                    number_format((float) $q->total, 2, '.', ''),
                    $q->status,
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
        $itemCategories = \App\Models\ItemCategory::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::query()->active()->ordered()->get();
        $defaultIncomeAccountId = $incomeAccounts->first()?->id;

        return view('accounting.quotations.create', compact('customers', 'branches', 'costCenters', 'incomeAccounts', 'itemCategories', 'products', 'currencies', 'defaultIncomeAccountId'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'terms_key' => 'nullable|string|max:40',
            'department' => 'nullable|string|max:120',
            'prepared_by' => 'nullable|string|max:120',
            'project' => 'nullable|string|max:120',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date|after:quotation_date',
            'currency' => 'nullable|string|max:10',
            'notes_internal' => 'nullable|string|max:5000',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.disc_pct' => 'nullable|numeric|min:0|max:100',
            'lines.*.discount' => 'nullable|numeric|min:0',
            'lines.*.income_account_id' => 'required|exists:accounts,id',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx,doc,docx,txt,csv|max:10240',
            'delete_documents' => 'nullable|array',
            'delete_documents.*' => 'integer',
        ]);

        $companyId = session('current_company_id');
        $isWalkin = !$request->filled('customer_id') && $request->filled('customer_name');
        $quotationService = app(QuotationService::class);
        $quotation = $quotationService->create($this->payloadFromRequest($request, $companyId), auth()->id());

        $this->handleAttachments($request, $quotation);

        if ($request->input('action') === 'save_and_email') {
            return $this->redirectAfterEmail($quotation, 'created');
        }

        if ($redirect = $this->applySubmissionMode($request, $quotation, $isWalkin)) {
            return $redirect;
        }

        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->quotation_number} created.");
    }

    public function destroy(Quotation $quotation)
    {
        $this->requirePermission('quotations.edit');
        abort_unless((int) $quotation->company_id === (int) session('current_company_id'), 403);

        if (!$quotation->isDraft()) {
            return redirect()->route('accounting.quotations.show', $quotation)
                ->with('error', 'Only draft quotations can be deleted.');
        }

        foreach ($quotation->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }
        $quotation->attachments()->delete();

        app(QuotationService::class)->destroy($quotation);

        return redirect()->route('accounting.quotations.index')
            ->with('success', "Quotation {$quotation->quotation_number} deleted.");
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['lines.product', 'customer', 'branch', 'costCenter', 'createdByUser', 'postedByUser', 'attachments']);
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
        $currencies = Currency::query()->active()->ordered()->get();
        $defaultIncomeAccountId = $incomeAccounts->first()?->id;

        $quotation->load(['lines', 'attachments']);

        return view('accounting.quotations.edit', compact('quotation', 'customers', 'branches', 'costCenters', 'incomeAccounts', 'itemCategories', 'products', 'currencies', 'defaultIncomeAccountId'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        if (!$quotation->isDraft()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Only draft quotations can be updated.'], 409);
            }

            return redirect()->route('accounting.quotations.show', $quotation)
                ->with('error', 'Only draft quotations can be updated.');
        }

        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'terms_key' => 'nullable|string|max:40',
            'department' => 'nullable|string|max:120',
            'prepared_by' => 'nullable|string|max:120',
            'project' => 'nullable|string|max:120',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date|after:quotation_date',
            'currency' => 'nullable|string|max:10',
            'notes_internal' => 'nullable|string|max:5000',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.disc_pct' => 'nullable|numeric|min:0|max:100',
            'lines.*.discount' => 'nullable|numeric|min:0',
            'lines.*.income_account_id' => 'required|exists:accounts,id',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx,doc,docx,txt,csv|max:10240',
            'delete_documents' => 'nullable|array',
            'delete_documents.*' => 'integer',
        ]);

        $quotationService = app(QuotationService::class);
        $quotation = $quotationService->update($quotation, $this->payloadFromRequest($request, $quotation->company_id));

        $this->handleAttachments($request, $quotation);

        if ($request->input('action') === 'save_and_email') {
            return $this->redirectAfterEmail($quotation, 'updated');
        }

        if ($redirect = $this->applySubmissionMode($request, $quotation)) {
            return $redirect;
        }

        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->quotation_number} updated.");
    }

    /**
     * Build the QuotationService payload from a request (§7.2 field set).
     */
    private function payloadFromRequest(Request $request, int $companyId): array
    {
        return [
            'company_id' => $companyId,
            'branch_id' => $request->branch_id,
            'cost_center_id' => $request->cost_center_id,
            'customer_id' => $request->customer_id,
            'customer_name' => $request->customer_name,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'terms_key' => $request->terms_key,
            'department' => $request->department,
            'prepared_by' => $request->prepared_by,
            'project' => $request->project,
            'quotation_date' => $request->quotation_date,
            'valid_until' => $request->valid_until,
            'reference' => $request->reference,
            'memo' => $request->memo,
            'notes_internal' => $request->notes_internal,
            'currency' => $request->currency,
            'lines' => $request->lines,
        ];
    }

    /**
     * Apply the §7.2 submission mode
     * (draft | finalize_later | finalize_print | finalize_send).
     * Returns a redirect for draft/finalize modes, or null to continue with
     * the default post-save redirect.
     */
    private function applySubmissionMode(Request $request, Quotation $quotation, bool $isWalkin = false): ?\Illuminate\Http\RedirectResponse
    {
        $mode = $request->input('submission_mode');

        if ($mode === 'draft') {
            return redirect()->route('accounting.quotations.show', $quotation)
                ->with('success', "Quotation {$quotation->quotation_number} saved as draft.");
        }

        if (!in_array($mode, ['finalize_later', 'finalize_print', 'finalize_send'], true)) {
            return null;
        }

        // R12 — "Save & send later" saves a draft (nothing issued yet) and shows
        // the green success confirmation dialog instead of a toast.
        if ($mode === 'finalize_later') {
            return $this->successRedirect($quotation, __('Quotation saved'), false, false, null, $isWalkin);
        }

        $recipient = $quotation->customer?->email ?: $quotation->email;

        // R11 — the email regex is enforced server-side on every send mode.
        if ($mode === 'finalize_send' && !$this->emailIsValid($recipient)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['email' => __('Provide customer email to activate this button')]);
        }

        app(QuotationService::class)->send($quotation);

        if ($mode === 'finalize_send') {
            $emailed = $this->emailQuotation($quotation);

            if (!$emailed) {
                return redirect()->route('accounting.quotations.show', $quotation)
                    ->with('warning', "Quotation {$quotation->quotation_number} finalized, but the customer has no email on file.");
            }

            return $this->successRedirect($quotation, __('Quotation finalized & sent'), true, true, $recipient, $isWalkin);
        }

        // finalize_print — finalized + print preview available from the dialog.
        return $this->successRedirect($quotation, __('Quotation finalized'), true, false, null, $isWalkin);
    }

    /**
     * R12 — flash the green success confirmation dialog payload and land on the
     * quotation show page (View / Open print preview actions).
     */
    private function successRedirect(Quotation $quotation, string $title, bool $print, bool $send, ?string $email, bool $walkin = false): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('quotation_success', [
                'title' => $title,
                'ref' => $quotation->quotation_number,
                'name' => $quotation->customer?->name ?? $quotation->customer_name,
                'total' => format_money($quotation->total),
                'status' => $quotation->status,
                'print' => $print,
                'send' => $send,
                'email' => $email,
                'walkin' => $walkin,
                'view_url' => route('accounting.quotations.show', $quotation),
                'print_url' => route('accounting.quotations.print', $quotation),
            ]);
    }

    /**
     * R11 — the send gate shared by client + server (name@domain.tld).
     */
    private function emailIsValid(?string $email): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', (string) $email);
    }

    /**
     * Redirect after a save-and-email action, distinguishing a missing
     * customer email from a successful send.
     */
    private function redirectAfterEmail(Quotation $quotation, string $verb): \Illuminate\Http\RedirectResponse
    {
        if (!$this->emailQuotation($quotation)) {
            return redirect()->route('accounting.quotations.show', $quotation)
                ->with('warning', "Quotation {$quotation->quotation_number} {$verb}, but the customer has no email address on file.");
        }

        return redirect()->route('accounting.quotations.show', $quotation)
            ->with('success', "Quotation {$quotation->quotation_number} {$verb} and emailed to {$quotation->customer->email}.");
    }

    /**
     * Persist uploaded attachments and delete any flagged for removal.
     */
    private function handleAttachments(Request $request, Quotation $quotation): void
    {
        $companyId = $quotation->company_id;

        foreach ((array) $request->input('delete_documents', []) as $id) {
            $attachment = Attachment::where('company_id', $companyId)
                ->where('id', (int) $id)
                ->where('attachmentable_type', Quotation::class)
                ->where('attachmentable_id', $quotation->id)
                ->first();

            if ($attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            }
        }

        foreach ($request->file('files', []) as $file) {
            $path = $file->storeAs(
                "quotation-attachments/{$companyId}/{$quotation->id}",
                Str::random(24) . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            $quotation->attachments()->create([
                'company_id' => $companyId,
                'name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    /**
     * A stale page or a direct POST can reach a transition the current status no
     * longer allows. The service already refuses with a precise message — surface
     * it as a flash back on the quotation instead of a 500.
     */
    private function attempt(Quotation $quotation, callable $operation, callable $onSuccess)
    {
        try {
            return $onSuccess($operation($quotation));
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('accounting.quotations.show', $quotation)
                ->with('error', $e->getMessage());
        }
    }

    public function send(Quotation $quotation)
    {
        $this->requirePermission('quotations.edit');
        $quotationService = app(QuotationService::class);

        return $this->attempt(
            $quotation,
            fn ($q) => $quotationService->send($q),
            fn ($q) => redirect()->route('accounting.quotations.show', $q)
                ->with('success', "Quotation {$q->quotation_number} marked as sent.")
        );
    }

    public function accept(Quotation $quotation)
    {
        $this->requirePermission('quotations.approve');
        $quotationService = app(QuotationService::class);

        return $this->attempt(
            $quotation,
            fn ($q) => $quotationService->accept($q),
            fn ($q) => redirect()->route('accounting.quotations.show', $q)
                ->with('success', "Quotation {$q->quotation_number} accepted.")
        );
    }

    public function decline(Quotation $quotation)
    {
        $this->requirePermission('quotations.approve');
        $quotationService = app(QuotationService::class);

        return $this->attempt(
            $quotation,
            fn ($q) => $quotationService->decline($q),
            fn ($q) => redirect()->route('accounting.quotations.show', $q)
                ->with('success', "Quotation {$q->quotation_number} declined.")
        );
    }

    public function convertToInvoice(Quotation $quotation)
    {
        $this->requirePermission('quotations.convert');
        $quotationService = app(QuotationService::class);

        return $this->attempt(
            $quotation,
            fn ($q) => $quotationService->convertToInvoice($q, auth()->id()),
            fn ($invoice) => redirect()->route('accounting.invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_number} created from Quotation {$quotation->quotation_number}.")
        );
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

        return $this->attempt(
            $quotation,
            fn ($q) => $quotationService->convertToSalesReceipt($q, ['payments' => $request->payments], auth()->id()),
            fn ($receipt) => redirect()->route('accounting.sales-receipts.show', $receipt)
                ->with('success', "Sales Receipt {$receipt->receipt_number} created from Quotation {$quotation->quotation_number}.")
        );
    }

    public function void(Quotation $quotation, Request $request)
    {
        $this->requirePermission($request, 'quotations.void');
        $request->validate(['void_reason' => 'required|string']);
        $quotationService = app(QuotationService::class);

        return $this->attempt(
            $quotation,
            fn ($q) => $quotationService->void($q, $request->void_reason, auth()->id()),
            fn ($q) => redirect()->route('accounting.quotations.show', $q)
                ->with('success', "Quotation {$q->quotation_number} voided.")
        );
    }

    /**
     * §2.3 — EMAIL DISPATCH entry point.
     *
     * JSON callers get the dispatch subsystem: recipient validated, job row
     * created, pipeline queued, `202 { job_id }` back so the progress modal can
     * poll real stage events. Legacy form posts (the needs-attention rail, saved
     * links) keep the exact redirect behaviour they have always had.
     */
    public function email(Request $request, Quotation $quotation)
    {
        $this->requirePermission($request, 'quotations.send_email');

        if ($request->expectsJson()) {
            return $this->dispatchEmail($request, $quotation, false);
        }

        $target = $request->input('return_to') === 'register'
            ? route('accounting.quotations.index')
            : route('accounting.quotations.show', $quotation);

        // R15 · DRAFTS NEVER SEND. Drafts issue through Edit → Finalize & issue,
        // so a direct POST must not put an unissued draft in a customer's inbox.
        if ($quotation->status === Quotation::STATUS_DRAFT) {
            return redirect()->to($target)
                ->with('error', "Quotation {$quotation->quotation_number} is still a draft — finalize it from Edit before emailing.");
        }

        // A voided quotation is withdrawn — it must never reach a customer, and
        // the register only withholds the action, so the route has to refuse too.
        if ($quotation->status === Quotation::STATUS_VOID) {
            return redirect()->to($target)
                ->with('error', "Quotation {$quotation->quotation_number} is void and cannot be emailed.");
        }

        $sent = $this->emailQuotation($quotation);

        // Whitelisted return targets only — the rail retries on the register,
        // the show/edit pages keep their existing destination.
        if (!$sent) {
            return redirect()->to($target)
                ->with('error', "Quotation {$quotation->quotation_number} could not be emailed — the customer has no valid email address on file.");
        }

        return redirect()->to($target)
            ->with('success', "Quotation {$quotation->quotation_number} emailed to {$quotation->emailed_to}.");
    }

    /**
     * §2.3 / §4.5 — retry a failed dispatch. Identical to a send except the
     * attempt counter carries over, so the job row shows the retry lineage.
     */
    public function retryEmail(Request $request, Quotation $quotation)
    {
        $this->requirePermission($request, 'quotations.send_email');

        return $this->dispatchEmail($request, $quotation, true);
    }

    /**
     * §2.3 — queue the four-stage pipeline and answer with the job contract the
     * modal/dock polls. Every refusal is a 422 carrying a copy-ready reason so
     * the client can surface it without guessing.
     */
    private function dispatchEmail(Request $request, Quotation $quotation, bool $isRetry)
    {
        abort_unless((int) $quotation->company_id === (int) session('current_company_id'), 403);

        $quotation->loadMissing(['customer']);

        if ($quotation->status === Quotation::STATUS_DRAFT) {
            return response()->json([
                'message' => "Quotation {$quotation->quotation_number} is still a draft — finalize it from Edit before emailing.",
                'error' => 'draft',
            ], 422);
        }

        if ($quotation->status === Quotation::STATUS_VOID) {
            return response()->json([
                'message' => "Quotation {$quotation->quotation_number} is void and cannot be emailed.",
                'error' => 'void',
            ], 422);
        }

        $recipient = trim((string) ($request->input('email')
            ?: ($quotation->customer?->email ?: $quotation->email)));

        // §2.3 — a malformed recipient is refused exactly like a missing one.
        if (!$this->emailIsValid($recipient)) {
            return response()->json([
                'message' => "Quotation {$quotation->quotation_number} could not be emailed — the customer has no valid email address on file.",
                'error' => 'no_recipient',
            ], 422);
        }

        $job = app(QuotationEmailDispatchService::class)->start($quotation, $recipient, auth()->id(), $isRetry);

        return response()->json($this->emailJobPayload($job, $quotation), 202);
    }

    /**
     * §2.3 — the stage/succeeded/failed endpoint the progress modal polls.
     *
     * The job is resolved inside the controller (not through implicit route
     * binding) so the lookup runs on the tenant connection the request bound.
     */
    public function emailJobStatus(Request $request, $emailJob)
    {
        return response()->json($this->emailJobPayload($this->findEmailJob($request, $emailJob)));
    }

    /** §4.4 — mid-send cancel. The quotation is deliberately left untouched. */
    public function cancelEmailJob(Request $request, $emailJob)
    {
        $job = $this->findEmailJob($request, $emailJob);

        return response()->json($this->emailJobPayload(app(QuotationEmailDispatchService::class)->cancel($job)));
    }

    private function findEmailJob(Request $request, $emailJob): QuotationEmailJob
    {
        $this->requirePermission($request, 'quotations.send_email');

        $job = QuotationEmailJob::find((int) $emailJob);

        abort_if(!$job, 404);
        abort_unless((int) $job->company_id === (int) session('current_company_id'), 403);

        return $job->fresh();
    }

    /**
     * The client contract (§2.2): everything the modal and the dock render is
     * derived here, so the UI never has to interpret raw job columns.
     */
    private function emailJobPayload(QuotationEmailJob $job, ?Quotation $quotation = null): array
    {
        $quotation ??= $job->quotable;
        $status = $job->clientStatus();

        return [
            'job_id' => $job->id,
            'status' => $status,
            'stage' => $job->stage,
            'stage_label' => $job->stageLabel(),
            'percent' => $job->percent(),
            'recipient' => $job->recipient,
            'attempts' => $job->attempts,
            'error_code' => $job->error_code,
            'error_message' => $job->error_message,
            'started_at' => optional($job->created_at)->toIso8601String(),
            'finished_at' => optional($job->updated_at)->toIso8601String(),
            'status_url' => route('accounting.email-jobs.show', $job->id),
            'cancel_url' => route('accounting.email-jobs.cancel', $job->id),
            'quotation' => $quotation ? [
                'id' => $quotation->id,
                'number' => $quotation->quotation_number,
                'customer' => $quotation->customer?->name ?: $quotation->customer_name,
                'email_status' => $quotation->email_status,
                'fail_reason' => $quotation->fail_reason,
                'emailed' => $quotation->hasEmailed(),
                'emailed_to' => $quotation->emailed_to,
                'emailed_at' => optional($quotation->emailed_at)->format('Y-m-d H:i:s'),
                'show_url' => route('accounting.quotations.show', $quotation),
            ] : null,
        ];
    }

    /**
     * Queue the quotation email (PDF attached, company standard template).
     *
     * §R11 — a malformed recipient is never queued, exactly like an empty one,
     * so callers can flash the same "no email on file" warning.
     */
    private function emailQuotation(Quotation $quotation): bool
    {
        $quotation->load(['lines.product', 'customer']);

        $email = $quotation->customer?->email ?: $quotation->email;

        if (!$this->emailIsValid($email)) {
            return false;
        }

        \Illuminate\Support\Facades\Mail::to($email)->queue(
            app(\App\Mail\QuotationMail::class, ['quotation' => $quotation])
        );

        app(QuotationService::class)->recordEmailed($quotation, $email);

        return true;
    }

    /**
     * §R7 — clone an expired quotation into a new record. Cloned values are
     * editable; the source row is stamped with superseded_by.
     */
    public function requote(Request $request, Quotation $quotation)
    {
        $this->requirePermission($request, 'quotations.create');
        abort_unless((int) $quotation->company_id === (int) session('current_company_id'), 403);

        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'quotation_date' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'validity_days' => 'nullable|integer|in:7,14,30,45,60',
            'reference' => 'nullable|string|max:100',
            'memo' => 'nullable|string|max:5000',
            'currency' => 'nullable|string|max:10',
            'mode' => 'nullable|in:draft,finalize',
            'send' => 'nullable|boolean',
            'finalize' => 'nullable|in:create,send',
            'lines' => 'nullable|array|min:1',
            'lines.*.description' => 'required_with:lines|string|max:500',
            'lines.*.quantity' => 'required_with:lines|numeric|min:0.01',
            'lines.*.unit_price' => 'required_with:lines|numeric|min:0',
            'lines.*.disc_pct' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_rate' => 'nullable|numeric|min:0',
            'lines.*.income_account_id' => 'required_with:lines|exists:accounts,id',
        ]);

        $date = $request->input('quotation_date') ?: now()->toDateString();
        $validUntil = $request->input('valid_until');
        if ($request->filled('validity_days')) {
            $validUntil = \Illuminate\Support\Carbon::parse($date)->addDays((int) $request->input('validity_days'))->toDateString();
        }

        $mode = $request->input('mode', $request->input('finalize') === 'send' ? 'finalize' : 'draft');
        $send = $request->boolean('send') || $request->input('finalize') === 'send';

        // R11 — enforce the email regex server-side before any send/finalize.
        if ($send && !$this->emailIsValid($request->input('email') ?: $quotation->customer?->email)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['email' => __('Provide customer email to activate this button')]);
        }

        $sourceName = trim((string) ($quotation->customer?->name ?? $quotation->customer_name ?? ''));
        $isWalkin = $request->filled('customer_name') && (
            !$request->filled('customer_id')
            || trim((string) $request->customer_name) !== $sourceName
        );

        $new = app(QuotationService::class)->requote($quotation, [
            'customer_id' => $request->customer_id,
            'customer_name' => $request->customer_name,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'is_walkin' => $isWalkin,
            'quotation_date' => $date,
            'valid_until' => $validUntil,
            'reference' => $request->reference,
            'memo' => $request->memo,
            'currency' => $request->currency,
            'lines' => $request->input('lines'),
            'status' => $mode === 'finalize' ? Quotation::STATUS_SENT : Quotation::STATUS_DRAFT,
        ], auth()->id());

        if ($send) {
            $emailed = $this->emailQuotation($new);

            if (!$emailed) {
                return redirect()->route('accounting.quotations.show', $new)
                    ->with('warning', "Quotation {$new->quotation_number} created, but the customer has no email on file.");
            }

            return $this->successRedirect($new, __('Quotation finalized & sent'), true, true, $new->customer?->email ?: $new->email, $isWalkin);
        }

        if ($mode === 'finalize') {
            return $this->successRedirect($new, __('Quotation finalized'), true, false, null, $isWalkin);
        }

        return redirect()->route('accounting.quotations.show', $new)
            ->with('success', "Quotation {$new->quotation_number} created from {$quotation->quotation_number}.");
    }

    /**
     * §G — server-rendered PDF download reusing the shared pdf.document view.
     */
    public function pdf(Quotation $quotation)
    {
        $quotation->load(['lines.product', 'customer', 'branch', 'costCenter', 'createdByUser']);
        $payload = \App\Services\PdfPresenter::payload('quotation', $quotation);
        $payload['pdfMode'] = true;

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.document', $payload)
            ->setPaper('a4')
            ->download('quotation-' . $quotation->quotation_number . '.pdf');
    }

    public function print(Quotation $quotation)
    {
        $quotation->load(['lines.product', 'customer', 'branch', 'costCenter', 'createdByUser', 'company']);
        return view('accounting.quotations.print', compact('quotation'));
    }
}
