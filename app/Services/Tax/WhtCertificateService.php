<?php

namespace App\Services\Tax;

use App\Models\WhtCertificate;
use App\Models\TaxAuditTrail;
use App\Models\TaxTransaction;
use App\Models\TaxType;
use Carbon\Carbon;

class WhtCertificateService
{
    public function generate(
        int $companyId,
        int $transactionId,
        int $issuedByUserId,
        ?string $issueDate = null,
        ?int $supplierId = null,
    ): WhtCertificate {
        $tx = TaxTransaction::findOrFail($transactionId);

        abort_unless($tx->company_id === $companyId, 403);
        abort_unless($tx->side === 'INPUT', 400, 'WHT certificates apply to INPUT (withholding) transactions.');
        abort_unless($tx->status === 'POSTED', 400, 'Transaction must be posted.');

        $whtType = TaxType::where('company_id', $companyId)
            ->where('code', 'WHT')->first();

        abort_unless($whtType && $tx->taxCode?->tax_type_id === $whtType->id, 400, 'Transaction is not a WHT type.');

        $certNumber = $this->nextCertNumber($companyId);

        return WhtCertificate::create([
            'company_id'      => $companyId,
            'cert_number'     => $certNumber,
            'supplier_id'     => $supplierId ?? $tx->source_id,
            'tax_code_id'     => $tx->tax_code_id,
            'period_id'       => $tx->period_id,
            'gross'           => $tx->base_amount,
            'wht_amount'      => $tx->tax_amount,
            'rate_pct'        => $tx->rate_pct,
            'status'          => 'issued',
            'issued_date'     => $issueDate ?? Carbon::now()->toDateString(),
        ]);

        TaxAuditTrail::log($companyId, $issuedByUserId, 'WHT_CERTIFICATE', $cert->id, 'status', null, 'ISSUED', 'WHT certificate issued.');

        return $cert;
    }

    public function revoke(int $companyId, int $certId, int $revokedByUserId): WhtCertificate
    {
        $cert = WhtCertificate::findOrFail($certId);
        abort_unless($cert->company_id === $companyId, 403);

        $oldStatus = $cert->status;
        $cert->update(['status' => 'revoked']);

        TaxAuditTrail::log($companyId, $revokedByUserId, 'WHT_CERTIFICATE', $cert->id, 'status', strtoupper($oldStatus), 'REVOKED', 'WHT certificate revoked.');

        return $cert->fresh();
    }

    public function generateBatch(
        int $companyId,
        int $periodId,
        int $issuedByUserId,
    ): array {
        $taxTypeId = TaxType::where('company_id', $companyId)
            ->where('code', 'WHT')->value('id');

        if (!$taxTypeId) {
            return [];
        }

        $transactions = TaxTransaction::where('company_id', $companyId)
            ->whereHas('taxCode', fn ($q) => $q->where('tax_type_id', $taxTypeId))
            ->where('period_id', $periodId)
            ->where('side', 'INPUT')
            ->where('status', 'POSTED')
            ->get();

        $certs = [];
        foreach ($transactions as $tx) {
            $existing = WhtCertificate::where('tax_code_id', $tx->tax_code_id)
                ->where('period_id', $tx->period_id)
                ->where('status', '!=', 'revoked')
                ->exists();

            if (!$existing) {
                $certs[] = $this->generate($companyId, $tx->id, $issuedByUserId);
            }
        }

        return $certs;
    }

    public function createFromForm(int $companyId, array $data, int $issuedByUserId): WhtCertificate
    {
        $certNumber = $this->nextCertNumber($companyId);

        $cert = WhtCertificate::create([
            'company_id'      => $companyId,
            'cert_number'     => $certNumber,
            'supplier_id'     => $data['supplier_id'],
            'tax_code_id'     => $data['tax_code_id'],
            'period_id'       => $data['period_id'],
            'gross'           => $data['gross_amount'],
            'wht_amount'      => $data['tax_amount'],
            'rate_pct'        => 0,
            'status'          => 'issued',
            'issued_date'     => now()->toDateString(),
        ]);

        TaxAuditTrail::log($companyId, $issuedByUserId, 'WHT_CERTIFICATE', $cert->id, 'status', null, 'ISSUED', 'WHT certificate created from form.');

        return $cert;
    }

    protected function nextCertNumber(int $companyId): string
    {
        $last = WhtCertificate::where('company_id', $companyId)
            ->orderByDesc('id')->value('cert_number');

        if ($last && preg_match('/WHT-(\d+)/', $last, $m)) {
            return sprintf('WHT-%06d', (int) $m[1] + 1);
        }

        return 'WHT-000001';
    }
}
