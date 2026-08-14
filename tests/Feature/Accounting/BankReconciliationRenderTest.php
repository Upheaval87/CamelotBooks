<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Reconciliation;
use App\Models\ReconciliationAdjustment;
use App\Models\ReconciliationAuditLog;
use App\Models\ReconciliationMatch;
use App\Models\User;
use App\Services\FeatureManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BankReconciliationRenderTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Account $bank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'company_code' => 'TESTCO',
            'name' => 'Test Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);
        $this->user->assignRole('company_admin');
        session(['current_company_id' => $this->company->id]);

        FeatureManagement::enable($this->company->id, 'banking');

        $this->bank = Account::create([
            'company_id' => $this->company->id,
            'code' => 'BK01',
            'name' => 'Main Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank' => true,
            'is_bank_account' => true,
            'opening_balance' => 1000,
        ]);
    }

    protected function makeReconciliation(array $overrides = []): Reconciliation
    {
        return Reconciliation::create(array_merge([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bank->id,
            'statement_date' => '2026-08-01',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'opening_balance' => 1000,
            'closing_balance' => 1250,
            'currency' => 'USD',
            'status' => Reconciliation::STATUS_DRAFT,
            'statement_balance' => 1250,
            'book_balance' => 1250,
            'difference' => 0,
            'created_by' => $this->user->id,
        ], $overrides));
    }

    protected function seedWorkspaceData(Reconciliation $reconciliation): array
    {
        $import = BankStatementImport::create([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bank->id,
            'reconciliation_id' => $reconciliation->id,
            'filename' => 'august.csv',
            'statement_date' => '2026-08-31',
            'statement_end_balance' => 1250,
            'line_count' => 1,
            'imported_by' => $this->user->id,
        ]);

        $line = BankStatementLine::create([
            'import_id' => $import->id,
            'reconciliation_id' => $reconciliation->id,
            'bank_account_id' => $this->bank->id,
            'transaction_date' => '2026-08-15',
            'description' => 'Client payment',
            'reference' => 'REF-001',
            'amount' => 250,
            'balance' => 1250,
            'is_matched' => false,
            'status' => BankStatementLine::STATUS_UNMATCHED,
        ]);

        $entry = JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-001',
            'date' => '2026-08-15',
            'reference' => 'REF-001',
            'memo' => 'Bank receipt',
            'status' => JournalEntry::STATUS_POSTED,
            'source_module' => 'bank',
            'created_by' => $this->user->id,
        ]);

        $transaction = BankTransaction::create([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bank->id,
            'journal_entry_id' => $entry->id,
            'type' => 'deposit',
            'source_type' => 'journal',
            'source_id' => $entry->id,
            'date' => '2026-08-15',
            'description' => 'Client payment',
            'reference' => 'REF-001',
            'amount' => 250,
            'is_reconciled' => false,
            'created_by' => $this->user->id,
        ]);

        $match = ReconciliationMatch::create([
            'company_id' => $this->company->id,
            'reconciliation_id' => $reconciliation->id,
            'bank_statement_line_id' => $line->id,
            'bank_transaction_id' => $transaction->id,
            'method' => 'manual',
            'confidence' => 100,
            'created_by' => $this->user->id,
        ]);

        $line->is_matched = true;
        $line->status = BankStatementLine::STATUS_MATCHED;
        $line->match_id = $match->id;
        $line->save();

        $transaction->reconciliation_status = BankTransaction::RECON_STATUS_MATCHED;
        $transaction->save();

        $adjustment = ReconciliationAdjustment::create([
            'company_id' => $this->company->id,
            'reconciliation_id' => $reconciliation->id,
            'type' => ReconciliationAdjustment::TYPE_BANK_FEES,
            'side' => ReconciliationAdjustment::SIDE_BANK,
            'sign' => ReconciliationAdjustment::SIGN_SUBTRACT,
            'amount' => 10,
            'description' => 'Monthly fee',
            'status' => ReconciliationAdjustment::STATUS_PENDING,
            'created_by' => $this->user->id,
        ]);

        ReconciliationAuditLog::create([
            'company_id' => $this->company->id,
            'reconciliation_id' => $reconciliation->id,
            'action' => ReconciliationAuditLog::ACTION_CREATED,
            'details' => ['statement_number' => 'AUG-01'],
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        return compact('import', 'line', 'transaction', 'match', 'adjustment', 'entry');
    }

    public function test_index_renders_register(): void
    {
        $reconciliation = $this->makeReconciliation([
            'status' => Reconciliation::STATUS_RECONCILED,
        ]);
        $this->seedWorkspaceData($reconciliation);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.index'))
            ->assertOk()
            ->assertSee('Bank Reconciliation')
            ->assertSee('New Reconciliation')
            ->assertSee('Reconciliation Register')
            ->assertSee('BK01')
            ->assertSee('Reconciled');
    }

    public function test_index_accepts_bank_account_filter(): void
    {
        $reconciliation = $this->makeReconciliation();

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.index', $this->bank->id))
            ->assertOk()
            ->assertSee($reconciliation->statement_number ?? '');
    }

    public function test_create_renders_form(): void
    {
        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.create'))
            ->assertOk()
            ->assertSee('New Bank Reconciliation')
            ->assertSee('Save & Continue')
            ->assertSee('Bank Account')
            ->assertSee('Statement Date')
            ->assertSee('max-width:1120px;margin:0 auto', false)
            ->assertSee('<select id="currency" name="currency" class="input" required>', false)
            ->assertSee('<option value="USD" selected>', false)
            ->assertSee('Defaults to your system currency', false)
            ->assertDontSee('readonly');
    }

    public function test_store_persists_selected_currency_and_redirects_to_import(): void
    {
        $this->actingAs($this->user)->post(route('accounting.bank-reconciliation.store'), [
            'bank_account_id' => $this->bank->id,
            'statement_date' => '2026-08-31',
            'opening_balance' => 1000,
            'closing_balance' => 1250,
            'currency' => 'EUR',
        ])->assertRedirect(route('accounting.bank-reconciliation.import', Reconciliation::latest('id')->first()->id));

        $this->assertSame('EUR', Reconciliation::latest('id')->first()->currency);
    }

    public function test_store_defaults_to_system_currency_when_currency_omitted(): void
    {
        $this->actingAs($this->user)->post(route('accounting.bank-reconciliation.store'), [
            'bank_account_id' => $this->bank->id,
            'statement_date' => '2026-08-31',
            'opening_balance' => 1000,
            'closing_balance' => 1250,
        ])->assertRedirect();

        $this->assertSame('USD', Reconciliation::latest('id')->first()->currency);
    }

    public function test_service_create_tolerates_missing_period_keys(): void
    {
        $service = app(\App\Services\BankReconciliation\ReconciliationService::class);

        $reconciliation = $service->create([
            'company_id' => $this->company->id,
            'bank_account_id' => $this->bank->id,
            'statement_date' => '2026-08-31',
            'opening_balance' => 1000,
            'closing_balance' => 1250,
            'currency' => 'USD',
        ], $this->user->id);

        $this->assertSame('USD', $reconciliation->currency);
        $this->assertSame('2026-08-31', $reconciliation->period_start?->format('Y-m-d'));
        $this->assertSame('2026-08-31', $reconciliation->period_end?->format('Y-m-d'));
    }

    public function test_workspace_renders_full_match_ui(): void
    {
        $reconciliation = $this->makeReconciliation();
        $this->seedWorkspaceData($reconciliation);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.workspace', $reconciliation->id))
            ->assertOk()
            ->assertSee('Statement Lines')
            ->assertSee('Book Transactions')
            ->assertSee('Adjustments')
            ->assertSee('Balance Summary')
            ->assertSee('Suggestions')
            ->assertSee('Imports')
            ->assertSee('REF-001')
            ->assertSee('Client payment')
            ->assertSee('Monthly fee');
    }

    public function test_show_renders_review_page(): void
    {
        $reconciliation = $this->makeReconciliation([
            'status' => Reconciliation::STATUS_READY_FOR_REVIEW,
        ]);
        $this->seedWorkspaceData($reconciliation);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.show', $reconciliation->id))
            ->assertOk()
            ->assertSee('Reconciliation Review')
            ->assertSee('Reconciliation Details')
            ->assertSee('Audit Trail')
            ->assertSee('Balance Summary')
            ->assertSee('Statement Lines');
    }

    public function test_audit_renders_activity_log(): void
    {
        $reconciliation = $this->makeReconciliation();
        $this->seedWorkspaceData($reconciliation);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.audit', $reconciliation->id))
            ->assertOk()
            ->assertSee('Audit Trail')
            ->assertSee('Activity')
            ->assertSee('Created')
            ->assertSee('Statement Number');
    }

    public function test_import_renders_upload_form(): void
    {
        $reconciliation = $this->makeReconciliation();
        $this->seedWorkspaceData($reconciliation);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.import', $reconciliation->id))
            ->assertOk()
            ->assertSee('Import Bank Statement')
            ->assertSee('Preview & Map Columns')
            ->assertSee('Recent Imports')
            ->assertSee('Supported formats')
            ->assertSee('august.csv')
            ->assertSee('max-width:1120px;margin:0 auto', false);
    }

    public function test_import_post_requires_file(): void
    {
        $reconciliation = $this->makeReconciliation();

        $this->actingAs($this->user)->post(route('accounting.bank-reconciliation.import.submit', $reconciliation->id), [])
            ->assertSessionHasErrors('statement_file');
    }

    public function test_preview_renders_mapping_screen(): void
    {
        $reconciliation = $this->makeReconciliation();

        $csv = "Date,Description,Reference,Withdrawal,Deposit,Balance\n"
            . "2026-08-03,Opening purchase,INV-001,150,,1100\n"
            . "2026-08-05,Sales deposit,RCT-100,,250,1350\n"
            . "2026-08-10,ATM withdrawal,WD-001,50,,1300\n";

        $file = UploadedFile::fake()->createWithContent('august.csv', $csv);

        $this->actingAs($this->user)->post(
            route('accounting.bank-reconciliation.import.preview', $reconciliation->id),
            ['statement_file' => $file]
        )
            ->assertOk()
            ->assertSee('Map Statement Columns')
            ->assertSee('4 rows')
            ->assertSee('august.csv')
            ->assertSee('Sales deposit')
            ->assertSee('name="upload"', false)
            ->assertSee('name="map[date]"', false)
            ->assertSee('name="has_header"', false)
            ->assertSee('<option value="0" selected>', false)
            ->assertSee('<option value="3" selected>', false)
            ->assertSee('<option value="4" selected>', false);
    }

    public function test_mapped_import_persists_lines_with_signed_amounts(): void
    {
        $reconciliation = $this->makeReconciliation();

        $csv = "Date,Description,Reference,Withdrawal,Deposit,Balance\n"
            . "2026-08-03,Opening purchase,INV-001,150,,1100\n"
            . "2026-08-05,Sales deposit,RCT-100,,250,1350\n"
            . "2026-08-10,ATM withdrawal,WD-001,50,,1300\n";

        $file = UploadedFile::fake()->createWithContent('august.csv', $csv);

        $preview = $this->actingAs($this->user)->post(
            route('accounting.bank-reconciliation.import.preview', $reconciliation->id),
            ['statement_file' => $file]
        );
        preg_match('/name="upload" value="([^"]+)"/', $preview->getContent(), $m);
        $this->assertNotEmpty($m, 'Expected an upload token in the preview response.');

        $this->actingAs($this->user)->post(
            route('accounting.bank-reconciliation.import.submit', $reconciliation->id),
            [
                'upload' => $m[1],
                'has_header' => '1',
                'map' => [
                    'date' => '0',
                    'description' => '1',
                    'reference' => '2',
                    'debit' => '3',
                    'credit' => '4',
                    'amount' => '',
                    'balance' => '5',
                ],
            ]
        )->assertRedirect(route('accounting.bank-reconciliation.workspace', $reconciliation->id));

        $lines = BankStatementLine::where('reconciliation_id', $reconciliation->id)
            ->orderBy('transaction_date')
            ->get();

        $this->assertCount(3, $lines);
        $this->assertSame('2026-08-03', $lines[0]->transaction_date->format('Y-m-d'));
        $this->assertSame('Opening purchase', $lines[0]->description);
        $this->assertSame('INV-001', $lines[0]->reference);
        $this->assertSame(-150.0, (float) $lines[0]->amount);

        $this->assertSame('2026-08-05', $lines[1]->transaction_date->format('Y-m-d'));
        $this->assertSame('Sales deposit', $lines[1]->description);
        $this->assertSame(250.0, (float) $lines[1]->amount);

        $this->assertSame('2026-08-10', $lines[2]->transaction_date->format('Y-m-d'));
        $this->assertSame(-50.0, (float) $lines[2]->amount);
        $this->assertSame(1300.0, (float) $lines[2]->balance);

        $this->assertSame(3, BankStatementImport::where('reconciliation_id', $reconciliation->id)->first()->line_count);
        $this->assertSame(Reconciliation::STATUS_IN_PROGRESS, $reconciliation->fresh()->status);
    }

    public function test_mapped_import_derives_amount_from_single_amount_column(): void
    {
        $reconciliation = $this->makeReconciliation();

        $csv = "Date,Description,Amount\n"
            . "2026-08-03,Opening purchase,150\n"
            . "2026-08-05,Sales deposit,-250\n";

        $file = UploadedFile::fake()->createWithContent('transactions.csv', $csv);

        $preview = $this->actingAs($this->user)->post(
            route('accounting.bank-reconciliation.import.preview', $reconciliation->id),
            ['statement_file' => $file]
        );
        preg_match('/name="upload" value="([^"]+)"/', $preview->getContent(), $m);
        $this->assertNotEmpty($m, 'Expected an upload token in the preview response.');

        $this->actingAs($this->user)->post(
            route('accounting.bank-reconciliation.import.submit', $reconciliation->id),
            [
                'upload' => $m[1],
                'has_header' => '1',
                'map' => [
                    'date' => '0',
                    'description' => '1',
                    'reference' => '',
                    'debit' => '',
                    'credit' => '',
                    'amount' => '2',
                    'balance' => '',
                ],
            ]
        )->assertRedirect(route('accounting.bank-reconciliation.workspace', $reconciliation->id));

        $lines = BankStatementLine::where('reconciliation_id', $reconciliation->id)
            ->orderBy('transaction_date')
            ->get();

        $this->assertCount(2, $lines);
        $this->assertSame(150.0, (float) $lines[0]->amount);
        $this->assertSame(-250.0, (float) $lines[1]->amount);
    }

    public function test_mapped_import_requires_date_mapping(): void
    {
        $reconciliation = $this->makeReconciliation();

        $csv = "Date,Description,Withdrawal,Deposit\n"
            . "2026-08-03,Opening purchase,150,\n"
            . "2026-08-05,Sales deposit,,250\n";

        $file = UploadedFile::fake()->createWithContent('august.csv', $csv);

        $preview = $this->actingAs($this->user)->post(
            route('accounting.bank-reconciliation.import.preview', $reconciliation->id),
            ['statement_file' => $file]
        );
        preg_match('/name="upload" value="([^"]+)"/', $preview->getContent(), $m);
        $this->assertNotEmpty($m, 'Expected an upload token in the preview response.');

        $this->actingAs($this->user)->post(
            route('accounting.bank-reconciliation.import.submit', $reconciliation->id),
            [
                'upload' => $m[1],
                'has_header' => '1',
                'map' => [
                    'date' => '',
                    'description' => '1',
                    'reference' => '',
                    'debit' => '2',
                    'credit' => '3',
                    'amount' => '',
                    'balance' => '',
                ],
            ]
        )
            ->assertOk()
            ->assertSee('Map the Transaction Date column to continue.');

        $this->assertSame(0, BankStatementLine::where('reconciliation_id', $reconciliation->id)->count());
    }

    public function test_statements_renders_imports_register(): void
    {
        $reconciliation = $this->makeReconciliation();
        $this->seedWorkspaceData($reconciliation);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.statements'))
            ->assertOk()
            ->assertSee('Bank Statements')
            ->assertSee('Statement Imports')
            ->assertSee('august.csv')
            ->assertSee('BK01')
            ->assertSee('Main Bank');
    }

    public function test_adjustments_renders_register(): void
    {
        $reconciliation = $this->makeReconciliation();
        $this->seedWorkspaceData($reconciliation);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.adjustments'))
            ->assertOk()
            ->assertSee('Adjustment Register')
            ->assertSee('Bank Fees')
            ->assertSee('10.00')
            ->assertSee('Bank');
    }

    public function test_outstanding_renders_unmatched_lines(): void
    {
        $reconciliation = $this->makeReconciliation();
        $data = $this->seedWorkspaceData($reconciliation);

        BankStatementLine::create([
            'import_id' => $data['import']->id,
            'company_id' => $this->company->id,
            'reconciliation_id' => $reconciliation->id,
            'bank_account_id' => $this->bank->id,
            'transaction_date' => '2026-08-20',
            'description' => 'Pending cheque',
            'reference' => 'CHQ-100',
            'amount' => -50,
            'balance' => 1200,
            'is_matched' => false,
            'status' => BankStatementLine::STATUS_UNMATCHED,
        ]);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.outstanding'))
            ->assertOk()
            ->assertSee('Outstanding Items')
            ->assertSee('Outstanding Statement Lines')
            ->assertSee('CHQ-100')
            ->assertSee('Pending cheque')
            ->assertDontSee('REF-001');
    }

    public function test_reports_renders_approval_setting_and_cards(): void
    {
        $reconciliation = $this->makeReconciliation();
        $this->seedWorkspaceData($reconciliation);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.reports'))
            ->assertOk()
            ->assertSee('Approval Setting')
            ->assertSee('Require approval before completion')
            ->assertSee('Reconciliation Summary')
            ->assertSee('Outstanding Transactions')
            ->assertSee('Reconciliation Exceptions')
            ->assertSee('Reconciliation History')
            ->assertSee('BK01');

        $this->actingAs($this->user)
            ->post(route('accounting.bank-reconciliation.approval'), ['enabled' => '1'])
            ->assertRedirect(route('accounting.bank-reconciliation.reports'));
        $this->assertDatabaseHas('approval_settings', [
            'company_id' => $this->company->id,
            'requires_approval' => 1,
        ]);
    }

    public function test_audit_all_renders_activity_log(): void
    {
        $reconciliation = $this->makeReconciliation();
        $this->seedWorkspaceData($reconciliation);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.audit-all'))
            ->assertOk()
            ->assertSee('Audit Trail')
            ->assertSee('Activity')
            ->assertSee('Created')
            ->assertSee('BK01');
    }

    public function test_report_detail_renders_all_variants(): void
    {
        $reconciliation = $this->makeReconciliation();
        $this->seedWorkspaceData($reconciliation);

        foreach (['summary', 'outstanding', 'unmatched', 'detail', 'history', 'exceptions'] as $report) {
            $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.report', ['report' => $report]))
                ->assertOk();
        }

        $this->actingAs($this->user)
            ->get(route('accounting.bank-reconciliation.report', ['report' => 'summary']))
            ->assertSee('Summary Report')
            ->assertSee('Bank Account')
            ->assertSee('BK01');
    }

    public function test_print_renders_a4_register(): void
    {
        $reconciliation = $this->makeReconciliation([
            'status' => Reconciliation::STATUS_RECONCILED,
        ]);
        $this->seedWorkspaceData($reconciliation);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.print'))
            ->assertOk()
            ->assertSee('Bank Reconciliation Register')
            ->assertSee('Statement Balance')
            ->assertSee('Difference')
            ->assertSee('BK01')
            ->assertSee('Reconciled');
    }

    public function test_cross_company_reconciliation_404s(): void
    {
        $otherCompany = Company::create([
            'company_code' => 'OTHER',
            'name' => 'Other Company',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
        ]);
        $otherBank = Account::create([
            'company_id' => $otherCompany->id,
            'code' => 'BK99',
            'name' => 'Other Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
            'is_bank_account' => true,
        ]);
        $other = Reconciliation::create([
            'company_id' => $otherCompany->id,
            'bank_account_id' => $otherBank->id,
            'statement_date' => '2026-08-01',
            'currency' => 'USD',
            'status' => Reconciliation::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)->get(route('accounting.bank-reconciliation.workspace', $other->id))
            ->assertNotFound();
    }
}
