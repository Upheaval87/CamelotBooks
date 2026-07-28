<?php

namespace App\Services\Reporting;

use App\Models\Bill;
use App\Models\PurchaseRequisition;
use App\Models\Budget;
use Carbon\Carbon;

class PendingApprovalsAgingService
{
    public function generate(int $companyId): array
    {
        $results = [];
        $now = Carbon::now();

        $bills = Bill::where('company_id', $companyId)
            ->where('status', 'pending_approval')
            ->with('vendor')
            ->get();

        foreach ($bills as $bill) {
            $age = $bill->created_at->diffInDays($now);
            $results[] = [
                'type' => 'Bill',
                'reference' => $bill->bill_number,
                'date' => $bill->bill_date,
                'vendor_or_employee' => $bill->vendor->name ?? 'N/A',
                'amount' => (float) $bill->amount,
                'days_pending' => $age,
                'created_at' => $bill->created_at,
            ];
        }

        $purchaseReqs = PurchaseRequisition::where('company_id', $companyId)
            ->where('status', 'submitted')
            ->get();

        foreach ($purchaseReqs as $pr) {
            $age = $pr->created_at->diffInDays($now);
            $results[] = [
                'type' => 'Purchase Requisition',
                'reference' => $pr->pr_number ?? (string) $pr->id,
                'date' => $pr->created_at->format('Y-m-d'),
                'vendor_or_employee' => '—',
                'amount' => (float) ($pr->estimated_total ?? 0),
                'days_pending' => $age,
                'created_at' => $pr->created_at,
            ];
        }

        $budgets = Budget::where('company_id', $companyId)
            ->where('status', 'pending_approval')
            ->get();

        foreach ($budgets as $budget) {
            $age = $budget->created_at->diffInDays($now);
            $results[] = [
                'type' => 'Budget',
                'reference' => $budget->budget_name ?? (string) $budget->id,
                'date' => $budget->created_at->format('Y-m-d'),
                'vendor_or_employee' => '—',
                'amount' => (float) ($budget->total_amount ?? 0),
                'days_pending' => $age,
                'created_at' => $budget->created_at,
            ];
        }

        usort($results, fn ($a, $b) => $b['days_pending'] <=> $a['days_pending']);

        $totalAmount = array_sum(array_column($results, 'amount'));

        return [
            'pending' => $results,
            'total_amount' => $totalAmount,
            'total_count' => count($results),
        ];
    }
}
