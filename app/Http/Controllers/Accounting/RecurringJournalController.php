<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\RecurringJournalTemplate;
use App\Models\RecurringJournalTemplateLine;
use Illuminate\Http\Request;

class RecurringJournalController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        $templates = RecurringJournalTemplate::where('company_id', $companyId)
            ->with(['branch', 'createdBy', 'templateLines.account'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('accounting.recurring-journals.index', compact('templates'));
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

        return view('accounting.recurring-journals.create', compact('accounts', 'branches'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'memo' => 'nullable|string|max:1000',
            'frequency' => 'required|in:weekly,biweekly,monthly,quarterly,yearly',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'auto_post' => 'sometimes|boolean',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:500',
            'lines.*.branch_id' => 'nullable|exists:branches,id',
        ]);

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($validated['lines'] as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->with('error', 'Debits and credits must be equal.');
        }

        if ($totalDebit === 0 && $totalCredit === 0) {
            return back()->withInput()->with('error', 'At least one line must have a debit or credit amount.');
        }

        $template = RecurringJournalTemplate::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'branch_id' => $validated['branch_id'] ?? null,
            'memo' => $validated['memo'] ?? null,
            'frequency' => $validated['frequency'],
            'day_of_month' => $validated['day_of_month'] ?? null,
            'day_of_week' => $validated['day_of_week'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'next_run_date' => $validated['start_date'],
            'auto_post' => $validated['auto_post'] ?? false,
            'is_active' => true,
            'created_by' => $userId,
        ]);

        foreach ($validated['lines'] as $line) {
            RecurringJournalTemplateLine::create([
                'recurring_journal_template_id' => $template->id,
                'account_id' => $line['account_id'],
                'branch_id' => $line['branch_id'] ?? null,
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'memo' => $line['memo'] ?? null,
            ]);
        }

        return redirect()->route('accounting.recurring-journals.show', $template)
            ->with('success', 'Recurring journal template created successfully.');
    }

    public function show(RecurringJournalTemplate $template)
    {
        $template->load([
            'templateLines.account',
            'templateLines.branch',
            'branch',
            'createdBy',
            'journalEntries' => function ($q) {
                $q->orderByDesc('date')->limit(10);
            },
        ]);

        return view('accounting.recurring-journals.show', compact('template'));
    }

    public function edit(RecurringJournalTemplate $template)
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

        $template->load('templateLines');

        return view('accounting.recurring-journals.edit', compact('template', 'accounts', 'branches'));
    }

    public function update(Request $request, RecurringJournalTemplate $template)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'memo' => 'nullable|string|max:1000',
            'frequency' => 'required|in:weekly,biweekly,monthly,quarterly,yearly',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'auto_post' => 'sometimes|boolean',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:500',
            'lines.*.branch_id' => 'nullable|exists:branches,id',
        ]);

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($validated['lines'] as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->with('error', 'Debits and credits must be equal.');
        }

        if ($totalDebit === 0 && $totalCredit === 0) {
            return back()->withInput()->with('error', 'At least one line must have a debit or credit amount.');
        }

        $template->update([
            'name' => $validated['name'],
            'branch_id' => $validated['branch_id'] ?? null,
            'memo' => $validated['memo'] ?? null,
            'frequency' => $validated['frequency'],
            'day_of_month' => $validated['day_of_month'] ?? null,
            'day_of_week' => $validated['day_of_week'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'auto_post' => $validated['auto_post'] ?? false,
        ]);

        $template->templateLines()->delete();

        foreach ($validated['lines'] as $line) {
            RecurringJournalTemplateLine::create([
                'recurring_journal_template_id' => $template->id,
                'account_id' => $line['account_id'],
                'branch_id' => $line['branch_id'] ?? null,
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'memo' => $line['memo'] ?? null,
            ]);
        }

        return redirect()->route('accounting.recurring-journals.show', $template)
            ->with('success', 'Recurring journal template updated successfully.');
    }

    public function toggle(RecurringJournalTemplate $template)
    {
        $template->update([
            'is_active' => !$template->is_active,
        ]);

        $status = $template->is_active ? 'activated' : 'deactivated';

        return redirect()->route('accounting.recurring-journals.show', $template)
            ->with('success', "Recurring journal template {$status} successfully.");
    }
}
