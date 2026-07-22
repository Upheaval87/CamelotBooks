<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        $branches = Branch::where('company_id', $companyId)
            ->latest()
            ->get();

        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,NULL,id,company_id,' . session('current_company_id'),
            'address' => 'nullable|string|max:500',
        ]);

        $validated['company_id'] = session('current_company_id');
        $validated['is_active'] = true;

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,' . $branch->id . ',id,company_id,' . session('current_company_id'),
            'address' => 'nullable|string|max:500',
        ]);

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function toggle(Branch $branch)
    {
        $branch->update(['is_active' => !$branch->is_active]);

        $status = $branch->is_active ? 'activated' : 'deactivated';

        return redirect()->route('branches.index')->with('success', "Branch {$status} successfully.");
    }
}
