<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

/**
 * Authorization policy for the Transaction Controls workspace.
 *
 * This is a plain policy class (the workspace is not bound to a single model),
 * so it is instantiated directly by TransactionControlsController rather than
 * going through Gate auto-discovery.
 */
class TransactionControlPolicy
{
    /**
     * May the user open the workspace and view the reversal register?
     */
    public function viewReversals(User $user): bool
    {
        return $user->can('transaction-reversals.view');
    }

    /**
     * May the user capture a reversal (post immediately, submit for
     * authorization, or save as a draft)?
     */
    public function captureReversal(User $user): bool
    {
        return $user->can('transaction-reversals.request');
    }

    /**
     * May the user manage an unposted transaction (reopen)?
     */
    public function manageUnposted(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal-entries.edit')
            || (int) $entry->created_by === (int) $user->id;
    }

    /**
     * May the user delete a draft transaction? Owner or an editor.
     */
    public function deleteUnposted(User $user, JournalEntry $entry): bool
    {
        return (int) $entry->created_by === (int) $user->id
            || $user->can('journal-entries.edit');
    }

    /**
     * May the user post a finalized (unposted) transaction to the ledger?
     */
    public function postUnposted(User $user, JournalEntry $entry): bool
    {
        return $entry->status === JournalEntry::STATUS_PENDING_APPROVAL
            && $user->can('journal-entries.post');
    }

    /**
     * May the user act on the authorization queue (approve / reject)?
     */
    public function authorizeRequests(User $user): bool
    {
        return $user->can('transaction-reversals.approve')
            || $user->can('transaction-reversals.reject');
    }

    /**
     * Segregation of duties: the requester may never authorize their own
     * request.
     */
    public function dualControl(User $actor, ?int $requesterId): bool
    {
        return $requesterId === null || (int) $actor->id !== (int) $requesterId;
    }
}
