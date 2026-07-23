<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\PayeTable;
use App\Models\PayeTableBand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayeTableController extends Controller
{
    public function index()
    {
        $companyId = session('current_company_id');

        $tables = PayeTable::where('company_id', $companyId)
            ->with('bands')
            ->orderByDesc('is_current')
            ->orderByDesc('effective_from')
            ->get();

        return view('accounting.paye-tables.index', compact('tables'));
    }

    public function create()
    {
        return view('accounting.paye-tables.create');
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'version_name' => 'required|string|max:100',
            'effective_from' => 'required|date',
            'bands' => 'required|array|min:1',
            'bands.*.threshold' => 'required|numeric|min:0',
            'bands.*.upper_limit' => 'nullable|numeric|min:0',
            'bands.*.rate' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            PayeTable::where('company_id', $companyId)
                ->where('is_current', true)
                ->update(['is_current' => false, 'effective_to' => $validated['effective_from']]);

            $table = PayeTable::create([
                'company_id' => $companyId,
                'version_name' => $validated['version_name'],
                'effective_from' => $validated['effective_from'],
                'is_current' => true,
            ]);

            foreach ($validated['bands'] as $index => $band) {
                PayeTableBand::create([
                    'paye_table_id' => $table->id,
                    'threshold' => $band['threshold'],
                    'upper_limit' => $band['upper_limit'] ?? null,
                    'rate' => $band['rate'],
                    'sort_order' => $index,
                ]);
            }

            DB::commit();

            return redirect()->route('accounting.paye-tables.show', $table)
                ->with('success', 'PAYE tax table created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(PayeTable $payeTable)
    {
        $this->authorize($payeTable);

        $payeTable->load('bands');

        return view('accounting.paye-tables.show', ['table' => $payeTable]);
    }

    public function edit(PayeTable $payeTable)
    {
        $this->authorize($payeTable);

        $payeTable->load('bands');

        return view('accounting.paye-tables.edit', ['table' => $payeTable]);
    }

    public function update(Request $request, PayeTable $payeTable)
    {
        $this->authorize($payeTable);

        $validated = $request->validate([
            'version_name' => 'required|string|max:100',
            'effective_from' => 'required|date',
            'bands' => 'required|array|min:1',
            'bands.*.threshold' => 'required|numeric|min:0',
            'bands.*.upper_limit' => 'nullable|numeric|min:0',
            'bands.*.rate' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            $payeTable->update([
                'version_name' => $validated['version_name'],
                'effective_from' => $validated['effective_from'],
            ]);

            $payeTable->bands()->delete();

            foreach ($validated['bands'] as $index => $band) {
                PayeTableBand::create([
                    'paye_table_id' => $payeTable->id,
                    'threshold' => $band['threshold'],
                    'upper_limit' => $band['upper_limit'] ?? null,
                    'rate' => $band['rate'],
                    'sort_order' => $index,
                ]);
            }

            DB::commit();

            return redirect()->route('accounting.paye-tables.show', $payeTable)
                ->with('success', 'PAYE tax table updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function activate(PayeTable $payeTable)
    {
        $this->authorize($payeTable);

        $companyId = session('current_company_id');

        DB::beginTransaction();

        try {
            PayeTable::where('company_id', $companyId)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $payeTable->update([
                'is_current' => true,
                'effective_to' => null,
            ]);

            DB::commit();

            return redirect()->route('accounting.paye-tables.index')
                ->with('success', 'PAYE tax table activated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(PayeTable $payeTable)
    {
        $this->authorize($payeTable);

        if ($payeTable->is_current) {
            return back()->withErrors(['error' => 'Cannot delete the currently active PAYE table. Activate another table first.']);
        }

        $payeTable->bands()->delete();
        $payeTable->delete();

        return redirect()->route('accounting.paye-tables.index')
            ->with('success', 'PAYE tax table deleted successfully.');
    }

    private function authorize(PayeTable $payeTable): void
    {
        if ($payeTable->company_id !== session('current_company_id')) {
            abort(404);
        }
    }
}
