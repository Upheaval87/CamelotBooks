<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Services\Accounting\BranchLimitExceededException;
use App\Services\Accounting\BranchLimitService;
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

    /**
     * Create a branch under the company's branch limit.
     *
     * The limit applies to EVERYONE (including super admins) unless the request
     * carries an override flag that is verified server-side against the
     * actor's central is_super_admin flag. The override flag may arrive as a
     * query parameter (?override=true) or in the JSON/FormData body.
     */
    public function store(Request $request)
    {
        $company = Company::findOrFail(session('current_company_id'));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,NULL,id,company_id,' . $company->id,
            'address' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $overrideRequested = $request->boolean('override')
            || $request->input('override') === '1'
            || $request->input('override') === 'true';
        $wasOverride = $overrideRequested && $user->isSuperAdmin();

        try {
            $branch = app(BranchLimitService::class)->createBranch($company, $validated, $user, $wasOverride);
        } catch (BranchLimitExceededException $e) {
            if ($request->wantsJson()) {
                return response()->json($e->payload(), 422);
            }

            return back()
                ->withErrors(['branch_limit' => $e->getMessage()])
                ->with('branch_limit_payload', $e->payload());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'branch' => [
                    'id' => $branch->id,
                    'code' => $branch->code,
                    'name' => $branch->name,
                ],
            ], 201);
        }

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    /**
     * Usage for the frontend usage indicator: { branch_count, branch_limit }.
     */
    public function usage(Request $request)
    {
        $company = Company::findOrFail(session('current_company_id'));

        return response()->json(app(BranchLimitService::class)->usage($company));
    }

    public function update(Request $request, Branch $branch)
    {
        $this->authorizeScope($branch);

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
        $this->authorizeScope($branch);

        $company = Company::findOrFail($branch->company_id);

        $branch->update(['is_active' => !$branch->is_active]);

        // branch_count tracks ACTIVE branches, so reactivation/deactivation
        // changes the cached count. Reconcile from the live count instead of
        // manual +/- so drift can never accumulate.
        app(BranchLimitService::class)->usage($company);

        $status = $branch->is_active ? 'activated' : 'deactivated';

        return redirect()->route('branches.index')->with('success', "Branch {$status} successfully.");
    }

    /**
     * Branch writes are scoped to the session company; a forged id targeting
     * another company's branch (possible only in the legacy shared DB) is
     * rejected server-side.
     */
    private function authorizeScope(Branch $branch): void
    {
        abort_unless((int) $branch->company_id === (int) session('current_company_id'), 403);
    }
}
