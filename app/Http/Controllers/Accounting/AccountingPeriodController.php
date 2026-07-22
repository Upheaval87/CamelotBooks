<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Http\Request;

class AccountingPeriodController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        $periods = AccountingPeriod::where('company_id', $companyId)
            ->with('closedByUser')
            ->orderByDesc('start_date')
            ->get();

        return view('accounting.periods.index', compact('periods'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $overlap = AccountingPeriod::where('company_id', $companyId)
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($overlap) {
            return back()->withInput()->with('error', 'The selected date range overlaps with an existing accounting period.');
        }

        AccountingPeriod::create([
            'company_id' => $companyId,
            'label' => $validated['label'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'open',
        ]);

        return redirect()->route('accounting.periods.index')
            ->with('success', 'Accounting period created successfully.');
    }

    public function close(AccountingPeriod $period)
    {
        $engine = app(JournalPostingEngine::class);

        try {
            $closingEntry = $engine->closePeriod($period, auth()->id());

            $message = 'Accounting period closed successfully.';
            if ($closingEntry) {
                $message .= ' Closing entry ' . $closingEntry->journal_number . ' was posted.';
            }

            return redirect()->route('accounting.periods.index')
                ->with('success', $message);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function lock(AccountingPeriod $period)
    {
        if (!$period->isClosed()) {
            return back()->with('error', 'Only closed periods can be locked.');
        }

        $period->update([
            'status' => 'locked',
        ]);

        return redirect()->route('accounting.periods.index')
            ->with('success', 'Accounting period locked successfully.');
    }

    public function reopen(AccountingPeriod $period)
    {
        if ($period->isLocked()) {
            return back()->with('error', 'Locked periods cannot be reopened.');
        }

        if (!$period->isClosed()) {
            return back()->with('error', 'Period is not closed.');
        }

        $period->update([
            'status' => 'open',
            'closed_by' => null,
            'closed_at' => null,
        ]);

        return redirect()->route('accounting.periods.index')
            ->with('success', 'Accounting period reopened successfully.');
    }
}
