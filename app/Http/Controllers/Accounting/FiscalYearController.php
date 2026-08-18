<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Services\Accounting\YearEndCloseService;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    public function __construct(private YearEndCloseService $service)
    {
    }

    public function index()
    {
        $companyId = session('current_company_id');

        $fiscalYears = FiscalYear::where('company_id', $companyId)
            ->with(['closedByUser', 'periods'])
            ->orderByDesc('start_date')
            ->get();

        return view('accounting.fiscal-years.index', compact('fiscalYears'));
    }

    public function create()
    {
        return view('accounting.fiscal-years.create');
    }

    public function show(FiscalYear $fiscalYear)
    {
        $fiscalYear->load(['periods.closedByUser', 'closedByUser', 'closingEntry']);

        return view('accounting.fiscal-years.show', compact('fiscalYear'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'label' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'generate_periods' => 'sometimes|boolean',
            'add_adjustment' => 'sometimes|boolean',
        ]);

        try {
            $fy = $this->service->createFiscalYear(
                $companyId,
                $validated['label'],
                $validated['start_date'],
            );

            return redirect()->route('accounting.fiscal-years.show', $fy)
                ->with('success', 'Fiscal year "' . $fy->label . '" created with ' . $fy->periods()->count() . ' periods.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, FiscalYear $fiscalYear)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $fiscalYear->update($validated);

        return redirect()->route('accounting.fiscal-years.show', $fiscalYear)
            ->with('success', 'Fiscal year updated successfully.');
    }

    public function close(FiscalYear $fiscalYear)
    {
        try {
            $closingEntry = $this->service->close($fiscalYear, auth()->id());

            $message = 'Fiscal year closed successfully.';
            if ($closingEntry) {
                $message .= ' Closing entry ' . $closingEntry->journal_number . ' was posted.';
            }

            return redirect()->route('accounting.fiscal-years.show', $fiscalYear)
                ->with('success', $message);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reopen(Request $request, FiscalYear $fiscalYear)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        try {
            $this->service->reopen($fiscalYear, $validated['reason'], auth()->id());

            return redirect()->route('accounting.fiscal-years.show', $fiscalYear)
                ->with('success', 'Fiscal year reopened. Closing entry has been reversed.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
