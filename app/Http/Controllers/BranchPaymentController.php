<?php

namespace App\Http\Controllers;

use App\Mail\BranchPaymentConfirmedMail;
use App\Models\BranchPayment;
use App\Models\BranchRequest;
use App\Services\BranchRequests\BranchRequestException;
use App\Services\BranchRequests\BranchRequestNotifier;
use App\Services\BranchRequests\BranchRequestService;
use Illuminate\Http\Request;

class BranchPaymentController extends Controller
{
    /**
     * Record an offline payment against the request's quotation. Payments are
     * never auto-confirmed here.
     */
    public function store(Request $request, BranchRequest $branchRequest)
    {
        $this->authorizeScope($branchRequest);

        $validated = $request->validate([
            'payment_mode' => 'required|string|in:' . implode(',', BranchPayment::MODES),
            'reference_no' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:2000',
            'paid_at' => 'nullable|date',
        ]);

        try {
            $payment = app(BranchRequestService::class)
                ->recordPayment($branchRequest, $validated, $request->user());
        } catch (BranchRequestException $e) {
            return back()
                ->withErrors(['payment' => $e->getMessage()])
                ->with('payment_error_code', $e->errorCode());
        }

        $discrepancy = $payment->amount !== round((float) $branchRequest->quotation->total, 2)
            ? ' Payment amount differs from the quotation total and will require manual review before confirmation.'
            : '';

        return redirect()->route('branch-requests.show', $branchRequest)
            ->with('success', 'Payment recorded pending confirmation.' . $discrepancy);
    }

    /**
     * Staff confirmation — the ONLY action that raises the branch limit.
     */
    public function confirm(Request $request, BranchRequest $branchRequest, BranchPayment $payment)
    {
        $this->authorizeScope($branchRequest);
        $this->authorizePayment($branchRequest, $payment);

        try {
            app(BranchRequestService::class)->confirmPayment($payment, $request->user());
        } catch (BranchRequestException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        app(BranchRequestNotifier::class)
            ->notifyRequester($branchRequest, new BranchPaymentConfirmedMail($branchRequest));

        return redirect()->route('branch-requests.show', $branchRequest)
            ->with('success', 'Payment confirmed. The branch limit has been raised.');
    }

    public function reject(Request $request, BranchRequest $branchRequest, BranchPayment $payment)
    {
        $this->authorizeScope($branchRequest);
        $this->authorizePayment($branchRequest, $payment);

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        try {
            app(BranchRequestService::class)->rejectPayment($payment, $request->user(), $validated['reason']);
        } catch (BranchRequestException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()->route('branch-requests.show', $branchRequest)
            ->with('success', 'Payment rejected.');
    }

    private function authorizeScope(BranchRequest $branchRequest): void
    {
        abort_unless((int) $branchRequest->company_id === (int) session('current_company_id'), 403);
    }

    private function authorizePayment(BranchRequest $branchRequest, BranchPayment $payment): void
    {
        abort_unless((int) $payment->billing_quotation_id === (int) $branchRequest->quotation?->id, 403);
    }
}
