<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\BranchQuotationIssuedMail;
use App\Mail\BranchRequestRejectedMail;
use App\Models\BranchRequest;
use App\Models\Company;
use App\Services\BranchRequests\BranchRequestException;
use App\Services\BranchRequests\BranchRequestNotifier;
use App\Services\BranchRequests\BranchRequestService;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Http\Request;

/**
 * Super Admin queue for branch requests. BranchRequest is TenantScoped, so
 * reads and writes for provisioned companies happen inside a temporary tenant
 * binding (mirroring TenantBranchReader), which is always cleared afterwards so
 * no tenant connection leaks into the panel request.
 */
class BranchRequestsController extends Controller
{
    public function __construct(
        private readonly TenantConnectionResolver $resolver,
        private readonly BranchRequestService $service,
        private readonly BranchRequestNotifier $notifier,
    ) {
    }

    public function index()
    {
        $companies = Company::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $requests = collect();

        foreach ($companies as $company) {
            $bound = $this->bind($company);

            try {
                $requests = $requests->merge(
                    BranchRequest::query()
                        ->where('company_id', $company->id)
                        ->with('quotation')
                        ->latest('created_at')
                        ->limit(20)
                        ->get()
                        ->map(fn (BranchRequest $r) => $r->setAttribute('company_name', $company->name))
                );
            } finally {
                $this->unbind($bound);
            }
        }

        $requests = $requests->sortByDesc('created_at')->take(100);

        return view('superadmin.branch-requests.index', compact('requests'));
    }

    public function show(Company $company, int $branchRequest)
    {
        $bound = $this->bind($company);

        try {
            $request = $this->findRequest($company, $branchRequest);
        } finally {
            $this->unbind($bound);
        }

        $request->load(['quotation.payments', 'quotation.branchRequest']);

        return view('superadmin.branch-requests.show', compact('company', 'request'));
    }

    public function approve(Request $request, Company $company, int $branchRequest)
    {
        $bound = $this->bind($company);

        try {
            $branchRequest = $this->findRequest($company, $branchRequest);

            $quotation = $this->service->approveAndQuote($branchRequest, $request->user(), $request->input('admin_notes'));

            $this->notifier->notifyRequester($branchRequest, new BranchQuotationIssuedMail($quotation));
        } catch (BranchRequestException $e) {
            return back()->withErrors(['branch_request' => $e->getMessage()]);
        } finally {
            $this->unbind($bound);
        }

        return redirect()->route('superadmin.companies.branch-requests.show', [$company, $branchRequest])
            ->with('success', "Quotation {$quotation->quotation_number} issued.");
    }

    public function reject(Request $request, Company $company, int $branchRequest)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $bound = $this->bind($company);

        try {
            $branchRequest = $this->findRequest($company, $branchRequest);

            $this->service->reject($branchRequest, $request->user(), $validated['reason']);

            $this->notifier->notifyRequester($branchRequest, new BranchRequestRejectedMail($branchRequest));
        } catch (BranchRequestException $e) {
            return back()->withErrors(['branch_request' => $e->getMessage()]);
        } finally {
            $this->unbind($bound);
        }

        return redirect()->route('superadmin.companies.branch-requests.show', [$company, $branchRequest])
            ->with('success', 'Branch request rejected.');
    }

    private function findRequest(Company $company, int $id): BranchRequest
    {
        return BranchRequest::query()
            ->where('company_id', $company->id)
            ->findOrFail($id);
    }

    /**
     * Bind the tenant connection for provisioned companies only. Legacy
     * (unprovisioned) companies keep their data in the shared DB, so the
     * default connection is already correct.
     */
    private function bind(Company $company): bool
    {
        if ($company->isProvisioned() && $company->is_active) {
            $this->resolver->resolve($company);
            return true;
        }

        return false;
    }

    private function unbind(bool $wasBound): void
    {
        if ($wasBound) {
            $this->resolver->clear();
        }
    }
}
