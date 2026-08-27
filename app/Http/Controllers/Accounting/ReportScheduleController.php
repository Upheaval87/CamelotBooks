<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ReportSchedule;
use Illuminate\Http\Request;

class ReportScheduleController extends Controller
{
    private const REPORT_OPTIONS = [
        'fin.income' => 'Income Statement',
        'fin.position' => 'Balance Sheet',
        'fin.cashflow' => 'Cash Flow Statement',
        'fin.ar-aging' => 'A/R Aging',
        'fin.ap-aging' => 'A/P Aging',
    ];

    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $schedules = ReportSchedule::where('company_id', $companyId)
            ->with('creator')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('accounting.report-schedules.index', [
            'schedules' => $schedules,
            'reportOptions' => self::REPORT_OPTIONS,
        ]);
    }

    public function create()
    {
        return view('accounting.report-schedules.create', [
            'reportOptions' => self::REPORT_OPTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_key' => ['required', 'string', 'in:' . implode(',', array_keys(self::REPORT_OPTIONS))],
            'frequency' => ['required', 'string', 'in:DAILY,WEEKLY,MONTHLY'],
            'recipients' => ['required', 'string'],
            'format' => ['required', 'string', 'in:PDF,EXCEL'],
            // Filter params — stored as JSON
            'filters' => ['nullable', 'string'],
        ]);

        $companyId = session('current_company_id');

        ReportSchedule::create([
            'report_key' => $validated['report_key'],
            'filters' => json_decode($validated['filters'] ?? '{}', true) ?: [],
            'frequency' => $validated['frequency'],
            'recipients' => array_filter(array_map('trim', explode(',', $validated['recipients']))),
            'format' => $validated['format'],
            'active' => true,
            'created_by' => auth()->id(),
            'company_id' => $companyId,
        ]);

        return redirect()
            ->route('accounting.report-schedules.index')
            ->with('success', 'Report schedule created.');
    }

    public function edit(int $id)
    {
        $schedule = ReportSchedule::findOrFail($id);

        return view('accounting.report-schedules.edit', [
            'schedule' => $schedule,
            'reportOptions' => self::REPORT_OPTIONS,
            'recipientsStr' => implode(', ', $schedule->recipients ?? []),
            'filtersJson' => json_encode($schedule->filters ?? []),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $schedule = ReportSchedule::findOrFail($id);

        $validated = $request->validate([
            'frequency' => ['required', 'string', 'in:DAILY,WEEKLY,MONTHLY'],
            'recipients' => ['required', 'string'],
            'format' => ['required', 'string', 'in:PDF,EXCEL'],
            'filters' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $schedule->update([
            'frequency' => $validated['frequency'],
            'recipients' => array_filter(array_map('trim', explode(',', $validated['recipients']))),
            'format' => $validated['format'],
            'filters' => json_decode($validated['filters'] ?? '{}', true) ?: [],
            'active' => $validated['active'] ?? $schedule->active,
        ]);

        return redirect()
            ->route('accounting.report-schedules.index')
            ->with('success', 'Report schedule updated.');
    }

    public function destroy(int $id)
    {
        $schedule = ReportSchedule::findOrFail($id);
        $schedule->delete();

        return redirect()
            ->route('accounting.report-schedules.index')
            ->with('success', 'Report schedule deleted.');
    }

    public function toggle(int $id)
    {
        $schedule = ReportSchedule::findOrFail($id);
        $schedule->update(['active' => !$schedule->active]);

        return back()->with('success', $schedule->active ? 'Schedule activated.' : 'Schedule paused.');
    }
}
