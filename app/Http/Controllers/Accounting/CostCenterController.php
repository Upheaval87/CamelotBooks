<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CostCenterController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        $costCenters = CostCenter::where('company_id', $companyId)
            ->orderBy('code')
            ->get();

        return view('accounting.cost-centers.index', compact('costCenters'));
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $parentCenters = CostCenter::where('company_id', $companyId)->active()->orderBy('code')->get();

        return view('accounting.cost-centers.create', compact('parentCenters'));
    }

    public function show(CostCenter $costCenter)
    {
        $costCenter->load(['journalEntryLines.journalEntry']);

        $companyId = session('current_company_id');

        $actualDebit = (float) JournalEntryLine::where('cost_center_id', $costCenter->id)
            ->whereHas('journalEntry', fn ($q) => $q->where('company_id', $companyId)->whereIn('status', ['posted', 'reversed']))
            ->sum('debit');

        $actualCredit = (float) JournalEntryLine::where('cost_center_id', $costCenter->id)
            ->whereHas('journalEntry', fn ($q) => $q->where('company_id', $companyId)->whereIn('status', ['posted', 'reversed']))
            ->sum('credit');

        return view('accounting.cost-centers.show', compact('costCenter', 'actualDebit', 'actualCredit'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cost_centers,code,NULL,id,company_id,' . session('current_company_id'),
            'description' => 'nullable|string|max:500',
            'manager' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
        ]);

        $validated['company_id'] = session('current_company_id');
        $validated['is_active'] = true;

        CostCenter::create($validated);

        return redirect()->route('accounting.cost-centers.index')->with('success', 'Cost centre created successfully.');
    }

    public function update(Request $request, CostCenter $costCenter)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cost_centers,code,' . $costCenter->id . ',id,company_id,' . session('current_company_id'),
            'description' => 'nullable|string|max:500',
            'manager' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $costCenter->update($validated);

        return redirect()->route('accounting.cost-centers.show', $costCenter)->with('success', 'Cost centre updated successfully.');
    }

    public function toggle(CostCenter $costCenter)
    {
        $costCenter->update(['is_active' => !$costCenter->is_active]);

        $status = $costCenter->is_active ? 'activated' : 'deactivated';

        return redirect()->route('accounting.cost-centers.index')->with('success', "Cost centre {$status} successfully.");
    }
}
