<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ApprovalSetting;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\BankTransaction;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\Reconciliation;
use App\Models\ReconciliationAdjustment;
use App\Models\ReconciliationAuditLog;
use App\Models\ReconciliationMatch;
use App\Models\SystemSetting;
use App\Services\BankReconciliation\AuditTrailService;
use App\Services\BankReconciliation\CalculationService;
use App\Services\BankReconciliation\MatchingEngine;
use App\Services\BankReconciliation\ReconciliationService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BankReconciliationController extends Controller
{
    public function __construct(
        protected ReconciliationService $reconciliationService,
        protected CalculationService $calculationService,
        protected MatchingEngine $matchingEngine,
        protected AuditTrailService $auditTrail
    ) {
    }

    protected function companyId(): int
    {
        return (int) session('current_company_id');
    }

    protected function cs(): string
    {
        return SystemSetting::getValue('currency', 'currency_symbol', $this->companyId(), '$');
    }

    protected function bankAccount(int $bankAccountId): Account
    {
        $account = Account::where('id', $bankAccountId)
            ->where('company_id', $this->companyId())
            ->where('is_bank_account', true)
            ->first();

        abort_unless($account, 404);

        return $account;
    }

    protected function reconciliation(int $reconciliation): Reconciliation
    {
        $reconciliation = Reconciliation::where('id', $reconciliation)
            ->where('company_id', $this->companyId())
            ->firstOrFail();

        return $reconciliation;
    }

    protected function resolveReconciliation(Request $request, int $reconciliation): Reconciliation
    {
        $this->requirePermission($request, 'bank-reconciliations.view');

        return $this->reconciliation($reconciliation);
    }

    /**
     * Register of reconciliations, optionally scoped to a bank account.
     */
    public function index(?int $bankAccountId = null)
    {
        $this->requirePermission(request(), 'bank-reconciliations.view');
        $companyId = $this->companyId();
        $cs = $this->cs();

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get();

        $activeBankAccountId = $bankAccountId ?? request()->integer('bank_account_id') ?: null;
        if ($activeBankAccountId !== null && !$bankAccounts->contains('id', $activeBankAccountId)) {
            $activeBankAccountId = null;
        }

        $reconciliations = $this->registerQuery($companyId, $activeBankAccountId)
            ->paginate(15)
            ->withQueryString();

        $kpis = $this->registerKpis($companyId, $activeBankAccountId, request()->query());

        return view('accounting.bank-reconciliation.index', compact(
            'bankAccounts', 'activeBankAccountId', 'reconciliations', 'kpis', 'cs'
        ));
    }

    /**
     * Shared register query (index + export + print): company scope, optional
     * bank account and period-end window.
     */
    protected function registerQuery(int $companyId, ?int $bankAccountId = null)
    {
        $query = Reconciliation::where('company_id', $companyId)
            ->with(['bankAccount', 'createdBy', 'completedBy', 'approvedBy'])
            ->latest('id');

        if ($bankAccountId !== null) {
            $query->where('bank_account_id', $bankAccountId);
        }

        $periodFrom = request()->input('period_from');
        $periodTo = request()->input('period_to');

        if ($periodFrom || $periodTo) {
            $query->where(function ($q) use ($periodFrom, $periodTo) {
                if ($periodFrom) {
                    $q->where('period_end', '>=', $periodFrom);
                }
                if ($periodTo) {
                    $q->where('period_end', '<=', $periodTo);
                }
            });
        }

        return $query;
    }

    /**
     * §5.1 headline KPIs computed over the filtered register set.
     */
    protected function registerKpis(int $companyId, ?int $bankAccountId, array $query): array
    {
        $reconQuery = Reconciliation::where('company_id', $companyId);

        if ($bankAccountId !== null) {
            $reconQuery->where('bank_account_id', $bankAccountId);
        }

        if (!empty($query['period_from']) || !empty($query['period_to'])) {
            $reconQuery->where(function ($q) use ($query) {
                if (!empty($query['period_from'])) {
                    $q->where('period_end', '>=', $query['period_from']);
                }
                if (!empty($query['period_to'])) {
                    $q->where('period_end', '<=', $query['period_to']);
                }
            });
        }

        $rows = $reconQuery->get(['id', 'statement_balance', 'book_balance', 'difference']);
        $ids = $rows->pluck('id');

        return [
            'statement_balance' => (float) $rows->sum('statement_balance'),
            'book_balance' => (float) $rows->sum('book_balance'),
            'difference' => (float) $rows->sum('difference'),
            'matched' => $ids->isNotEmpty() ? ReconciliationMatch::whereIn('reconciliation_id', $ids)->count() : 0,
            'unmatched' => $ids->isNotEmpty() ? BankStatementLine::whereIn('reconciliation_id', $ids)
                ->whereNull('match_id')
                ->where('status', '!=', BankStatementLine::STATUS_ADJUSTED)
                ->count() : 0,
            'adjustments' => $ids->isNotEmpty() ? ReconciliationAdjustment::whereIn('reconciliation_id', $ids)
                ->where('status', '!=', ReconciliationAdjustment::STATUS_REVERSED)
                ->count() : 0,
            'count' => $rows->count(),
        ];
    }

    protected function toCsv(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($out, array_map(fn ($v) => (string) $v, $row));
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /**
     * CSV export of the filtered register.
     */
    public function export(Request $request)
    {
        $this->requirePermission($request, 'bank-reconciliations.view');
        $companyId = $this->companyId();

        $bankAccountId = $request->integer('bank_account_id') ?: null;
        $rows = $this->registerQuery($companyId, $bankAccountId)->get();

        $csv = $this->toCsv(array_merge(
            [[
                'Bank Account', 'Statement Number', 'Statement Date', 'Period Start', 'Period End',
                'Opening', 'Closing', 'Book Balance', 'Statement Balance', 'Difference', 'Status', 'Created By',
            ]],
            $rows->map(fn (Reconciliation $r) => [
                $r->bankAccount?->code . ' ' . $r->bankAccount?->name,
                $r->statement_number ?? '',
                $r->statement_date?->format('Y-m-d') ?? '',
                $r->period_start?->format('Y-m-d') ?? '',
                $r->period_end?->format('Y-m-d') ?? '',
                number_format((float) $r->opening_balance, 2),
                number_format((float) $r->closing_balance, 2),
                number_format((float) $r->book_balance, 2),
                number_format((float) $r->statement_balance, 2),
                number_format((float) $r->difference, 2),
                Reconciliation::statusLabel($r->status),
                $r->createdBy?->name ?? '',
            ])->all()
        ));

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'reconciliations-' . now()->format('Y-m-d-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * A4 print of the filtered register.
     */
    public function print(Request $request)
    {
        $this->requirePermission($request, 'bank-reconciliations.view');
        $companyId = $this->companyId();
        $cs = $this->cs();

        $bankAccountId = $request->integer('bank_account_id') ?: null;
        $rows = $this->registerQuery($companyId, $bankAccountId)->get();
        $kpis = $this->registerKpis($companyId, $bankAccountId, $request->query());
        $company = Company::find($companyId);

        return view('accounting.bank-reconciliation.print', compact('rows', 'kpis', 'cs', 'company'));
    }

    /**
     * Pill target — all statement imports across the company.
     */
    public function statements(Request $request)
    {
        $this->requirePermission($request, 'bank-reconciliations.view');
        $companyId = $this->companyId();
        $cs = $this->cs();

        $imports = BankStatementImport::where('company_id', $companyId)
            ->with(['reconciliation.bankAccount', 'importedBy'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('accounting.bank-reconciliation.statements', compact('imports', 'cs'));
    }

    /**
     * Pill target — all adjustments across the company.
     */
    public function adjustmentsList(Request $request)
    {
        $this->requirePermission($request, 'bank-reconciliations.view');
        $companyId = $this->companyId();
        $cs = $this->cs();

        $adjustments = ReconciliationAdjustment::where('company_id', $companyId)
            ->with(['reconciliation.bankAccount', 'account', 'createdBy'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('accounting.bank-reconciliation.adjustments', compact('adjustments', 'cs'));
    }

    /**
     * Pill target — outstanding (unmatched) statement lines across the company.
     */
    public function outstanding(Request $request)
    {
        $this->requirePermission($request, 'bank-reconciliations.view');
        $companyId = $this->companyId();
        $cs = $this->cs();

        $lines = BankStatementLine::where('company_id', $companyId)
            ->whereNull('match_id')
            ->with(['reconciliation.bankAccount'])
            ->latest('transaction_date')
            ->paginate(15)
            ->withQueryString();

        return view('accounting.bank-reconciliation.outstanding', compact('lines', 'cs'));
    }

    /**
     * Pill target — report cards grid (Summary / Outstanding / Unmatched /
     * Detail / History / Exceptions).
     */
    public function reports(Request $request)
    {
        $this->requirePermission($request, 'bank-reconciliations.view');
        $companyId = $this->companyId();
        $cs = $this->cs();

        $accounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get();

        $reconciliations = Reconciliation::where('company_id', $companyId)
            ->with('bankAccount')
            ->latest('id')
            ->get();

        $outOfBalance = Reconciliation::where('company_id', $companyId)
            ->where(function ($q) {
                $q->whereRaw('ABS(difference) > 0.005')->orWhereNull('difference');
            })
            ->count();

        $approvalSetting = ApprovalSetting::where('company_id', $companyId)->first();

        return view('accounting.bank-reconciliation.reports', compact('accounts', 'reconciliations', 'outOfBalance', 'approvalSetting', 'cs'));
    }

    /**
     * Pill target — audit trail across the company.
     */
    public function auditAll(Request $request)
    {
        $this->requirePermission($request, 'bank-reconciliations.view');
        $companyId = $this->companyId();
        $cs = $this->cs();

        $logs = ReconciliationAuditLog::where('company_id', $companyId)
            ->with(['reconciliation.bankAccount', 'user'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('accounting.bank-reconciliation.audit-all', compact('logs', 'cs'));
    }

    /**
     * The six report pages reached from the Reports card grid.
     */
    public function report(Request $request, string $report, ?int $reconciliation = null)
    {
        $this->requirePermission($request, 'bank-reconciliations.view');
        $companyId = $this->companyId();
        $cs = $this->cs();

        $valid = ['summary', 'outstanding', 'unmatched', 'detail', 'history', 'exceptions'];
        abort_unless(in_array($report, $valid, true), 404);

        $accounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get();

        $accountId = $request->integer('bank_account_id') ?: null;
        if ($accountId !== null && !$accounts->contains('id', $accountId)) {
            $accountId = null;
        }

        $reconciliations = Reconciliation::where('company_id', $companyId)
            ->with('bankAccount')
            ->latest('id')
            ->get();

        $selectedRecon = null;
        if ($reconciliation !== null) {
            $selectedRecon = $this->reconciliation($reconciliation);
        } elseif ($request->has('reconciliation_id')) {
            $selectedRecon = $this->reconciliation((int) $request->input('reconciliation_id'));
        }

        [$title, $rows, $totals] = $this->reportData($report, $companyId, $accountId, $selectedRecon);

        return view('accounting.bank-reconciliation.report-detail', compact(
            'report', 'title', 'rows', 'totals', 'cs', 'accounts', 'accountId',
            'reconciliations', 'selectedRecon'
        ));
    }

    /**
     * Report row data per report key.
     *
     * @return array{0:string, 1:\Illuminate\Support\Collection, 2:array}
     */
    protected function reportData(string $report, int $companyId, ?int $accountId, ?Reconciliation $selectedRecon): array
    {
        $reconQuery = Reconciliation::where('company_id', $companyId)->with('bankAccount');
        if ($accountId !== null) {
            $reconQuery->where('bank_account_id', $accountId);
        }

        switch ($report) {
            case 'summary':
                $rows = $reconQuery->get();
                $totals = [
                    'Opening' => (float) $rows->sum('opening_balance'),
                    'Closing' => (float) $rows->sum('closing_balance'),
                    'Book' => (float) $rows->sum('book_balance'),
                    'Statement' => (float) $rows->sum('statement_balance'),
                    'Difference' => (float) $rows->sum('difference'),
                ];

                return ['Summary Report', $rows, $totals];

            case 'outstanding':
                $rows = BankStatementLine::where('company_id', $companyId)
                    ->whereNull('match_id')
                    ->with('reconciliation.bankAccount')
                    ->latest('transaction_date')
                    ->get();
                if ($accountId !== null) {
                    $rows = $rows->where('bank_account_id', $accountId)->values();
                }
                $totals = ['Amount' => (float) $rows->sum('amount')];

                return ['Outstanding Items Report', $rows, $totals];

            case 'unmatched':
                $rows = BankTransaction::where('company_id', $companyId)
                    ->where('reconciliation_status', '!=', BankTransaction::RECON_STATUS_MATCHED)
                    ->with('journalEntry')
                    ->latest('date')
                    ->get();
                if ($accountId !== null) {
                    $rows = $rows->where('bank_account_id', $accountId)->values();
                }
                $totals = ['Amount' => (float) $rows->sum('amount')];

                return ['Unmatched Transactions Report', $rows, $totals];

            case 'detail':
                $rows = $selectedRecon
                    ? $selectedRecon->statementLines()->with('match.bankTransaction')->orderBy('transaction_date')->get()
                    : collect();

                return ['Detail Report', $rows, []];

            case 'history':
                $logs = ReconciliationAuditLog::where('company_id', $companyId)
                    ->with(['reconciliation.bankAccount', 'user'])
                    ->latest('id');
                if ($selectedRecon) {
                    $logs->where('reconciliation_id', $selectedRecon->id);
                }
                $rows = $logs->get();

                return ['History Report', $rows, []];

            case 'exceptions':
            default:
                $rows = (clone $reconQuery)
                    ->where(function ($q) {
                        $q->whereRaw('ABS(difference) > 0.005')->orWhereNull('difference');
                    })
                    ->get();
                $totals = ['Difference' => (float) $rows->sum('difference')];

                return ['Exceptions Report', $rows, $totals];
        }
    }

    /**
     * Toggle the company-wide "approval required before completion" setting.
     */
    public function toggleApproval(Request $request)
    {
        $this->requirePermission($request, 'bank-reconciliations.edit');
        $companyId = $this->companyId();

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $setting = ApprovalSetting::firstOrNew(['company_id' => $companyId]);
        $setting->requires_approval = (bool) $validated['enabled'];
        $setting->save();

        return redirect()
            ->route('accounting.bank-reconciliation.reports')
            ->with('success', (bool) $validated['enabled']
                ? 'Approval is now required before a reconciliation can be completed.'
                : 'Approval is no longer required before completion.');
    }

    public function create(Request $request)
    {
        $this->requirePermission($request, 'bank-reconciliations.create');
        $companyId = $this->companyId();
        $cs = $this->cs();

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $preselectedBankAccountId = $request->integer('bank_account_id') ?: null;
        if ($preselectedBankAccountId !== null && !$bankAccounts->contains('id', $preselectedBankAccountId)) {
            $preselectedBankAccountId = null;
        }

        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $systemCurrency = Company::find($companyId)?->base_currency ?? 'MWK';
        $currencies = Currency::query()->active()->ordered()->get();

        $approvalSetting = ApprovalSetting::where('company_id', $companyId)->first();

        // §5.2 — the opening balance defaults to the previous period's closing balance.
        $defaultOpeningBalance = null;
        if ($preselectedBankAccountId !== null) {
            $defaultOpeningBalance = Reconciliation::where('company_id', $companyId)
                ->where('bank_account_id', $preselectedBankAccountId)
                ->where('status', '!=', Reconciliation::STATUS_REVERSED)
                ->latest('period_end')
                ->value('closing_balance');
        }

        return view('accounting.bank-reconciliation.create', compact(
            'bankAccounts', 'preselectedBankAccountId', 'defaultOpeningBalance', 'branches',
            'costCenters', 'systemCurrency', 'currencies', 'approvalSetting', 'cs'
        ));
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'bank-reconciliations.create');
        $companyId = $this->companyId();

        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'statement_number' => ['nullable', 'string', 'max:60'],
            'statement_date' => ['required', 'date'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'opening_balance' => ['required', 'numeric'],
            'closing_balance' => ['required', 'numeric'],
            'currency' => ['nullable', 'string', 'max:10'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:cost_centers,id'],
        ]);

        $bankAccount = Account::where('id', (int) $validated['bank_account_id'])
            ->where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->first();

        abort_unless($bankAccount, 422);

        $validated['company_id'] = $companyId;
        $validated['currency'] = $validated['currency'] ?? Company::find($companyId)?->base_currency ?? 'MWK';
        $validated['period_start'] = $validated['period_start'] ?? $validated['statement_date'] ?? null;
        $validated['period_end'] = $validated['period_end'] ?? $validated['statement_date'] ?? null;

        try {
            $reconciliation = $this->reconciliationService->create($validated, auth()->id());

            return redirect()
                ->route('accounting.bank-reconciliation.import', $reconciliation->id)
                ->with('success', 'Reconciliation created. Import your bank statement to begin.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * The matching workspace — statement lines, book transactions, suggestions and
     * the §5.4 calculation card.
     */
    public function workspace(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $cs = $this->cs();

        $reconciliation = $this->calculationService->recalculate($reconciliation);

        $statementLines = $reconciliation->statementLines()
            ->with('match.bankTransaction')
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $transactions = $this->matchingEngine->availableTransactions($reconciliation);

        $matches = ReconciliationMatch::where('reconciliation_id', $reconciliation->id)
            ->with(['bankStatementLine', 'bankTransaction'])
            ->orderBy('id')
            ->get();

        $suggestions = $this->matchingEngine->suggest($reconciliation);

        $adjustments = $reconciliation->adjustments()
            ->with('account')
            ->orderBy('id')
            ->get();

        $imports = $reconciliation->imports()
            ->with('importedBy')
            ->latest('id')
            ->get();

        $accounts = Account::where('company_id', $reconciliation->company_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $approvalRequired = $this->reconciliationService->approvalRequired($reconciliation);

        return view('accounting.bank-reconciliation.workspace', compact(
            'reconciliation', 'cs', 'statementLines', 'transactions', 'matches',
            'suggestions', 'adjustments', 'imports', 'accounts', 'approvalRequired'
        ));
    }

    /**
     * Read-only finalization page with the full audit trail.
     */
    public function show(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $cs = $this->cs();

        $reconciliation = $this->calculationService->recalculate($reconciliation);

        $statementLines = $reconciliation->statementLines()
            ->with('match.bankTransaction')
            ->orderBy('transaction_date')
            ->get();

        $matches = ReconciliationMatch::where('reconciliation_id', $reconciliation->id)
            ->with(['bankStatementLine', 'bankTransaction'])
            ->orderBy('id')
            ->get();

        $adjustments = $reconciliation->adjustments()->with('account')->orderBy('id')->get();
        $auditLogs = $this->auditTrail->forReconciliation($reconciliation->id);
        $imports = $reconciliation->imports()->with('importedBy')->latest('id')->get();

        $approvalRequired = $this->reconciliationService->approvalRequired($reconciliation);

        return view('accounting.bank-reconciliation.show', compact(
            'reconciliation', 'cs', 'statementLines', 'matches', 'adjustments',
            'auditLogs', 'imports', 'approvalRequired'
        ));
    }

    public function audit(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $cs = $this->cs();

        $auditLogs = $this->auditTrail->forReconciliation($reconciliation->id);

        return view('accounting.bank-reconciliation.audit', compact('reconciliation', 'auditLogs', 'cs'));
    }

    public function importForm(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.import');
        $cs = $this->cs();

        return view('accounting.bank-reconciliation.import', compact('reconciliation', 'cs'));
    }

    /**
     * Step 2a — accept the uploaded statement, parse its header + sample rows,
     * and render the column-mapping screen.
     */
    public function previewImport(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.import');
        $cs = $this->cs();

        $validated = $request->validate([
            'statement_file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
        ]);

        $file = $request->file('statement_file');
        $token = bin2hex(random_bytes(8));
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = $token . '.' . $extension;

        $dir = storage_path('app/private/bank-reconciliation-imports/' . $reconciliation->id);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file->move($dir, $storedName);
        file_put_contents($dir . '/' . $token . '.meta.json', json_encode([
            'original' => $file->getClientOriginalName(),
            'uploaded_at' => now()->toDateTimeString(),
        ]));

        $preview = $this->reconciliationService->previewStatementFile($dir . '/' . $storedName, $file->getClientOriginalName());
        $defaults = $this->reconciliationService->suggestColumnMapping($preview['header']);
        $originalName = $file->getClientOriginalName();

        return view('accounting.bank-reconciliation.mapping', compact(
            'reconciliation', 'cs', 'storedName', 'preview', 'defaults', 'originalName'
        ));
    }

    public function importStatement(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.import');

        $storedName = $request->input('upload');
        if ($storedName !== null && is_array($request->input('map'))) {
            return $this->importMapped($request, $reconciliation, $storedName);
        }

        // Legacy direct-upload path — columns are auto-detected from the header row.
        $validated = $request->validate([
            'statement_file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
        ]);

        try {
            $file = $request->file('statement_file');
            $path = $file->getRealPath();
            $originalName = $file->getClientOriginalName();

            $import = $this->reconciliationService->importStatement($reconciliation, $path, $originalName, auth()->id());

            return redirect()
                ->route('accounting.bank-reconciliation.workspace', $reconciliation->id)
                ->with('success', "Imported {$import->line_count} statement lines from {$import->filename}.");
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function importMapped(Request $request, Reconciliation $reconciliation, string $storedName)
    {
        $dir = storage_path('app/private/bank-reconciliation-imports/' . $reconciliation->id);
        $path = $dir . '/' . basename($storedName);
        $metaPath = $dir . '/' . pathinfo($storedName, PATHINFO_FILENAME) . '.meta.json';

        if (!is_file($path)) {
            return redirect()
                ->route('accounting.bank-reconciliation.import', $reconciliation->id)
                ->with('error', 'The uploaded file is no longer available. Please re-upload the statement.');
        }

        $originalName = $storedName;
        if (is_file($metaPath)) {
            $meta = json_decode((string) file_get_contents($metaPath), true);
            $originalName = $meta['original'] ?? $storedName;
        }

        $map = $request->input('map', []);
        $normalized = [];
        foreach (['date', 'reference', 'description', 'debit', 'credit', 'amount', 'balance'] as $field) {
            $normalized[$field] = isset($map[$field]) && $map[$field] !== '' && $map[$field] !== null ? (int) $map[$field] : null;
        }

        try {
            $import = $this->reconciliationService->importStatementWithMapping(
                $reconciliation,
                $path,
                $originalName,
                auth()->id(),
                $normalized,
                $request->boolean('has_header', true)
            );

            @unlink($path);
            @unlink($metaPath);

            return redirect()
                ->route('accounting.bank-reconciliation.workspace', $reconciliation->id)
                ->with('success', "Imported {$import->line_count} statement lines from {$import->filename}.");
        } catch (InvalidArgumentException $e) {
            // Re-render the mapping screen (still on the stored file) so the user
            // can correct the mapping without re-uploading.
            $preview = $this->reconciliationService->previewStatementFile($path, $originalName);
            $defaults = $normalized;

            return view('accounting.bank-reconciliation.mapping', compact(
                'reconciliation', 'storedName', 'preview', 'defaults', 'originalName'
            ))->with('cs', $this->cs())
                ->with('error', $e->getMessage())
                ->with('hasHeader', $request->boolean('has_header', true));
        }
    }

    public function autoMatch(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.match');

        try {
            $result = $this->reconciliationService->applyAutoMatches($reconciliation, auth()->id());

            return redirect()
                ->route('accounting.bank-reconciliation.workspace', $reconciliation->id)
                ->with('success', "Auto-match applied {$result['applied']} suggestion(s).");
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function match(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.match');

        $validated = $request->validate([
            'bank_statement_line_id' => ['required', 'integer'],
            'bank_transaction_id' => ['required', 'integer'],
        ]);

        try {
            $this->reconciliationService->match(
                $reconciliation,
                (int) $validated['bank_statement_line_id'],
                (int) $validated['bank_transaction_id'],
                'manual',
                null,
                auth()->id()
            );

            return redirect()
                ->route('accounting.bank-reconciliation.workspace', $reconciliation->id)
                ->with('success', 'Transaction matched.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function unmatch(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.match');

        $validated = $request->validate([
            'match_id' => ['required', 'integer'],
        ]);

        try {
            $this->reconciliationService->unmatch($reconciliation, (int) $validated['match_id'], auth()->id());

            return redirect()
                ->route('accounting.bank-reconciliation.workspace', $reconciliation->id)
                ->with('success', 'Match removed.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function addAdjustment(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.adjust');

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', ReconciliationAdjustment::TYPES)],
            'sign' => ['required', 'string', 'in:add,subtract'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'side' => ['nullable', 'string', 'in:book,bank'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $adjustment = $this->reconciliationService->adjust(
                $reconciliation,
                $validated['type'],
                $validated['sign'],
                (float) $validated['amount'],
                $validated['side'] ?? null,
                isset($validated['account_id']) ? (int) $validated['account_id'] : null,
                $validated['description'] ?? null,
                auth()->id()
            );

            return redirect()
                ->route('accounting.bank-reconciliation.workspace', $reconciliation->id)
                ->with('success', 'Adjustment added — ' . ReconciliationAdjustment::typeLabel($adjustment->type));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function removeAdjustment(Request $request, int $reconciliation, int $adjustmentId)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.adjust');

        try {
            $this->reconciliationService->removeAdjustment($reconciliation, $adjustmentId, auth()->id());

            return redirect()
                ->route('accounting.bank-reconciliation.workspace', $reconciliation->id)
                ->with('success', 'Adjustment removed.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function markReadyForReview(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.edit');

        try {
            $this->reconciliationService->markReadyForReview($reconciliation, auth()->id());

            return redirect()
                ->route('accounting.bank-reconciliation.show', $reconciliation->id)
                ->with('success', 'Reconciliation marked ready for review.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reopen(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.edit');

        try {
            $this->reconciliationService->reopen($reconciliation, auth()->id());

            return redirect()
                ->route('accounting.bank-reconciliation.workspace', $reconciliation->id)
                ->with('success', 'Reconciliation reopened.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function approve(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.approve');

        if ($reconciliation->created_by === auth()->id()) {
            abort(403, __('You cannot approve a reconciliation you created.'));
        }

        try {
            $this->reconciliationService->approve($reconciliation, auth()->id());

            return redirect()
                ->route('accounting.bank-reconciliation.show', $reconciliation->id)
                ->with('success', 'Reconciliation approved.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function complete(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.complete');

        if ($reconciliation->created_by === auth()->id()) {
            abort(403, __('You cannot complete a reconciliation you created.'));
        }

        try {
            $this->reconciliationService->complete($reconciliation, auth()->id());

            return redirect()
                ->route('accounting.bank-reconciliation.show', $reconciliation->id)
                ->with('success', 'Reconciliation completed. The matched book transactions are now cleared.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reverse(Request $request, int $reconciliation)
    {
        $reconciliation = $this->resolveReconciliation($request, $reconciliation);
        $this->requirePermission($request, 'bank-reconciliations.reverse');

        if ($reconciliation->created_by === auth()->id()) {
            abort(403, __('You cannot reverse a reconciliation you created.'));
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->reconciliationService->reverse($reconciliation, $validated['reason'], auth()->id());

            return redirect()
                ->route('accounting.bank-reconciliation.show', $reconciliation->id)
                ->with('success', 'Reconciliation reversed.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
