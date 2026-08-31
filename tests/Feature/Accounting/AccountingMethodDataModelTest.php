<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\MethodConversion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 1 — accounting-method data model.
 *
 * Covers the schema additions (central companies.accounting_method /
 * reporting_preference, tenant accounting_periods.basis, tenant
 * method_conversions) and the model wiring that surfaces the inherited method
 * on the COA setup page and drives the Switch-to-Accrual flow.
 */
class AccountingMethodDataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_defaults_to_accrual_method(): void
    {
        $company = Company::create(['name' => 'Defaults Co', 'company_code' => 'DFLT', 'is_active' => true]);

        $this->assertSame(Company::METHOD_ACCRUAL, $company->accounting_method);
        $this->assertSame(Company::REPORTING_ACCRUAL_VIEW, $company->reporting_preference);
        $this->assertTrue($company->isAccrual());
        $this->assertFalse($company->isCashBasis());
    }

    public function test_company_persists_cash_method_and_reporting_preference(): void
    {
        $company = Company::create([
            'name' => 'Cash Co',
            'company_code' => 'CSHC',
            'is_active' => true,
            'accounting_method' => Company::METHOD_CASH,
            'reporting_preference' => Company::REPORTING_CASH_VIEW,
        ]);

        $this->assertTrue($company->isCashBasis());
        $this->assertSame('Cash', $company->accountingMethodLabel());
        $this->assertSame('Cash view', $company->reportingPreferenceLabel());
        $this->assertSame(Company::METHOD_CASH, $company->fresh()->accounting_method);
    }

    public function test_accounting_period_persists_basis(): void
    {
        $company = Company::create(['name' => 'Period Co', 'company_code' => 'PRDC', 'is_active' => true]);
        $period = AccountingPeriod::create([
            'company_id' => $company->id,
            'label' => 'August 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => 'open',
            'basis' => 'cash',
        ]);

        $this->assertTrue($period->isCashBasis());
        $this->assertSame('cash', $period->fresh()->basis);
    }

    public function test_method_conversion_table_exists_and_model_writes(): void
    {
        $this->assertTrue(Schema::hasTable('method_conversions'));

        $company = Company::create(['name' => 'Conv Co', 'company_code' => 'CNVC', 'is_active' => true]);
        $conversion = MethodConversion::create([
            'company_id' => $company->id,
            'from_method' => Company::METHOD_CASH,
            'to_method' => Company::METHOD_ACCRUAL,
            'cut_off_date' => '2026-08-31',
            'treatment' => 'reclassify',
            'status' => MethodConversion::STATUS_DRAFT,
            'created_by' => 1,
        ]);

        $this->assertSame(MethodConversion::STATUS_DRAFT, $conversion->status);
        $this->assertSame(1, MethodConversion::forCompany($company->id)->count());
    }

    public function test_schema_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('companies', 'accounting_method'));
        $this->assertTrue(Schema::hasColumn('companies', 'reporting_preference'));
        $this->assertTrue(Schema::hasColumn('accounting_periods', 'basis'));
    }
}
