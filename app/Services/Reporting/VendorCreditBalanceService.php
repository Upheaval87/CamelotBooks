<?php
namespace App\Services\Reporting;
use App\Models\VendorCredit;

class VendorCreditBalanceService
{
    public function generate(int $companyId): array
    {
        $credits = VendorCredit::forCompany($companyId)
            ->whereIn('status', [VendorCredit::STATUS_POSTED, VendorCredit::STATUS_APPLIED])
            ->with('vendor')->get();

        $results = [];
        foreach ($credits as $vc) {
            $unapplied = (float) $vc->amount - (float) $vc->amount_applied - (float) $vc->amount_refunded;
            if ($unapplied > 0.001) {
                $results[] = [
                    'credit_note_number' => $vc->credit_note_number,
                    'date' => $vc->credit_note_date,
                    'vendor_name' => $vc->vendor->name ?? 'N/A',
                    'amount' => (float) $vc->amount,
                    'applied' => (float) $vc->amount_applied,
                    'refunded' => (float) $vc->amount_refunded,
                    'unapplied' => $unapplied,
                ];
            }
        }
        usort($results, fn($a, $b) => $b['unapplied'] <=> $a['unapplied']);
        return ['credits' => $results, 'total_unapplied' => array_sum(array_column($results, 'unapplied'))];
    }
}
