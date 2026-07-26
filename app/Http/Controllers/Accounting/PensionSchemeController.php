<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\PensionScheme;
use Illuminate\Http\Request;

class PensionSchemeController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $schemes = PensionScheme::where('company_id', $companyId)
            ->orderByDesc('effective_from')
            ->paginate(20);

        return view('accounting.pension-schemes.index', compact('schemes'));
    }

    public function create()
    {
        return view('accounting.pension-schemes.create');
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'registration_number' => 'nullable|string|max:100',
            'employee_rate' => 'required|numeric|min:0|max:100',
            'employer_rate' => 'required|numeric|min:0|max:100',
            'max_contributory_salary' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
        ]);

        PensionScheme::where('company_id', $companyId)
            ->where('is_current', true)
            ->update(['is_current' => false, 'effective_to' => now()->subDay()]);

        $scheme = PensionScheme::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'registration_number' => $validated['registration_number'] ?? null,
            'employee_rate' => $validated['employee_rate'],
            'employer_rate' => $validated['employer_rate'],
            'max_contributory_salary' => $validated['max_contributory_salary'] ?? null,
            'effective_from' => $validated['effective_from'],
            'is_current' => true,
        ]);

        return redirect()->route('accounting.pension-schemes.show', $scheme)
            ->with('success', 'Pension scheme created successfully.');
    }

    public function show(PensionScheme $scheme)
    {
        $companyId = session('current_company_id');

        if ($scheme->company_id !== $companyId) {
            abort(404);
        }

        return view('accounting.pension-schemes.show', compact('scheme'));
    }

    public function edit(PensionScheme $scheme)
    {
        $companyId = session('current_company_id');

        if ($scheme->company_id !== $companyId) {
            abort(404);
        }

        return view('accounting.pension-schemes.edit', compact('scheme'));
    }

    public function update(Request $request, PensionScheme $scheme)
    {
        $companyId = session('current_company_id');

        if ($scheme->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'registration_number' => 'nullable|string|max:100',
            'employee_rate' => 'required|numeric|min:0|max:100',
            'employer_rate' => 'required|numeric|min:0|max:100',
            'max_contributory_salary' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
        ]);

        $scheme->update($validated);

        return redirect()->route('accounting.pension-schemes.show', $scheme)
            ->with('success', 'Pension scheme updated successfully.');
    }

    public function toggle(PensionScheme $scheme)
    {
        $companyId = session('current_company_id');

        if ($scheme->company_id !== $companyId) {
            abort(404);
        }

        $scheme->update(['is_current' => !$scheme->is_current]);

        return back()->with('success', 'Pension scheme status updated.');
    }
}
