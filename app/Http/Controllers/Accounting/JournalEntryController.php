<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    protected JournalPostingEngine $postingEngine;

    public function __construct(JournalPostingEngine $postingEngine)
    {
        $this->postingEngine = $postingEngine;
    }

    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $query = JournalEntry::where('company_id', $companyId)
            ->with(['createdBy', 'branch']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('journal_number', 'like', "%{$search}%")
                  ->orWhere('memo', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            if ($request->type === 'adjusting') {
                $query->where('is_adjusting_entry', true);
            } else {
                $query->where('is_adjusting_entry', false);
            }
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('branch_id')) {
            $query->forBranch($request->branch_id);
        }

        $journalEntries = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $stats = JournalEntry::where('company_id', $companyId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.journal-entries.index', compact('journalEntries', 'branches', 'stats'));
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
        $journalEntry->load([
            'lines.account',
            'lines.branch',
            'branch',
            'createdBy',
            'postedByUser',
            'approvedByUser',
            'auditLogs.user',
        ]);

        return view('accounting.journal-entries.show', compact('journalEntry'));
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
            'reversal_date' => 'nullable|date',
        ]);

        try {
            $reversal = $this->postingEngine->reverse(
                $journalEntry->id,
                auth()->id(),
                $request->reversal_date
            );

            return redirect()->route('accounting.journal-entries.show', $reversal)
                ->with('success', 'Reversal entry created successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
