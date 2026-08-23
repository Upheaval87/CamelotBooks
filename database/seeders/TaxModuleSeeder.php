<?php

namespace Database\Seeders;

use App\Models\TaxType;
use App\Models\TaxJurisdiction;
use App\Models\TaxCode;
use App\Models\TaxCodeRate;
use App\Models\TaxRecognitionRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxModuleSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = session('current_company_id') ?? 1;

        // ── Tax Types ──────────────────────────────────────────────
        $vat = TaxType::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'VAT'],
            ['name' => 'Value Added Tax', 'category' => 'VAT', 'active' => true]
        );
        $wht = TaxType::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'WHT'],
            ['name' => 'Withholding Tax', 'category' => 'WHT', 'active' => true]
        );
        $paye = TaxType::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'PAYE'],
            ['name' => 'Pay As You Earn', 'category' => 'PAYE', 'active' => true]
        );

        // ── Jurisdiction ───────────────────────────────────────────
        $mwi = TaxJurisdiction::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'MWI'],
            ['name' => 'Malawi', 'country' => 'Malawi', 'authority' => 'Malawi Revenue Authority', 'active' => true]
        );

        // ── Tax Codes ──────────────────────────────────────────────
        $vatStd = TaxCode::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'VAT_STD'],
            [
                'name' => 'Standard VAT',
                'tax_type_id' => $vat->id,
                'jurisdiction_id' => $mwi->id,
                'treatment' => 'STANDARD',
                'price_basis' => 'EXCLUSIVE',
                'rounding_mode' => 'HALF_UP',
                'rounding_level' => 'LINE',
                'effective_from' => '2024-01-01',
                'active' => true,
            ]
        );
        $vatZero = TaxCode::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'VAT_ZERO'],
            [
                'name' => 'Zero Rated',
                'tax_type_id' => $vat->id,
                'jurisdiction_id' => $mwi->id,
                'treatment' => 'ZERO_RATED',
                'price_basis' => 'EXCLUSIVE',
                'rounding_mode' => 'HALF_UP',
                'rounding_level' => 'LINE',
                'effective_from' => '2024-01-01',
                'active' => true,
            ]
        );
        $vatExempt = TaxCode::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'VAT_EXEMPT'],
            [
                'name' => 'Exempt',
                'tax_type_id' => $vat->id,
                'jurisdiction_id' => $mwi->id,
                'treatment' => 'EXEMPT',
                'price_basis' => 'EXCLUSIVE',
                'rounding_mode' => 'HALF_UP',
                'rounding_level' => 'LINE',
                'effective_from' => '2024-01-01',
                'active' => true,
            ]
        );
        $vatInclusive = TaxCode::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'VAT_INC'],
            [
                'name' => 'Standard VAT (Inclusive)',
                'tax_type_id' => $vat->id,
                'jurisdiction_id' => $mwi->id,
                'treatment' => 'STANDARD',
                'price_basis' => 'INCLUSIVE',
                'rounding_mode' => 'HALF_UP',
                'rounding_level' => 'LINE',
                'effective_from' => '2024-01-01',
                'active' => true,
            ]
        );
        $vatReverse = TaxCode::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'VAT_RC'],
            [
                'name' => 'Reverse Charge',
                'tax_type_id' => $vat->id,
                'jurisdiction_id' => $mwi->id,
                'treatment' => 'REVERSE_CHARGE',
                'price_basis' => 'EXCLUSIVE',
                'rounding_mode' => 'HALF_UP',
                'rounding_level' => 'LINE',
                'effective_from' => '2024-01-01',
                'active' => true,
            ]
        );
        $whtSup = TaxCode::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'WHT_SUP'],
            [
                'name' => 'Supplier Withholding',
                'tax_type_id' => $wht->id,
                'jurisdiction_id' => $mwi->id,
                'treatment' => 'DEDUCTED',
                'price_basis' => 'EXCLUSIVE',
                'rounding_mode' => 'HALF_UP',
                'rounding_level' => 'LINE',
                'effective_from' => '2024-01-01',
                'active' => true,
            ]
        );
        $whtServ = TaxCode::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'WHT_SERV'],
            [
                'name' => 'Service Withholding',
                'tax_type_id' => $wht->id,
                'jurisdiction_id' => $mwi->id,
                'treatment' => 'DEDUCTED',
                'price_basis' => 'EXCLUSIVE',
                'rounding_mode' => 'HALF_UP',
                'rounding_level' => 'LINE',
                'effective_from' => '2024-01-01',
                'active' => true,
            ]
        );
        $fbtStd = TaxCode::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'FBT_STD'],
            [
                'name' => 'Fringe Benefits Tax',
                'tax_type_id' => TaxType::firstOrCreate(
                    ['company_id' => $companyId, 'code' => 'FBT'],
                    ['name' => 'Fringe Benefits Tax', 'category' => 'FBT', 'active' => true]
                )->id,
                'jurisdiction_id' => $mwi->id,
                'treatment' => 'CHARGED',
                'price_basis' => 'EXCLUSIVE',
                'rounding_mode' => 'HALF_UP',
                'rounding_level' => 'LINE',
                'effective_from' => '2024-01-01',
                'active' => true,
            ]
        );

        // ── Tax Code Rates ─────────────────────────────────────────
        TaxCodeRate::firstOrCreate(
            ['tax_code_id' => $vatStd->id, 'effective_from' => '2024-01-01'],
            ['rate_pct' => 16.5]
        );
        TaxCodeRate::firstOrCreate(
            ['tax_code_id' => $vatZero->id, 'effective_from' => '2024-01-01'],
            ['rate_pct' => 0]
        );
        TaxCodeRate::firstOrCreate(
            ['tax_code_id' => $vatExempt->id, 'effective_from' => '2024-01-01'],
            ['rate_pct' => 0]
        );
        TaxCodeRate::firstOrCreate(
            ['tax_code_id' => $vatInclusive->id, 'effective_from' => '2024-01-01'],
            ['rate_pct' => 16.5]
        );
        TaxCodeRate::firstOrCreate(
            ['tax_code_id' => $vatReverse->id, 'effective_from' => '2024-01-01'],
            ['rate_pct' => 16.5]
        );
        TaxCodeRate::firstOrCreate(
            ['tax_code_id' => $whtSup->id, 'effective_from' => '2024-01-01'],
            ['rate_pct' => 10]
        );
        TaxCodeRate::firstOrCreate(
            ['tax_code_id' => $whtServ->id, 'effective_from' => '2024-01-01'],
            ['rate_pct' => 15]
        );
        TaxCodeRate::firstOrCreate(
            ['tax_code_id' => $fbtStd->id, 'effective_from' => '2024-01-01'],
            ['rate_pct' => 30]
        );

        // ── Recognition Rules (defaults) ───────────────────────────
        TaxRecognitionRule::firstOrCreate(
            ['company_id' => $companyId, 'tax_type_id' => $vat->id],
            ['basis' => 'INVOICE', 'note' => 'Tax recognised on invoice date regardless of payment']
        );
        TaxRecognitionRule::firstOrCreate(
            ['company_id' => $companyId, 'tax_type_id' => $wht->id],
            ['basis' => 'PAYMENT', 'note' => 'Deducted when supplier is paid']
        );
        TaxRecognitionRule::firstOrCreate(
            ['company_id' => $companyId, 'tax_type_id' => $paye->id],
            ['basis' => 'ACCRUAL', 'note' => 'Due on salary payment date']
        );
    }
}
