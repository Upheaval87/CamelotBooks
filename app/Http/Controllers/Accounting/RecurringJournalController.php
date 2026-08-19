<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\RecurringJournalTemplate;
use App\Models\RecurringJournalTemplateLine;
use App\Models\RecurringJournalRun;
use App\Models\RecurringJournalHistory;
use App\Models\RecurringJournalSetting;
use App\Services\Accounting\RecurringJournalService;
use Illuminate\Http\Request;

class RecurringJournalController extends Controller
{
    public function __construct(
        private RecurringJournalService $service,
    ) {
    }

    public function dashboard()
    {
        $companyId = session('current_company_id');
        $stats = $this->service->getDashboardStats($companyId);

        return view('accounting.rj.dashboard', $stats);
    }

    public function index()
    {
        $companyId = session('current_company_id');
        $status = request('status');
        $frequency = request('frequency');
        $search = request('search');

        $query = RecurringJournalTemplate::where('company_id', $companyId)
            ->with(['branch', 'createdBy', 'templateLines.account']);

        if ($status) $query->where('status', $status);
        if ($frequency) $query->where('frequency', $frequency);
        if ($search) $query->where('name', 'like', "%{$search}%");

        $counts = [
            'all' => RecurringJournalTemplate::where('company_id', $companyId)->count(),
            'active' => RecurringJournalTemplate::where('company_id', $companyId)->where('status', 'active')->count(),
            'paused' => RecurringJournalTemplate::where('company_id', $companyId)->where('status', 'paused')->count(),
            'expired' => RecurringJournalTemplate::where('company_id', $companyId)->where('status', 'expired')->count(),
        ];

        $templates = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('accounting.rj.index', compact('templates', 'counts'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $accounts = Account::where('company_id', $companyId)->active()->orderBy('code')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::query()->active()->ordered()->get();

        return view('accounting.rj.create', compact('accounts', 'branches', 'costCenters', 'currencies'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'memo' => 'nullable|string|max:1000',
            'journal_type' => 'required|in:standard,accrual,depreciation,prepayment,adjustment',
            'currency' => 'nullable|string|max:10',
            'frequency' => 'required|in:daily,weekly,biweekly,monthly,quarterly,semi_annually,yearly,custom',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'occurrences' => 'nullable|integer|min:1',
            'generation_mode' => 'required|in:auto_post,approval_first,draft_only',
            'email_notification' => 'nullable|in:before_posting,after_posting,none',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:500',
            'lines.*.branch_id' => 'nullable|exists:branches,id',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $totalDebit = collect($validated['lines'])->sum(fn($l) => (float) ($l['debit'] ?? 0));
        $totalCredit = collect($validated['lines'])->sum(fn($l) => (float) ($l['credit'] ?? 0));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->with('error', 'Debits and credits must be equal.');
        }
        if ($totalDebit == 0 && $totalCredit == 0) {
            return back()->withInput()->with('error', 'At least one line must have a debit or credit amount.');
        }

        $template = $this->service->createTemplate($validated, $companyId, $userId);

        return redirect()->route('accounting.rj.show', $template)
            ->with('success', 'Recurring journal created successfully.');
    }

    public function show(RecurringJournalTemplate $template)
    {
        $template->load([
            'templateLines.account', 'templateLines.branch', 'templateLines.costCenter',
            'branch', 'costCenter', 'createdBy',
        ]);

        $recentRuns = $template->runs()->with('journalEntry', 'createdBy')
            ->orderByDesc('created_at')->limit(10)->get();

        $recentHistory = $template->history()->with('actor')
            ->orderByDesc('happened_at')->limit(20)->get();

        return view('accounting.rj.show', compact('template', 'recentRuns', 'recentHistory'));
    }

    public function edit(RecurringJournalTemplate $template)
    {
        $companyId = session('current_company_id');
        $accounts = Account::where('company_id', $companyId)->active()->orderBy('code')->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::query()->active()->ordered()->get();
        $template->load('templateLines');

        return view('accounting.rj.edit', compact('template', 'accounts', 'branches', 'costCenters', 'currencies'));
    }

    public function update(Request $request, RecurringJournalTemplate $template)
    {
        $userId = auth()->id();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'branch_id' => 'nullable|exists:branches,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'memo' => 'nullable|string|max:1000',
            'journal_type' => 'required|in:standard,accrual,depreciation,prepayment,adjustment',
            'currency' => 'nullable|string|max:10',
            'frequency' => 'required|in:daily,weekly,biweekly,monthly,quarterly,semi_annually,yearly,custom',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'occurrences' => 'nullable|integer|min:1',
            'generation_mode' => 'required|in:auto_post,approval_first,draft_only',
            'email_notification' => 'nullable|in:before_posting,after_posting,none',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:500',
            'lines.*.branch_id' => 'nullable|exists:branches,id',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $totalDebit = collect($validated['lines'])->sum(fn($l) => (float) ($l['debit'] ?? 0));
        $totalCredit = collect($validated['lines'])->sum(fn($l) => (float) ($l['credit'] ?? 0));

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->with('error', 'Debits and credits must be equal.');
        }

        $template = $this->service->updateTemplate($template, $validated, $userId);

        return redirect()->route('accounting.rj.show', $template)
            ->with('success', 'Recurring journal updated successfully.');
    }

    public function toggle(RecurringJournalTemplate $template)
    {
        $userId = auth()->id();
        if ($template->status === RecurringJournalTemplate::STATUS_PAUSED) {
            $this->service->resumeTemplate($template, $userId);
        } else {
            $this->service->pauseTemplate($template, $userId);
        }

        return redirect()->route('accounting.rj.show', $template)
            ->with('success', 'Recurring journal status updated.');
    }

    public function destroy(RecurringJournalTemplate $template)
    {
        $this->service->deleteTemplate($template, auth()->id());
        return redirect()->route('accounting.rj.index')->with('success', 'Recurring journal deleted.');
    }

    public function duplicate(RecurringJournalTemplate $template)
    {
        $new = $this->service->duplicateTemplate($template, auth()->id());
        return redirect()->route('accounting.rj.edit', $new)->with('success', 'Duplicated as a new journal.');
    }

    public function runNow(RecurringJournalTemplate $template)
    {
        $run = $this->service->generateJournal($template, auth()->id(), false);
        if ($run->status === RecurringJournalRun::STATUS_FAILED) {
            return back()->with('error', "Generation failed: {$run->failure_reason}");
        }
        return back()->with('success', "Journal {$run->reference} generated successfully.");
    }

    public function testRun(RecurringJournalTemplate $template)
    {
        $run = $this->service->generateJournal($template, auth()->id(), true);
        return back()->with('success', "Test run complete. Reference: {$run->reference} (test only, not persisted to GL).");
    }

    public function templates()
    {
        $companyId = session('current_company_id');
        $templates = RecurringJournalTemplate::where('company_id', $companyId)
            ->with('templateLines.account')
            ->orderByDesc('created_at')
            ->get();

        return view('accounting.rj.templates', compact('templates'));
    }

    public function scheduled()
    {
        $companyId = session('current_company_id');
        $scheduled = RecurringJournalTemplate::where('company_id', $companyId)
            ->active()
            ->where('next_run_date', '>=', now()->toDateString())
            ->where('next_run_date', '<=', now()->addDays(30)->toDateString())
            ->with('templateLines.account')
            ->orderBy('next_run_date')
            ->get();

        $pausedCount = RecurringJournalTemplate::where('company_id', $companyId)->paused()->count();

        return view('accounting.rj.scheduled', compact('scheduled', 'pausedCount'));
    }

    public function generated()
    {
        $companyId = session('current_company_id');
        $status = request('status');

        $query = RecurringJournalRun::where('company_id', $companyId)
            ->with(['template', 'journalEntry', 'createdBy']);

        if ($status) $query->where('status', $status);

        $runs = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $counts = [
            'draft' => RecurringJournalRun::where('company_id', $companyId)->draft()->count(),
            'pending_approval' => RecurringJournalRun::where('company_id', $companyId)->pending()->count(),
            'posted' => RecurringJournalRun::where('company_id', $companyId)->posted()->count(),
            'reversed' => RecurringJournalRun::where('company_id', $companyId)->where('status', 'reversed')->count(),
        ];

        return view('accounting.rj.generated', compact('runs', 'counts'));
    }

    public function approvals()
    {
        $companyId = session('current_company_id');
        $pendingRuns = RecurringJournalRun::where('company_id', $companyId)
            ->pending()
            ->with(['template', 'journalEntry', 'createdBy'])
            ->orderByDesc('created_at')
            ->get();

        return view('accounting.rj.approvals', compact('pendingRuns'));
    }

    public function approveRun(RecurringJournalRun $run)
    {
        $this->service->approveRun($run, auth()->id());
        return back()->with('success', "Journal {$run->reference} approved and posted.");
    }

    public function rejectRun(Request $request, RecurringJournalRun $run)
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->service->rejectRun($run, auth()->id(), $request->reason);
        return back()->with('success', "Journal {$run->reference} rejected.");
    }

    public function history()
    {
        $companyId = session('current_company_id');
        $action = request('action');
        $search = request('search');

        $query = RecurringJournalHistory::where('company_id', $companyId)
            ->with(['template', 'run', 'actor']);

        if ($action) $query->where('action', $action);
        if ($search) $query->where('description', 'like', "%{$search}%");

        $history = $query->orderByDesc('happened_at')->paginate(20)->withQueryString();

        return view('accounting.rj.history', compact('history'));
    }

    public function reports()
    {
        return view('accounting.rj.reports');
    }

    public function settings()
    {
        $companyId = session('current_company_id');
        $settings = RecurringJournalSetting::forCompany($companyId);
        $accounts = Account::where('company_id', $companyId)->active()->orderBy('code')->get();

        return view('accounting.rj.settings', compact('settings', 'accounts'));
    }

    public function settingsUpdate(Request $request)
    {
        $companyId = session('current_company_id');
        $settings = RecurringJournalSetting::forCompany($companyId);

        $validated = $request->validate([
            'numbering_pattern' => 'nullable|string|max:60',
            'approval_required' => 'sometimes|boolean',
            'approval_threshold' => 'nullable|numeric|min:0',
            'notification_email' => 'nullable|in:before_posting,after_posting,none',
            'block_locked_periods' => 'sometimes|boolean',
            'default_suspense_account_id' => 'nullable|exists:accounts,id',
        ]);

        $settings->update($validated);

        return back()->with('success', 'Settings updated.');
    }

    public function export()
    {
        $companyId = session('current_company_id');
        $templates = RecurringJournalTemplate::where('company_id', $companyId)
            ->with('templateLines.account')
            ->orderBy('name')
            ->get();

        $csv = "Name,Reference,Type,Frequency,Next Run,Total Amount,Status\n";
        foreach ($templates as $t) {
            $csv .= '"' . str_replace('"', '""', $t->name) . '",';
            $csv .= '"' . ($t->reference ?? '') . '",';
            $csv .= '"' . $t->journal_type . '",';
            $csv .= '"' . $t->frequency . '",';
            $csv .= '"' . ($t->next_run_date?->format('Y-m-d') ?? '') . '",';
            $csv .= number_format($t->total_amount, 2) . ',';
            $csv .= '"' . $t->status . '"';
            $csv .= "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="recurring-journals-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    public function previewSchedule(RecurringJournalTemplate $template)
    {
        $dates = [];
        $current = \Carbon\Carbon::parse($template->next_run_date ?? $template->start_date);
        $end = $template->end_date ? \Carbon\Carbon::parse($template->end_date) : $current->copy()->addYear();
        $limit = min($template->occurrences ?? 12, 12);

        for ($i = 0; $i < $limit && $current->lte($end); $i++) {
            $dates[] = $current->copy()->format('Y-m-d');
            $current = match ($template->frequency) {
                'daily' => $current->addDay(),
                'weekly' => $current->addWeek(),
                'biweekly' => $current->addWeeks(2),
                'monthly' => $current->addMonthNoOverflow(),
                'quarterly' => $current->addMonthsNoOverflow(3),
                'semi_annually' => $current->addMonthsNoOverflow(6),
                'yearly' => $current->addYearNoOverflow(),
                default => $current->addMonthNoOverflow(),
            };
        }

        $total = $template->occurrences ?? max(1, $end->diffInDays($template->start_date ?? now()) / 30);

        return response()->json(['dates' => $dates, 'total' => (int) $total]);
    }
}
