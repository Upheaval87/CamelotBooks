<?php

namespace App\Services\BranchRequests;

use App\Mail\BranchRequestSubmittedMail;
use App\Models\BranchRequest;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Outbound notification dispatcher for the branch-request workflow. The
 * platform has no in-app notification channel, so everything is email via the
 * existing Mail\* mailable convention (queued, ShouldQueue). Recipients are
 * CENTRAL users (auth is central; tenant users are only stub rows).
 */
class BranchRequestNotifier
{
    public function notifySuperAdmins(BranchRequest $request): void
    {
        $admins = User::query()
            ->where('is_super_admin', true)
            ->where('is_active', true)
            ->get(['id', 'email']);

        foreach ($admins as $admin) {
            if ($admin->email) {
                Mail::to($admin->email)->queue(new BranchRequestSubmittedMail($request));
            }
        }
    }

    /**
     * Notify everyone holding the company-level `billing` role for the company.
     */
    public function notifyCompanyBillingUsers(BranchRequest $request): void
    {
        $emails = collect();

        $assignments = UserCompanyAssignment::query()
            ->where('company_id', $request->company_id)
            ->where('role', 'billing')
            ->where('is_active', true)
            ->with('user:id,email')
            ->get();

        foreach ($assignments as $assignment) {
            $emails->push($assignment->user->email);
        }

        // Legacy pivot back-compat (roles assigned before user_company_assignments).
        foreach ($request->company->users()->wherePivot('role', 'billing')->get(['users.email']) as $legacy) {
            $emails->push($legacy->email);
        }

        $emails = $emails->filter()->unique();

        foreach ($emails as $email) {
            Mail::to($email)->queue(new BranchRequestSubmittedMail($request));
        }
    }

    public function notifyRequester(BranchRequest $request, Mailable $mailable): void
    {
        $requester = User::find($request->requested_by_user_id);

        if ($requester?->email) {
            Mail::to($requester->email)->queue($mailable);
        }
    }
}
