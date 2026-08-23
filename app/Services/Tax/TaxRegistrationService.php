<?php

namespace App\Services\Tax;

use App\Models\TaxRegistration;
use App\Models\TaxType;
use App\Models\TaxJurisdiction;
use Carbon\Carbon;
use InvalidArgumentException;

class TaxRegistrationService
{
    public function checkRegistration(
        int $companyId,
        string $entityKind,
        int $entityId,
        int $taxTypeId,
        ?int $jurisdictionId = null,
        ?string $date = null,
    ): bool {
        $date = $date ?? Carbon::now()->toDateString();

        $query = TaxRegistration::where('company_id', $companyId)
            ->where('entity_kind', $entityKind)
            ->where('entity_id', $entityId)
            ->where('tax_type_id', $taxTypeId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $date);

        if ($jurisdictionId) {
            $query->where('jurisdiction_id', $jurisdictionId);
        }

        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', $date);
        })->exists();
    }

    public function register(
        int $companyId,
        string $entityKind,
        int $entityId,
        int $taxTypeId,
        int $jurisdictionId,
        string $regNumber,
        string $effectiveFrom,
        ?string $effectiveTo = null,
    ): TaxRegistration {
        $engine = app(TaxEngine::class);
        $engine->validateNoOverlappingRegistrations(
            $companyId,
            $entityKind,
            $entityId,
            $taxTypeId,
            $jurisdictionId,
            $effectiveFrom,
            $effectiveTo,
        );

        return TaxRegistration::create([
            'company_id'     => $companyId,
            'entity_kind'    => $entityKind,
            'entity_id'      => $entityId,
            'jurisdiction_id' => $jurisdictionId,
            'tax_type_id'    => $taxTypeId,
            'reg_number'     => $regNumber,
            'effective_from' => $effectiveFrom,
            'effective_to'   => $effectiveTo,
            'status'         => 'active',
        ]);
    }

    public function deregister(int $registrationId): TaxRegistration
    {
        $reg = TaxRegistration::findOrFail($registrationId);
        $reg->update(['status' => 'inactive', 'effective_to' => Carbon::now()->toDateString()]);
        return $reg->fresh();
    }
}
