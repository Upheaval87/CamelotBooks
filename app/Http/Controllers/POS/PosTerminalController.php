<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosTerminal;
use App\Models\Branch;
use Illuminate\Http\Request;

class PosTerminalController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');
        $terminals = PosTerminal::where('company_id', $companyId)
            ->with('branch')
            ->latest()
            ->get();
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->get();

        return view('pos.terminals.index', compact('terminals', 'branches'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|max:50|unique:pos_terminals,identifier,NULL,id,company_id,' . $companyId,
            'branch_id' => 'nullable|exists:branches,id',
            'cashier_pin_timeout_minutes' => 'nullable|integer|min:0|max:480',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;

        PosTerminal::create($validated);

        return redirect()->route('pos.terminals.index')->with('success', 'Terminal created successfully.');
    }

    public function update(Request $request, PosTerminal $terminal)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|max:50|unique:pos_terminals,identifier,' . $terminal->id . ',id,company_id,' . $companyId,
            'branch_id' => 'nullable|exists:branches,id',
            'cashier_pin_timeout_minutes' => 'nullable|integer|min:0|max:480',
        ]);

        $terminal->update($validated);

        return redirect()->route('pos.terminals.index')->with('success', 'Terminal updated successfully.');
    }

    public function toggle(PosTerminal $terminal)
    {
        $terminal->update(['is_active' => !$terminal->is_active]);
        $status = $terminal->is_active ? 'activated' : 'deactivated';

        return redirect()->route('pos.terminals.index')->with("success", "Terminal {$status} successfully.");
    }
}
