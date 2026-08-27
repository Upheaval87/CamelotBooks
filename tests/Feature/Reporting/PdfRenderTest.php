<?php

namespace Tests\Feature\Reporting;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Models\Vendor;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * STAGE 3 — PDF Render Tests
 *
 * Verifies the 5 financial report PDFs render without errors
 * via the shared editorial template (§9).
 *
 * §9.8 rules: no meta block; actual-year column headers; footer;
 * negatives grey parentheses; red only for 90+ aging; sign-off;
 * one shared template for all five.
 */
class PdfRenderTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $incomeAccount;
    protected Account $expenseAccount;
    protected Account $assetAccount;
    protected Account $bankAccount;
    protected Account $cogsAccount;
    protected Account $liabilityAccount;
    protected Account $equityAccount;
    protected Account $fixedAssetAccount;
    protected Branch $branch;
    protected Customer $customer;
    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'PDFTEST',
            'name' => 'PDF Test Co',
            'base_currency' => 'MWK',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        foreach (['banking', 'budgets'] as $feature) {
            FeatureManagement::enable($this->company->id, $feature);
        }

        $this->branch = Branch::create(['company_id' => $this->company->id, 'name' => 'HQ', 'code' => 'BR01']);

        // ── Accounts ──
        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '4000',
            'name' => 'Sales Revenue', 'type' => 'income', 'sub_type' => 'revenue',
            'is_active' => true,
        ]);
        $this->cogsAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '5000',
            'name' => 'Cost of Goods Sold', 'type' => 'expense', 'sub_type' => 'cost_of_goods_sold',
            'is_active' => true,
        ]);
        $this->expenseAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '6100',
            'name' => 'Rent Expense', 'type' => 'expense', 'sub_type' => 'operating_expense',
            'is_active' => true,
        ]);
        $this->bankAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '1100',
            'name' => 'Main Bank', 'type' => 'asset', 'sub_type' => 'current_asset',
            'is_active' => true, 'is_bank_account' => true,
            'opening_balance' => 10000,
        ]);
        $this->assetAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '1200',
            'name' => 'Accounts Receivable', 'type' => 'asset', 'sub_type' => 'current_asset',
            'is_active' => true,
        ]);
        $this->fixedAssetAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '1500',
            'name' => 'Office Equipment', 'type' => 'asset', 'sub_type' => 'fixed_asset',
            'is_active' => true, 'opening_balance' => 5000,
        ]);
        $this->liabilityAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '2000',
            'name' => 'Accounts Payable', 'type' => 'liability', 'sub_type' => 'current_liability',
            'is_active' => true,
        ]);
        $this->equityAccount = Account::create([
            'company_id' => $this->company->id, 'code' => '3000',
            'name' => 'Retained Earnings', 'type' => 'equity', 'sub_type' => 'equity',
            'is_active' => true, 'opening_balance' => 15000,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id, 'name' => 'Widget Co',
            'payment_terms_days' => 30,
        ]);
        $this->vendor = Vendor::create([
            'company_id' => $this->company->id, 'name' => 'ACME Supplies',
            'payment_terms_days' => 30,
        ]);

        // ── Journal entries (posted) ──
        $je = JournalEntry::create([
            'company_id' => $this->company->id, 'journal_number' => 'JE-PDF-001',
            'date' => now()->subDays(20), 'status' => JournalEntry::STATUS_POSTED,
            'created_by' => $this->user->id, 'total_debit' => 1000, 'total_credit' => 1000,
        ]);
        JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $this->bankAccount->id, 'debit' => 1000, 'credit' => 0]);
        JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $this->incomeAccount->id, 'debit' => 0, 'credit' => 1000]);

        $je2 = JournalEntry::create([
            'company_id' => $this->company->id, 'journal_number' => 'JE-PDF-002',
            'date' => now()->subDays(10), 'status' => JournalEntry::STATUS_POSTED,
            'created_by' => $this->user->id, 'total_debit' => 400, 'total_credit' => 400,
        ]);
        JournalEntryLine::create(['journal_entry_id' => $je2->id, 'account_id' => $this->expenseAccount->id, 'debit' => 400, 'credit' => 0]);
        JournalEntryLine::create(['journal_entry_id' => $je2->id, 'account_id' => $this->bankAccount->id, 'debit' => 0, 'credit' => 400]);

        // COGS entry
        $je3 = JournalEntry::create([
            'company_id' => $this->company->id, 'journal_number' => 'JE-PDF-003',
            'date' => now()->subDays(15), 'status' => JournalEntry::STATUS_POSTED,
            'created_by' => $this->user->id, 'total_debit' => 250, 'total_credit' => 250,
        ]);
        JournalEntryLine::create(['journal_entry_id' => $je3->id, 'account_id' => $this->cogsAccount->id, 'debit' => 250, 'credit' => 0]);
        JournalEntryLine::create(['journal_entry_id' => $je3->id, 'account_id' => $this->bankAccount->id, 'debit' => 0, 'credit' => 250]);

        // ── Invoices (for AR aging) ──
        Invoice::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id, 'invoice_number' => 'INV-PDF-001',
            'invoice_date' => now()->subDays(10), 'due_date' => now()->addDays(20),
            'status' => 'posted', 'amount' => 500, 'amount_paid' => 0,
            'created_by' => $this->user->id,
        ]);
        // 90+ day invoice
        Invoice::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id, 'invoice_number' => 'INV-PDF-002',
            'invoice_date' => now()->subDays(120), 'due_date' => now()->subDays(90),
            'status' => 'posted', 'amount' => 300, 'amount_paid' => 0,
            'created_by' => $this->user->id,
        ]);

        // ── Bills (for AP aging) ──
        \App\Models\Bill::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'vendor_id' => $this->vendor->id, 'bill_number' => 'BILL-PDF-001',
            'bill_date' => now()->subDays(15), 'due_date' => now()->addDays(15),
            'status' => 'posted', 'amount' => 200, 'amount_paid' => 0,
            'created_by' => $this->user->id,
        ]);
        \App\Models\Bill::create([
            'company_id' => $this->company->id, 'branch_id' => $this->branch->id,
            'vendor_id' => $this->vendor->id, 'bill_number' => 'BILL-PDF-002',
            'bill_date' => now()->subDays(100), 'due_date' => now()->subDays(60),
            'status' => 'posted', 'amount' => 150, 'amount_paid' => 0,
            'created_by' => $this->user->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §9.8 — PDF Download Tests (all 5 reports)
    // ═══════════════════════════════════════════════════════════════════

    public function test_income_statement_pdf_downloads(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('accounting.reports.financial.pdf', [
                'report' => 'income',
                'date_from' => now()->startOfYear()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ])
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        // PDF magic bytes
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_balance_sheet_pdf_downloads(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('accounting.reports.financial.pdf', [
                'report' => 'position',
                'as_of_date' => now()->format('Y-m-d'),
            ])
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_cash_flow_pdf_downloads(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('accounting.reports.financial.pdf', [
                'report' => 'cashflow',
                'date_from' => now()->startOfYear()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ])
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_ar_aging_pdf_downloads(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('accounting.reports.financial.pdf', [
                'report' => 'ar-aging',
                'as_of_date' => now()->format('Y-m-d'),
            ])
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_ap_aging_pdf_downloads(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('accounting.reports.financial.pdf', [
                'report' => 'ap-aging',
                'as_of_date' => now()->format('Y-m-d'),
            ])
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §9.8 — PDF Preview (stream) Tests
    // ═══════════════════════════════════════════════════════════════════

    public function test_income_statement_preview_streams(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('accounting.reports.financial.preview', [
                'report' => 'income',
                'date_from' => now()->startOfYear()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ])
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Invalid report type → 404
    // ═══════════════════════════════════════════════════════════════════

    public function test_invalid_report_type_returns_404(): void
    {
        $response = $this->actingAs($this->user)->get(
            route('accounting.reports.financial.pdf', [
                'report' => 'nonexistent',
            ])
        );

        $response->assertStatus(404);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §9.3 — Content verification via Blade template (HTML mode)
    //
    //  DomPDF compresses text into binary streams, so we render the
    //  shared Blade template in HTML mode via view()->render() and
    //  assert on the plaintext output.
    // ═══════════════════════════════════════════════════════════════════

    public function test_income_statement_template_renders_title_and_sections(): void
    {
        $pdfService = app(\App\Services\Reporting\FiReportPdfService::class);
        $data = $pdfService->incomeStatement([
            'date_from' => now()->startOfYear()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);
        $data['pdfMode'] = false;

        $html = view('pdf.financial-report', $data)->render();

        $this->assertStringContainsString('INCOME STATEMENT', strtoupper($html));
        $this->assertStringContainsString('Revenue', $html);
        $this->assertStringContainsString('Gross Profit', $html);
        $this->assertStringContainsString('Prepared By', $html);
        $this->assertStringContainsString('Authorised By', $html);
        // §9.3 — no meta block
        $this->assertStringNotContainsString('frp-meta', $html);
    }

    public function test_balance_sheet_template_renders_balance_check(): void
    {
        $pdfService = app(\App\Services\Reporting\FiReportPdfService::class);
        $data = $pdfService->balanceSheet([
            'as_of_date' => now()->format('Y-m-d'),
        ]);
        $data['pdfMode'] = false;

        $html = view('pdf.financial-report', $data)->render();

        $this->assertStringContainsString('STATEMENT OF FINANCIAL POSITION', strtoupper($html));
        $this->assertStringContainsString('Assets', $html);
        $this->assertStringContainsString('Liabilities', $html);
        $this->assertStringContainsString('Equity', $html);
        // §9.4 — balance check line present
        $this->assertStringContainsString('frp-balance', $html);
        $this->assertStringContainsString('Total Assets equal', $html);
    }

    public function test_ar_aging_template_renders_aging_columns(): void
    {
        $pdfService = app(\App\Services\Reporting\FiReportPdfService::class);
        $data = $pdfService->arAging([
            'as_of_date' => now()->format('Y-m-d'),
        ]);
        $data['pdfMode'] = false;

        $html = view('pdf.financial-report', $data)->render();

        $this->assertStringContainsString('ACCOUNTS RECEIVABLE AGING', strtoupper($html));
        $this->assertStringContainsString('Summary by Customer', $html);
        // §9.3 — aging column headers
        $this->assertStringContainsString('Current', $html);
        $this->assertStringContainsString('90+', $html);
    }

    public function test_ap_aging_template_renders_aging_columns(): void
    {
        $pdfService = app(\App\Services\Reporting\FiReportPdfService::class);
        $data = $pdfService->apAging([
            'as_of_date' => now()->format('Y-m-d'),
        ]);
        $data['pdfMode'] = false;

        $html = view('pdf.financial-report', $data)->render();

        $this->assertStringContainsString('ACCOUNTS PAYABLE AGING', strtoupper($html));
        $this->assertStringContainsString('Summary by Vendor', $html);
        $this->assertStringContainsString('Current', $html);
        $this->assertStringContainsString('90+', $html);
    }

    public function test_cash_flow_template_renders_activity_sections(): void
    {
        $pdfService = app(\App\Services\Reporting\FiReportPdfService::class);
        $data = $pdfService->cashFlow([
            'date_from' => now()->startOfYear()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);
        $data['pdfMode'] = false;

        $html = view('pdf.financial-report', $data)->render();

        $this->assertStringContainsString('CASH FLOW STATEMENT', strtoupper($html));
        $this->assertStringContainsString('Operating Activities', $html);
        $this->assertStringContainsString('Investing Activities', $html);
        $this->assertStringContainsString('Financing Activities', $html);
        $this->assertStringContainsString('Opening Cash', $html);
        $this->assertStringContainsString('Closing Cash', $html);
    }

    public function test_signoff_present_in_template(): void
    {
        $pdfService = app(\App\Services\Reporting\FiReportPdfService::class);
        $data = $pdfService->incomeStatement([
            'date_from' => now()->startOfYear()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);
        $data['pdfMode'] = false;

        $html = view('pdf.financial-report', $data)->render();

        $this->assertStringContainsString('Prepared By', $html);
        $this->assertStringContainsString('Authorised By', $html);
    }

    public function test_template_with_empty_data_renders_empty_state(): void
    {
        $html = view('pdf.financial-report', [
            'title' => 'Empty Test',
            'periodLabel' => 'N/A',
            'currency' => '$',
            'columns' => ['A', 'B'],
            'sections' => [['label' => null, 'items' => []]],
            'totals' => null,
            'balanceCheck' => null,
            'signOff' => false,
            'pdfMode' => false,
        ])->render();

        $this->assertStringContainsString('EMPTY TEST', strtoupper($html));
        // §9.5 — signOff false = no sign-off lines
        $this->assertStringNotContainsString('Prepared By', $html);
    }

    public function test_template_negative_values_rendered_in_parens(): void
    {
        $html = view('pdf.financial-report', [
            'title' => 'Test',
            'periodLabel' => 'Test',
            'currency' => '$',
            'columns' => ['Amount'],
            'sections' => [[
                'label' => null,
                'items' => [
                    [
                        'label' => 'Loss',
                        'values' => ['(500)'],
                        'isSubtotal' => false,
                        'isTotal' => false,
                        'isSection' => false,
                    ],
                ],
            ]],
            'totals' => null,
            'balanceCheck' => null,
            'signOff' => false,
            'pdfMode' => false,
        ])->render();

        // §9.3 — negative values should have the grey-parens class
        $this->assertStringContainsString('frp-neg', $html);
    }

    public function test_template_balance_check_unbalanced_renders_red(): void
    {
        $html = view('pdf.financial-report', [
            'title' => 'Test',
            'periodLabel' => 'Test',
            'currency' => '$',
            'columns' => [],
            'sections' => [],
            'totals' => null,
            'balanceCheck' => ['text' => 'Out of balance', 'balanced' => false],
            'signOff' => false,
            'pdfMode' => false,
        ])->render();

        $this->assertStringContainsString('frp-unbalanced', $html);
        $this->assertStringContainsString('Out of balance', $html);
        $this->assertStringContainsString('✗', $html);
    }

    public function test_template_balance_check_balanced_renders_green(): void
    {
        $html = view('pdf.financial-report', [
            'title' => 'Test',
            'periodLabel' => 'Test',
            'currency' => '$',
            'columns' => [],
            'sections' => [],
            'totals' => null,
            'balanceCheck' => ['text' => 'Balanced OK', 'balanced' => true],
            'signOff' => false,
            'pdfMode' => false,
        ])->render();

        // The CSS block defines .frp-unbalanced, so check the actual div class instead
        $this->assertMatchesRegularExpression('/class="frp-balance\s*"/', $html);
        $this->assertStringContainsString('Balanced OK', $html);
        $this->assertStringContainsString('✓', $html);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Unauthenticated → redirect
    // ═══════════════════════════════════════════════════════════════════

    public function test_unauthenticated_pdf_request_redirects(): void
    {
        $response = $this->get(
            route('accounting.reports.financial.pdf', ['report' => 'income'])
        );

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
