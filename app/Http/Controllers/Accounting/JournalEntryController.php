<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\AccountingPeriod;
use App\Models\SystemSetting;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Accounting\JournalReversalService;
use App\Services\Accounting\JournalTypeClassifier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JournalEntryController extends Controller
{
    protected JournalPostingEngine $postingEngine;

    protected JournalReversalService $reversalService;

    public function __construct(
        JournalPostingEngine $postingEngine,
        JournalReversalService $reversalService,
    ) {
        $this->postingEngine = $postingEngine;
        $this->reversalService = $reversalService;
    }

    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');
        $user = $request->user();

        $filters = $this->resolveFilters($request);

        $journalEntries = $this->baseQuery($companyId, $filters)
            ->with(['createdBy', 'branch', 'reversalEntry'])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $payload = $this->buildPayload($companyId, $journalEntries->getCollection());

        $stats = $this->statusCounts($companyId, $filters);

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $typeOptions = JournalTypeClassifier::options();

        [$periodOptions, $periodLabel] = $this->periodOptions($companyId);

        $defaultAccount = Account::where('company_id', $companyId)
            ->active()
            ->orderBy('code')
            ->first();
        $defaultAccount = $defaultAccount ? [
            'id' => $defaultAccount->id,
            'code' => $defaultAccount->code,
            'name' => $defaultAccount->name,
        ] : null;

        $cs = $this->currencySymbol($companyId);

        $can = [
            'finalize' => (bool) $user?->can('journal-entries.edit'),
            'post' => (bool) $user?->can('journal-entries.post'),
            'reverse' => (bool) $user?->can('journal-entries.reverse'),
            'delete' => (bool) $user?->can('journal-entries.edit'),
        ];

        return view('accounting.journal-entries.index', compact(
            'journalEntries',
            'branches',
            'stats',
            'payload',
            'filters',
            'typeOptions',
            'periodOptions',
            'periodLabel',
            'defaultAccount',
            'cs',
            'can'
        ));
    }

    /**
     * Normalise the register's filter inputs.
     *
     * @return array<string, mixed>
     */
    private function resolveFilters(Request $request): array
    {
        $status = (string) $request->input('status', '');
        $statusMap = [
            'unfinalized' => 'draft',
            'unposted' => 'pending_approval',
        ];

        return [
            'status' => $status,
            'status_key' => $status === '' ? 'all' : $status,
            'search' => trim((string) $request->input('search', '')),
            'type' => (string) $request->input('type', ''),
            'branch_id' => $request->filled('branch_id') ? (int) $request->input('branch_id') : null,
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'period' => (string) $request->input('period', ''),
            'mode' => $request->input('mode') === 'period' ? 'period' : 'range',
            'resolved_status' => $statusMap[$status] ?? $status,
        ];
    }

    private function baseQuery(int $companyId, array $filters, bool $withStatus = true)
    {
        $query = JournalEntry::where('company_id', $companyId);

        if ($withStatus && $filters['resolved_status'] !== '' && $filters['resolved_status'] !== 'all') {
            if ($filters['resolved_status'] === 'pending_approval') {
                $query->whereIn('status', ['pending_approval', 'approved']);
            } else {
                $query->where('status', $filters['resolved_status']);
            }
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('journal_number', 'like', "%{$search}%")
                    ->orWhere('memo', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $this->applyTypeFilter($query, $filters['type']);

        if ($filters['branch_id']) {
            $query->forBranch($filters['branch_id']);
        }

        [$from, $to] = $this->resolveDateRange($companyId, $filters);

        if ($from) {
            $query->where('date', '>=', $from);
        }

        if ($to) {
            $query->where('date', '<=', $to);
        }

        return $query;
    }

    private function applyTypeFilter($query, string $type): void
    {
        if ($type === '' || !JournalTypeClassifier::isValid($type)) {
            return;
        }

        $modules = JournalTypeClassifier::modulesFor($type);

        if ($modules === null) {
            $grouped = JournalTypeClassifier::allGroupedModules();
            $query->where(function ($q) use ($grouped) {
                $q->whereNull('source_module')->orWhereNotIn('source_module', $grouped);
            });

            return;
        }

        $query->whereIn('source_module', $modules);
    }

    /**
     * Resolve the active date window. Period mode wins over the raw range.
     *
     * @param  array<string, mixed>  $filters
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveDateRange(int $companyId, array $filters): array
    {
        if ($filters['mode'] === 'period' && $filters['period'] !== '') {
            $range = $this->periodRange($companyId, $filters['period']);

            if ($range) {
                return [$range[0]->toDateString(), $range[1]->toDateString()];
            }
        }

        return [$filters['date_from'] ?: null, $filters['date_to'] ?: null];
    }

    /**
     * @return array<string, array{label: string, from: string, to: string}>
     */
    private function periodOptionMap(int $companyId): array
    {
        $now = now();

        $map = [
            'this_month' => ['label' => 'This Month', 'from' => $now->copy()->startOfMonth(), 'to' => $now->copy()->endOfMonth()],
            'last_month' => ['label' => 'Last Month', 'from' => $now->copy()->subMonthNoOverflow()->startOfMonth(), 'to' => $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => ['label' => 'This Quarter', 'from' => $now->copy()->startOfQuarter(), 'to' => $now->copy()->endOfQuarter()],
            'ytd' => ['label' => 'Year to Date', 'from' => $now->copy()->startOfYear(), 'to' => $now->copy()->endOfDay()],
        ];

        $fiscalYear = FiscalYear::where('company_id', $companyId)
            ->where('start_date', '<=', $now->toDateString())
            ->where('end_date', '>=', $now->toDateString())
            ->first();

        if ($fiscalYear) {
            $map['fy'] = ['label' => $fiscalYear->label ?: 'This Fiscal Year', 'from' => $fiscalYear->start_date->copy()->startOfDay(), 'to' => $fiscalYear->end_date->copy()->endOfDay()];
        } else {
            $map['fy'] = ['label' => 'This Fiscal Year', 'from' => $now->copy()->startOfYear(), 'to' => $now->copy()->endOfYear()];
        }

        return $map;
    }

    /**
     * @return array{0: array<int, string>, 1: string}
     */
    private function periodOptions(int $companyId): array
    {
        $map = $this->periodOptionMap($companyId);

        return [
            array_map(fn ($row) => $row['label'], $map),
            $map['fy']['label'] ?? 'This Fiscal Year',
        ];
    }

    /**
     * @return array{0: \Carbon\Carbon|null, 1: \Carbon\Carbon|null}|null
     */
    private function periodRange(int $companyId, string $key): ?array
    {
        $map = $this->periodOptionMap($companyId);

        if (!isset($map[$key])) {
            return null;
        }

        return [$map[$key]['from'], $map[$key]['to']];
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(int $companyId, array $filters): array
    {
        $rows = $this->baseQuery($companyId, $filters, false)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = [
            'unfinalized' => (int) ($rows['draft'] ?? 0),
            'unposted' => (int) ($rows['pending_approval'] ?? 0) + (int) ($rows['approved'] ?? 0),
            'posted' => (int) ($rows['posted'] ?? 0),
            'reversed' => (int) ($rows['reversed'] ?? 0),
        ];
        $counts['all'] = array_sum($counts);

        return $counts;
    }

    /**
     * Build the modal's JSON payload for the current page plus any referenced
     * reversal entries.
     *
     * @param  \Illuminate\Support\Collection<int, JournalEntry>  $entries
     * @return array<int, array<string, mixed>>
     */
    private function buildPayload(int $companyId, $entries): array
    {
        $entries = $entries->load(['lines.account', 'lines.costCenter', 'reversalEntry.lines.account', 'reversalEntry.lines.costCenter']);

        $payload = [];
        $seen = [];

        foreach ($entries as $entry) {
            $payload[] = $this->entryPayload($entry);
            $seen[$entry->id] = true;
        }

        foreach ($entries as $entry) {
            $linked = $entry->reversalEntry;

            if ($linked && !isset($seen[$linked->id])) {
                $payload[] = $this->entryPayload($linked);
                $seen[$linked->id] = true;
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function entryPayload(JournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'no' => $entry->journal_number,
            'status' => $entry->status,
            'type' => JournalTypeClassifier::label($entry->source_module, (bool) $entry->is_adjusting_entry),
            'date' => $entry->date?->format('d M Y'),
            'dateRaw' => $entry->date?->format('Y-m-d'),
            'source' => $entry->source_module ?: 'manual',
            'branch' => $entry->branch?->name ?? '—',
            'creator' => $entry->created_by,
            'creatorName' => $entry->createdBy?->name ?? '—',
            'memo' => $entry->memo ?: ($entry->reference ?? ''),
            'reference' => $entry->reference,
            'reversalId' => $entry->reversal_entry_id,
            'reversalNo' => $entry->reversalEntry?->journal_number,
            'urls' => [
                'show' => route('accounting.journal-entries.show', $entry->id),
                'finalize' => route('accounting.journal-entries.finalize', $entry->id),
                'post' => route('accounting.journal-entries.post', $entry->id),
                'reopen' => route('accounting.journal-entries.reopen', $entry->id),
                'destroy' => route('accounting.journal-entries.destroy', $entry->id),
                'reverse' => route('accounting.journal-entries.reverse', $entry->id),
            ],
            'lines' => $entry->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'a' => $line->account?->code ?? '—',
                'n' => $line->account?->name ?? '',
                'd' => $line->memo ?? '',
                'dr' => (float) $line->debit == 0.0 ? null : (float) $line->debit,
                'cr' => (float) $line->credit == 0.0 ? null : (float) $line->credit,
                'cc' => $line->costCenter?->code ?? '—',
            ])->values()->all(),
        ];
    }

    private function currencySymbol(int $companyId): string
    {
        $baseCurrency = Company::find($companyId)?->base_currency;

        if ($baseCurrency) {
            $symbol = Currency::where('code', $baseCurrency)->value('symbol');

            if ($symbol) {
                return $symbol;
            }
        }

        return SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');
    }


    public function create()
    {
        $companyId = session('current_company_id');

        $accounts = Account::where('company_id', $companyId)
            ->active()
            ->orderBy('code')
            ->get();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $costCenters = CostCenter::where('company_id', $companyId)->active()->orderBy('code')->get();

        return view('accounting.journal-entries.create', compact('accounts', 'branches', 'costCenters'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'memo' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
            'is_adjusting_entry' => 'sometimes|boolean',
            'action' => 'required|in:save_draft,post',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:500',
            'lines.*.branch_id' => 'nullable|exists:branches,id',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $data = [
            'company_id' => $companyId,
            'created_by' => $userId,
            'date' => $validated['date'],
            'reference' => $validated['reference'] ?? null,
            'memo' => $validated['memo'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'is_adjusting_entry' => $validated['is_adjusting_entry'] ?? false,
            'lines' => array_map(function ($line) {
                return [
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                    'branch_id' => $line['branch_id'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                ];
            }, $validated['lines']),
        ];

        try {
            if ($validated['action'] === 'save_draft') {
                $entry = $this->postingEngine->postAsDraft($data);
            } else {
                $entry = $this->postingEngine->post($data);
            }

            return redirect()->route('accounting.journal-entries.show', $entry)
                ->with('success', 'Journal entry created successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(JournalEntry $journalEntry)
    {
        if (!$journalEntry->isDraft()) {
            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('error', 'Only draft journal entries can be edited. Posted entries must be reversed.');
        }

        $companyId = session('current_company_id');
        $journalEntry->load('lines.account', 'lines.costCenter', 'lines.branch');

        $accounts = Account::where('company_id', $companyId)->active()->orderBy('code')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->active()->orderBy('code')->get();
        $currencies = Currency::query()->active()->ordered()->get();

        return view('accounting.journal-entries.edit', compact('journalEntry', 'accounts', 'branches', 'costCenters', 'currencies'));
    }

    public function update(Request $request, JournalEntry $journalEntry)
    {
        if (!$journalEntry->isDraft()) {
            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('error', 'Only draft journal entries can be edited.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'memo' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
            'is_adjusting_entry' => 'sometimes|boolean',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:500',
            'lines.*.branch_id' => 'nullable|exists:branches,id',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        try {
            $journalEntry->update([
                'date' => $validated['date'],
                'reference' => $validated['reference'] ?? null,
                'memo' => $validated['memo'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
                'is_adjusting_entry' => $validated['is_adjusting_entry'] ?? false,
            ]);

            $journalEntry->lines()->delete();
            foreach ($validated['lines'] as $line) {
                $journalEntry->lines()->create([
                    'company_id' => session('current_company_id'),
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                    'branch_id' => $line['branch_id'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                ]);
            }

            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('success', 'Journal entry updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(JournalEntry $journalEntry)
    {
        $companyId = session('current_company_id');

        $journalEntry->load([
            'lines.account',
            'lines.branch',
            'lines.costCenter',
            'branch',
            'createdBy',
            'postedByUser',
            'approvedByUser',
            'rejectedByUser',
            'linkedEntry',
            'reversalEntry',
            'auditLogs.user',
        ]);

        $pendingReversal = JournalEntry::where('company_id', $companyId)
            ->where('source_module', 'reversal')
            ->where('linked_entry_id', $journalEntry->id)
            ->whereIn('status', [JournalEntry::STATUS_DRAFT, JournalEntry::STATUS_PENDING_APPROVAL])
            ->orderByDesc('id')
            ->first();

        $reversalId = $journalEntry->reversal_entry_id;
        $appliedReversal = $reversalId
            ? JournalEntry::where('company_id', $companyId)->findOrFail($reversalId)
            : null;

        $period = AccountingPeriod::where('company_id', $companyId)
            ->where('start_date', '<=', $journalEntry->date)
            ->where('end_date', '>=', $journalEntry->date)
            ->first();

        $baseCurrency = Company::find($companyId)?->base_currency;
        $cs = $baseCurrency
            ? (Currency::where('code', $baseCurrency)->first()?->symbol ?: '$')
            : SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        return view('accounting.journal-entries.show', compact(
            'journalEntry',
            'pendingReversal',
            'appliedReversal',
            'period',
            'cs',
        ));
    }

    public function submitForApproval(JournalEntry $journalEntry)
    {
        $this->requirePermission('journal-entries.submit');
        try {
            $this->postingEngine->submitForApproval($journalEntry->id);

            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('success', 'Journal entry submitted for approval.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(JournalEntry $journalEntry)
    {
        $this->requirePermission('journal-entries.approve');
        try {
            $this->postingEngine->approve($journalEntry->id, auth()->id());

            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('success', 'Journal entry approved and posted.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, JournalEntry $journalEntry)
    {
        $this->requirePermission($request, 'journal-entries.reject');
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        try {
            $this->postingEngine->reject($journalEntry->id, auth()->id(), $request->rejection_reason);

            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('success', 'Journal entry rejected.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reverse(Request $request, JournalEntry $journalEntry)
    {
        $this->requirePermission($request, 'journal-entries.reverse');

        $request->validate([
            'reversal_date' => 'required|date',
            'reference' => 'nullable|string|max:60',
            'memo' => 'required|string|max:1000',
            'post_mode' => 'required|in:immediate,draft',
        ]);

        $threshold = JournalReversalService::identityVerifyThreshold();
        $reversalTotal = (float) $journalEntry->total_debit;
        if ($threshold !== null && $reversalTotal >= $threshold) {
            $request->validate([
                'identity_confirm' => 'accepted',
            ], [
                'identity_confirm.accepted' => 'Please confirm your identity to reverse entries at or above ' .
                    number_format($threshold, 2) . '.',
            ]);
        }

        try {
            if ($request->post_mode === 'draft') {
                $this->reversalService->createDraft($journalEntry, auth()->id(), $request->only([
                    'reversal_date', 'reference', 'memo',
                ]));

                return redirect()->route('accounting.journal-entries.show', $journalEntry)
                    ->with('success', 'Reversal draft created. Review and post it to complete the reversal.');
            }

            $reversal = $this->reversalService->createAndPost($journalEntry, auth()->id(), $request->only([
                'reversal_date', 'reference', 'memo',
            ]));

            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('success', "Reversal entry {$reversal->journal_number} created and posted.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function postReversal(Request $request, JournalEntry $journalEntry)
    {
        $this->requirePermission($request, 'journal-entries.reverse');

        $companyId = session('current_company_id');

        $pending = JournalEntry::where('company_id', $companyId)
            ->where('source_module', 'reversal')
            ->where('linked_entry_id', $journalEntry->id)
            ->where('status', JournalEntry::STATUS_DRAFT)
            ->orderByDesc('id')
            ->firstOrFail();

        try {
            $this->reversalService->postDraft($pending, auth()->id());

            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('success', "Reversal entry {$pending->journal_number} posted.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function discardReversal(Request $request, JournalEntry $journalEntry)
    {
        $this->requirePermission($request, 'journal-entries.reverse');

        $companyId = session('current_company_id');

        $pending = JournalEntry::where('company_id', $companyId)
            ->where('source_module', 'reversal')
            ->where('linked_entry_id', $journalEntry->id)
            ->where('status', JournalEntry::STATUS_DRAFT)
            ->orderByDesc('id')
            ->firstOrFail();

        try {
            $this->reversalService->discardDraft($pending, auth()->id());

            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('success', 'Reversal draft discarded.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Move a draft journal to the finalized (unposted) state.
     */
    public function finalize(Request $request, JournalEntry $journalEntry)
    {
        $this->requirePermission($request, 'journal-entries.edit');

        if ((int) $journalEntry->created_by !== (int) $request->user()->id) {
            return $this->registerRedirect($request)
                ->with('error', 'Only the creator of a draft journal can finalize it.');
        }

        $lines = null;

        if ($request->filled('lines')) {
            $validated = $request->validate([
                'lines' => 'required|array|min:2',
                'lines.*.account_id' => 'required|exists:accounts,id',
                'lines.*.debit' => 'nullable|numeric|min:0',
                'lines.*.credit' => 'nullable|numeric|min:0',
                'lines.*.memo' => 'nullable|string|max:500',
                'lines.*.branch_id' => 'nullable|exists:branches,id',
                'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
            ]);

            $lines = array_map(fn ($line) => [
                'account_id' => $line['account_id'],
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'memo' => $line['memo'] ?? null,
                'branch_id' => $line['branch_id'] ?? null,
                'cost_center_id' => $line['cost_center_id'] ?? null,
            ], $validated['lines']);
        }

        try {
            $this->postingEngine->finalize($journalEntry->id, $request->user()->id, $lines);
        } catch (\InvalidArgumentException $e) {
            return $this->registerRedirect($request)->with('error', $e->getMessage());
        }

        return $this->registerRedirect($request)
            ->with('success', "Journal {$journalEntry->journal_number} finalized — saved for authorisation / posting.");
    }

    /**
     * Post a finalized journal to the General Ledger.
     */
    public function postFinalized(Request $request, JournalEntry $journalEntry)
    {
        $this->requirePermission($request, 'journal-entries.post');

        try {
            $this->postingEngine->postFinalized($journalEntry->id, $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return $this->registerRedirect($request)->with('error', $e->getMessage());
        }

        return $this->registerRedirect($request)
            ->with('success', "Journal {$journalEntry->journal_number} posted to the General Ledger.");
    }

    /**
     * Return a finalized journal to the draft state with a recorded reason.
     */
    public function reopen(Request $request, JournalEntry $journalEntry)
    {
        $this->requirePermission($request, 'journal-entries.edit');

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $this->postingEngine->reopen($journalEntry->id, $request->user()->id, $validated['reason'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return $this->registerRedirect($request)->with('error', $e->getMessage());
        }

        return $this->registerRedirect($request)
            ->with('success', "Journal {$journalEntry->journal_number} reopened — moved back to unfinalized.");
    }

    /**
     * Delete an unfinalized draft (creator only).
     */
    public function destroy(Request $request, JournalEntry $journalEntry)
    {
        $this->requirePermission($request, 'journal-entries.edit');

        if (! $journalEntry->isDraft()) {
            return $this->registerRedirect($request)
                ->with('error', 'Only unfinalized (draft) journals can be deleted.');
        }

        if ((int) $journalEntry->created_by !== (int) $request->user()->id) {
            return $this->registerRedirect($request)
                ->with('error', 'Only the creator of a draft journal can delete it.');
        }

        $number = $journalEntry->journal_number;

        try {
            $this->postingEngine->deleteDraft($journalEntry, $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return $this->registerRedirect($request)->with('error', $e->getMessage());
        }

        return $this->registerRedirect($request)
            ->with('success', "Draft journal {$number} deleted.");
    }

    /**
     * CSV export of the register using the active filters.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $this->requirePermission($request, 'journal-entries.view');

        $companyId = (int) session('current_company_id');
        $filters = $this->resolveFilters($request);

        $entries = $this->baseQuery($companyId, $filters)
            ->with(['createdBy', 'branch'])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $filename = 'journal-register-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($entries) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Journal No', 'Date', 'Type', 'Description', 'Source',
                'Branch', 'Lines', 'Total', 'Status', 'Prepared By',
            ]);

            foreach ($entries as $entry) {
                fputcsv($handle, [
                    $entry->journal_number,
                    $entry->date?->format('Y-m-d'),
                    JournalTypeClassifier::label($entry->source_module, (bool) $entry->is_adjusting_entry),
                    $entry->memo ?: ($entry->reference ?? ''),
                    $entry->source_module ?: 'manual',
                    $entry->branch?->name ?? '',
                    $entry->lines_count,
                    number_format((float) $entry->total_debit, 2, '.', ''),
                    $entry->status,
                    $entry->createdBy?->name ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function registerRedirect(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('accounting.journal-entries.index', $request->only([
            'status', 'search', 'type', 'branch_id', 'date_from', 'date_to', 'period', 'mode', 'page',
        ]));
    }
}
