<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\User;
use App\Services\Admin\NumberingSequenceService;
use App\Services\POS\PosSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetupWizardController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $company = Company::findOrFail($companyId);

        $hasChartOfAccounts = $company->accounts()->count() > 0;
        $hasFiscalYear = $company->fiscalYears()->count() > 0;
        $hasBranch = $company->branches()->count() > 0;
        $hasCostCenter = CostCenter::where('company_id', $companyId)->count() > 0;
        $hasNumberingSequences = \App\Models\NumberingSequence::where('company_id', $companyId)->count() > 0;

        $steps = [
            ['label' => 'Chart of Accounts', 'done' => $hasChartOfAccounts, 'route' => 'accounting.accounts.index'],
            ['label' => 'Fiscal Year', 'done' => $hasFiscalYear, 'route' => 'accounting.fiscal-years.index'],
            ['label' => 'First Branch', 'done' => $hasBranch, 'route' => 'branches.index'],
            ['label' => 'Cost Center', 'done' => $hasCostCenter, 'route' => 'accounting.cost-centers.index'],
            ['label' => 'Numbering Sequences', 'done' => $hasNumberingSequences, 'route' => 'admin.numbering-sequences.index'],
        ];

        $completedCount = collect($steps)->where('done', true)->count();

        return view('admin.setup-wizard.index', compact('company', 'steps', 'completedCount', 'hasBranch', 'hasCostCenter'));
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'branch_code' => 'nullable|string|max:50',
            'cost_center_name' => 'required|string|max:255',
            'cost_center_code' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($validated, $companyId) {
            if (Branch::where('company_id', $companyId)->count() === 0) {
                Branch::create([
                    'company_id' => $companyId,
                    'name' => $validated['branch_name'],
                    'code' => $validated['branch_code'] ?? null,
                    'is_active' => true,
                ]);
            }

            if (CostCenter::where('company_id', $companyId)->count() === 0) {
                CostCenter::create([
                    'company_id' => $companyId,
                    'name' => $validated['cost_center_name'],
                    'code' => $validated['cost_center_code'] ?? null,
                    'is_active' => true,
                ]);
            }

            $hasSequences = \App\Models\NumberingSequence::where('company_id', $companyId)->count() > 0;
            if (!$hasSequences) {
                app(NumberingSequenceService::class)->seedDefaults($companyId);
            }

            PosSetupService::seedDefaultsForCompany($companyId);
        });

        return redirect()->route('admin.setup-wizard.index')
            ->with('success', 'Setup items created successfully.');
    }
}
