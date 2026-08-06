<?php

namespace App\Services\BranchRequests;

use App\Models\BillingQuotation;
use App\Models\BranchPayment;
use App\Models\BranchRequest;
use App\Models\Company;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Branch-request lifecycle orchestrator (tenant side). The Super Admin panel
 * approves/rejects/quotes; the company records offline payments; ONLY a
 * billing/accounting/super-admin actor can confirm a payment, and confirmation
 * is the single action that raises the central `companies.branch_limit`
 * (additively, by the quoted quantity).
 */
class BranchRequestService
{
    public function __construct(
        private readonly BranchPricingService $pricing,
        private readonly NumberingSequenceService $numbering,
    ) {
    }

    public function hasOpenRequest(Company $company): bool
    {
        return BranchRequest::query()
            ->where('company_id', $company->id)
            ->whereIn('status', BranchRequest::OPEN_STATUSES)
            ->exists();
    }

    public function submit(Company $company, array $data, User $user): BranchRequest
    {
        if ($this->hasOpenRequest($company)) {
            throw new BranchRequestException(
                'A branch request is already pending review, quoting, or payment. Submit a new request once it is resolved.',
                BranchRequestException::CODE_OPEN_REQUEST_EXISTS,
            );
        }

        return BranchRequest::create([
            'company_id' => $company->id,
            'branch_name' => $data['branch_name'],
            'branch_code' => $data['branch_code'] ?? null,
            'branch_address' => $data['branch_address'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'requested_quantity' => (int) ($data['requested_quantity'] ?? 1),
            'reason' => $data['reason'] ?? null,
            'status' => BranchRequest::STATUS_PENDING_REVIEW,
            'requested_by_user_id' => $user->id,
            'requested_at' => now(),
        ]);
    }

    /**
     * Super Admin approval: issues a quotation for the requested quantity and
     * moves the request to `quoted`. The total is computed server-side and
     * FROZEN onto the quotation row (plus pricing_breakdown).
     */
    public function approveAndQuote(BranchRequest $request, User $admin, ?string $adminNotes = null): BillingQuotation
    {
        if (!in_array($request->status, [BranchRequest::STATUS_PENDING_REVIEW, BranchRequest::STATUS_QUOTED], true)) {
            throw new BranchRequestException(
                "A {$request->status} request cannot be quoted.",
                BranchRequestException::CODE_INVALID_STATE,
            );
        }

        $company = $request->company;

        return DB::transaction(function () use ($request, $admin, $adminNotes, $company) {
            $pricing = $this->pricing->quote(
                $request->requested_quantity,
                ['currency_code' => $company->base_currency ?? 'USD'],
            );

            $quotation = BillingQuotation::create([
                'company_id' => $request->company_id,
                'branch_request_id' => $request->id,
                'quotation_number' => $this->nextQuotationNumber($request->company_id),
                'status' => BillingQuotation::STATUS_PENDING,
                'unit_price' => $pricing['unit_price'],
                'quantity' => $pricing['quantity'],
                'subtotal' => $pricing['subtotal'],
                'tax_rate' => $pricing['tax_rate'],
                'tax_amount' => $pricing['tax_amount'],
                'total' => $pricing['total'],
                'currency_code' => $pricing['currency_code'],
                'pricing_breakdown' => $pricing['breakdown'],
                'bank_reference' => $this->nextBankReference(),
                'created_by_user_id' => $admin->id,
                'valid_until' => now()->addDays((int) config('branch_requests.validity_days', 14)),
                'issued_at' => now(),
            ]);

            $request->forceFill([
                'status' => BranchRequest::STATUS_QUOTED,
                'admin_notes' => $adminNotes ?? $request->admin_notes,
                'reviewed_by_user_id' => $request->reviewed_by_user_id ?? $admin->id,
                'reviewed_at' => $request->reviewed_at ?? now(),
            ])->save();

            SuperAdminAuditLog::log(
                $admin->id,
                SuperAdminAuditLog::ACTION_BRANCH_REQUEST_APPROVED,
                $request->company_id,
                'branch_request',
                $request->id,
                ['status' => BranchRequest::STATUS_PENDING_REVIEW],
                [
                    'status' => BranchRequest::STATUS_QUOTED,
                    'quotation_number' => $quotation->quotation_number,
                    'total' => $quotation->total,
                ],
                "Quotation {$quotation->quotation_number} ({$quotation->total} {$quotation->currency_code}) issued for '{$request->branch_name}'.",
            );

            return $quotation;
        });
    }

    public function reject(BranchRequest $request, User $admin, string $reason): BranchRequest
    {
        if ($request->status !== BranchRequest::STATUS_PENDING_REVIEW) {
            throw new BranchRequestException(
                "A {$request->status} request cannot be rejected.",
                BranchRequestException::CODE_INVALID_STATE,
            );
        }

        $request->forceFill([
            'status' => BranchRequest::STATUS_REJECTED,
            'admin_notes' => $reason,
            'reviewed_by_user_id' => $admin->id,
            'reviewed_at' => now(),
            'rejected_at' => now(),
        ])->save();

        SuperAdminAuditLog::log(
            $admin->id,
            SuperAdminAuditLog::ACTION_BRANCH_REQUEST_REJECTED,
            $request->company_id,
            'branch_request',
            $request->id,
            ['status' => BranchRequest::STATUS_PENDING_REVIEW],
            ['status' => BranchRequest::STATUS_REJECTED, 'reason' => $reason],
            "Branch request for '{$request->branch_name}' rejected: {$reason}",
        );

        return $request;
    }

    /**
     * Record an offline payment against the request's quotation. Never
     * auto-confirms. Cash is restricted to billing/accounting/super-admin
     * actors and always requires notes.
     */
    public function recordPayment(BranchRequest $request, array $data, User $user): BranchPayment
    {
        $quotation = $request->quotation;

        if (!$quotation || $quotation->status !== BillingQuotation::STATUS_PENDING) {
            throw new BranchRequestException(
                'This request has no payable quotation.',
                BranchRequestException::CODE_NOT_QUOTED,
            );
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new BranchRequestException('Payment amount must be greater than zero.', BranchRequestException::CODE_INVALID_AMOUNT);
        }

        $mode = $data['payment_mode'];

        if ($mode === BranchPayment::MODE_CASH) {
            $this->assertCanRecordCash($user);
            if (blank($data['notes'] ?? null)) {
                throw new BranchRequestException('A note is required when recording a cash payment.', BranchRequestException::CODE_CASH_NOTES_REQUIRED);
            }
        }

        $payment = BranchPayment::create([
            'company_id' => $request->company_id,
            'billing_quotation_id' => $quotation->id,
            'payment_mode' => $mode,
            'reference_no' => $data['reference_no'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'amount' => $amount,
            'notes' => $data['notes'] ?? null,
            'status' => BranchPayment::STATUS_PENDING,
            'recorded_by_user_id' => $user->id,
            'paid_at' => $data['paid_at'] ?? now(),
        ]);

        if ($request->status === BranchRequest::STATUS_QUOTED) {
            $request->forceFill(['status' => BranchRequest::STATUS_AWAITING_PAYMENT])->save();
        }

        return $payment;
    }

    /**
     * Staff confirmation of a payment. This is the ONLY path that raises the
     * company's branch_limit, additively by the quoted quantity.
     */
    public function confirmPayment(BranchPayment $payment, User $user): BranchPayment
    {
        $this->assertCanConfirmPayment($user);

        if ($payment->status !== BranchPayment::STATUS_PENDING) {
            throw new BranchRequestException(
                'Only a pending payment can be confirmed.',
                BranchRequestException::CODE_INVALID_STATE,
            );
        }

        $quotation = $payment->quotation;

        if (!$quotation || $quotation->status !== BillingQuotation::STATUS_PENDING) {
            throw new BranchRequestException(
                'This payment belongs to a quotation that is no longer payable.',
                BranchRequestException::CODE_QUOTE_EXPIRED,
            );
        }

        return DB::transaction(function () use ($payment, $quotation, $user) {
            $request = $quotation->branchRequest;

            if ($request->status === BranchRequest::STATUS_APPROVED) {
                throw new BranchRequestException(
                    'This request has already been fulfilled.',
                    BranchRequestException::CODE_ALREADY_FULFILLED,
                );
            }

            // Serialize concurrent confirmations on the CENTRAL company row so
            // two confirms can never double-raise the limit.
            $company = Company::query()->lockForUpdate()->findOrFail($quotation->company_id);

            if ($company->branch_limit !== null) {
                $company->increment('branch_limit', $quotation->quantity);
            }

            $payment->forceFill([
                'status' => BranchPayment::STATUS_CONFIRMED,
                'confirmed_by_user_id' => $user->id,
                'confirmed_at' => now(),
            ])->save();

            $quotation->forceFill([
                'status' => BillingQuotation::STATUS_PAID,
                'paid_at' => now(),
            ])->save();

            $request->forceFill([
                'status' => BranchRequest::STATUS_APPROVED,
                'approved_at' => now(),
                'reviewed_by_user_id' => $request->reviewed_by_user_id ?? $user->id,
                'reviewed_at' => $request->reviewed_at ?? now(),
            ])->save();

            SuperAdminAuditLog::log(
                $user->id,
                SuperAdminAuditLog::ACTION_BRANCH_REQUEST_APPROVED,
                $company->id,
                'branch_request',
                $request->id,
                ['status' => BranchRequest::STATUS_AWAITING_PAYMENT],
                [
                    'status' => BranchRequest::STATUS_APPROVED,
                    'branch_limit' => $company->branch_limit,
                    'raised_by' => $quotation->quantity,
                ],
                "Branch request '{$request->branch_name}' fulfilled: branch_limit raised to {$company->branch_limit}.",
            );

            SuperAdminAuditLog::log(
                $user->id,
                SuperAdminAuditLog::ACTION_BRANCH_PAYMENT_CONFIRMED,
                $company->id,
                'branch_payment',
                $payment->id,
                ['status' => BranchPayment::STATUS_PENDING],
                ['status' => BranchPayment::STATUS_CONFIRMED, 'amount' => $payment->amount],
                "Payment #{$payment->id} ({$payment->modeLabel()}) for quotation {$quotation->quotation_number} confirmed.",
            );

            return $payment;
        });
    }

    public function rejectPayment(BranchPayment $payment, User $user, string $reason): BranchPayment
    {
        $this->assertCanConfirmPayment($user);

        if ($payment->status !== BranchPayment::STATUS_PENDING) {
            throw new BranchRequestException(
                'Only a pending payment can be rejected.',
                BranchRequestException::CODE_INVALID_STATE,
            );
        }

        $payment->forceFill([
            'status' => BranchPayment::STATUS_REJECTED,
            'confirmed_by_user_id' => $user->id,
            'rejection_reason' => $reason,
        ])->save();

        return $payment;
    }

    public function cancel(BranchRequest $request, User $user): BranchRequest
    {
        if (!in_array($request->status, [BranchRequest::STATUS_PENDING_REVIEW, BranchRequest::STATUS_QUOTED], true)) {
            throw new BranchRequestException(
                'Only a pending or quoted request can be cancelled.',
                BranchRequestException::CODE_INVALID_STATE,
            );
        }

        $request->forceFill(['status' => BranchRequest::STATUS_CANCELLED])->save();

        if ($request->quotation && $request->quotation->status === BillingQuotation::STATUS_PENDING) {
            $request->quotation->forceFill(['status' => BillingQuotation::STATUS_CANCELLED])->save();
        }

        return $request;
    }

    /**
     * Mark every pending quotation past its valid_until as expired, along with
     * its branch request (new requests create a fresh row; nothing is ever
     * resurrected). Returns the number of quotations expired.
     */
    public function expireOverdue(Company $company): int
    {
        $quotations = BillingQuotation::query()
            ->where('company_id', $company->id)
            ->where('status', BillingQuotation::STATUS_PENDING)
            ->where('valid_until', '<', now())
            ->get();

        foreach ($quotations as $quotation) {
            $quotation->forceFill(['status' => BillingQuotation::STATUS_EXPIRED])->save();

            $quotation->branchRequest->forceFill(['status' => BranchRequest::STATUS_EXPIRED])->save();
        }

        return $quotations->count();
    }

    public function assertCanConfirmPayment(User $user): void
    {
        if ($user->isSuperAdmin() || $user->hasRole('system_admin')) {
            return;
        }

        if ($user->hasRole('accountant') || $user->hasRole('billing')) {
            return;
        }

        throw new BranchRequestException(
            'Only a billing, accounting, or system administrator can confirm payments.',
            BranchRequestException::CODE_CONFIRM_FORBIDDEN,
        );
    }

    public function assertCanRecordCash(User $user): void
    {
        if ($user->isSuperAdmin() || $user->hasRole('system_admin')) {
            return;
        }

        if ($user->hasRole('accountant') || $user->hasRole('billing')) {
            return;
        }

        throw new BranchRequestException(
            'Only a billing, accounting, or system administrator can record cash payments.',
            BranchRequestException::CODE_CASH_RESTRICTED,
        );
    }

    private function nextQuotationNumber(int $companyId): string
    {
        try {
            return $this->numbering->getNextNumber($companyId, 'billing_quotation');
        } catch (\RuntimeException) {
            // Legacy tenants may not have a sequence for the new document type.
            return 'BQ-' . now()->year . '-' . strtoupper(Str::random(6));
        }
    }

    private function nextBankReference(): string
    {
        $prefix = (string) config('branch_requests.bank_reference_prefix', 'BRQ-');
        return $prefix . strtoupper(Str::random(8));
    }
}
