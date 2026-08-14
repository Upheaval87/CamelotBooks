<?php

namespace App\Services\BankReconciliation;

use App\Models\Reconciliation;
use App\Models\ReconciliationAuditLog;

class AuditTrailService
{
    public function log(
        Reconciliation $reconciliation,
        string $action,
        int $userId,
        ?array $details = null
    ): ReconciliationAuditLog {
        return ReconciliationAuditLog::create([
            'company_id' => $reconciliation->company_id,
            'reconciliation_id' => $reconciliation->id,
            'action' => $action,
            'details' => $details,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    public function forReconciliation(int $reconciliationId)
    {
        return ReconciliationAuditLog::query()
            ->where('reconciliation_id', $reconciliationId)
            ->with('user')
            ->orderByDesc('id')
            ->get();
    }
}
