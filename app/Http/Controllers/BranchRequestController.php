<?php

namespace App\Http\Controllers;

use App\Models\BranchRequest;
use App\Models\Company;
use App\Services\BranchRequests\BranchRequestException;
use App\Services\BranchRequests\BranchRequestNotifier;
use App\Services\BranchRequests\BranchRequestService;
use Illuminate\Http\Request;

class BranchRequestController extends Controller
{
    public function index()
    {
        $companyId = (int) session('current_company_id');

        $requests = BranchRequest::query()
            ->where('company_id', $companyId)
            ->with(['quotation' => fn ($q) => $q->with('payments')])
            ->latest()
            ->get();

        $usage = app(\App\Services\Accounting\BranchLimitService::class)->usage(Company::findOrFail($companyId));

        return view('branch-requests.index', compact('requests', 'usage'));
    }

    public function store(Request $request)
    {
        $company = Company::findOrFail(session('current_company_id'));

        $validated = $request->validate([
            'branch_name' => 'required|string|max:255',
            'branch_code' => 'nullable|string|max:50',
            'branch_address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'requested_quantity' => 'required|integer|min:1|max:50',
            'reason' => 'nullable|string|max:2000',
        ]);

        $service = app(BranchRequestService::class);
        $notifier = app(BranchRequestNotifier::class);

        try {
            $branchRequest = $service->submit($company, $validated, $request->user());
        } catch (BranchRequestException $e) {
            return back()
                ->withErrors(['branch_request' => $e->getMessage()])
                ->with('branch_request_error_code', $e->errorCode());
        }

        $notifier->notifySuperAdmins($branchRequest);
        $notifier->notifyCompanyBillingUsers($branchRequest);

        return redirect()->route('branch-requests.show', $branchRequest)
            ->with('success', 'Branch request submitted for review.');
    }

    public function show(BranchRequest $branchRequest)
    {
        $this->authorizeScope($branchRequest);

        $branchRequest->load(['quotation.payments']);

        return view('branch-requests.show', [
            'branchRequest' => $branchRequest,
            'canConfirmPayment' => $this->canConfirmPayment(),
        ]);
    }

    public function cancel(BranchRequest $branchRequest)
    {
        $this->authorizeScope($branchRequest);

        try {
            app(BranchRequestService::class)->cancel($branchRequest, request()->user());
        } catch (BranchRequestException $e) {
            return back()->withErrors(['branch_request' => $e->getMessage()]);
        }

        return redirect()->route('branch-requests.index')
            ->with('success', 'Branch request cancelled.');
    }

    /**
     * Branch requests are scoped to the session company; a forged id targeting
     * another company's request is rejected server-side.
     */
    private function authorizeScope(BranchRequest $branchRequest): void
    {
        abort_unless((int) $branchRequest->company_id === (int) session('current_company_id'), 403);
    }

    /**
     * Payment confirmation is restricted to billing/accounting/system-admin
     * actors; surface whether the current user may confirm in the UI.
     */
    private function canConfirmPayment(): bool
    {
        $user = request()->user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->hasRole('system_admin')) {
            return true;
        }

        return $user->hasRole('accountant') || $user->hasRole('billing');
    }
}
