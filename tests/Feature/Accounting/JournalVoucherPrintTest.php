<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\UserCompanyAssignment;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Print voucher: the standalone preview page and its real PDF download.
 *
 * Covers the two things that must never regress:
 *   1. the sheet is SERVER-rendered from the same payload as the PDF, so the
 *      preview cannot drift from the downloaded file;
 *   2. both routes are read-only and company-scoped — a forged journal id from
 *      another tenant 404s and can never print someone else's ledger.
 */
class JournalVoucherPrintTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private JournalEntry $entry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'company_code' => 'VOUCHCO',
            'name' => 'Voucher Co',
            'legal_name' => 'Voucher Co (Legal)',
            'base_currency' => 'USD',
            'tax_id' => 'TAX-9911',
            'phone' => '+1 555 0100',
            'address' => '12 Ledger Way',
            'city' => 'Harare',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
            'provisioning_status' => 'pending',
        ]);

        /* The permission guard on both routes is the Spatie permission, so the
         * acting user needs a role that carries journal-entries.view. */
        $this->seed(RolePermissionSeeder::class);
        setPermissionsTeamId($this->company->id);

        $this->user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        UserCompanyAssignment::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'role' => 'company_admin',
            'is_active' => true,
        ]);

        $this->user->assignRole('company_admin');

        Auth::login($this->user);
        $this->withSession(['current_company_id' => $this->company->id]);

/* An open period covering the entry date so show() resolves cleanly.
         * fiscal_year_id is nullable; the voucher does not read the fiscal year. */
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => now()->format('Y-m'),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        $cash = Account::create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Cash on Hand',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $sales = Account::create([
            'company_id' => $this->company->id,
            'code' => '4000',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'sub_type' => 'operating_revenue',
            'is_active' => true,
        ]);

        $this->entry = JournalEntry::create([
            'company_id' => $this->company->id,
            'journal_number' => 'JE-VOUCH-0001',
            'date' => now()->startOfMonth()->addDays(2)->toDateString(),
            'reference' => 'REF-77',
            'memo' => 'Opening funding',
            'status' => 'posted',
            'currency' => 'USD',
            'exchange_rate' => 1,
            'total_debit' => 1250.50,
            'total_credit' => 1250.50,
            'created_by' => $this->user->id,
            'posted_by' => $this->user->id,
            'posted_at' => now(),
        ]);

        $this->entry->lines()->create([
            'company_id' => $this->company->id,
            'account_id' => $cash->id,
            'memo' => 'Cash received',
            'debit' => 1250.50,
            'credit' => 0,
        ]);

        $this->entry->lines()->create([
            'company_id' => $this->company->id,
            'account_id' => $sales->id,
            'memo' => 'Revenue recognised',
            'debit' => 0,
            'credit' => 1250.50,
        ]);
    }

    public function test_routes_are_registered_with_the_expected_names(): void
    {
        $this->assertTrue(Route::has('accounting.journal-entries.print'));
        $this->assertTrue(Route::has('accounting.journal-entries.print-pdf'));

        $print = Route::getRoutes()->getByName('accounting.journal-entries.print');
        $pdf = Route::getRoutes()->getByName('accounting.journal-entries.print-pdf');

        $this->assertSame(['GET'], array_values(array_diff($print->methods(), ['HEAD'])));
        $this->assertSame(['GET'], array_values(array_diff($pdf->methods(), ['HEAD'])));

/* Both are read-only views. They carry the view permission and MUST NOT carry
         * the segregation-of-duties guard, which exists to stop a user approving
         * or reversing their own work. */
        foreach ([$print, $pdf] as $route) {
            $middleware = $route->gatherMiddleware();

            $this->assertContains('permission:journal-entries.view', $middleware);

            foreach ($middleware as $entry) {
                $this->assertStringStartsNotWith('sod:', $entry);
            }
        }
    }

    public function test_print_page_renders_the_full_voucher_from_the_server(): void
    {
        $response = $this->get(route('accounting.journal-entries.print', $this->entry));

        $response->assertOk();

        $html = $response->getContent();

/* Identity. The voucher meta grid is the 8 cells of spec §5.2 — reference is
         * deliberately NOT one of them (the sheet is not a document header), so it is
         * not asserted here. */
        $this->assertStringContainsString('JE-VOUCH-0001', $html);
        $this->assertStringContainsString('Voucher Co (Legal)', $html);
        $this->assertStringContainsString('Tax ID: TAX-9911', $html);

        /* Lines come from the ledger, not a summary. */
        $this->assertStringContainsString('1000', $html);
        $this->assertStringContainsString('Cash on Hand', $html);
        $this->assertStringContainsString('Cash received', $html);
        $this->assertStringContainsString('Sales Revenue', $html);

        /* Totals + amount in words are server-rendered (R7). */
        $this->assertStringContainsString('1,250.50', $html);
        $this->assertStringContainsString('1,250', $html);

        /* Both affordances exist, and the PDF link is a real route. */
        $this->assertStringContainsString(route('accounting.journal-entries.print-pdf', $this->entry), $html);
        $this->assertStringContainsString('Download PDF', $html);
        $this->assertStringContainsString('jv-print', $html);
        $this->assertStringContainsString('window.print()', $html);

        /* The old in-page overlay must be gone from both pages. */
        $this->assertStringNotContainsString('data-glj-print-now', $html);
        $this->assertStringNotContainsString('id="glj-print"', $html);

        /* Print-specific styling is a dedicated stylesheet, not the app bundle. */
        $this->assertStringContainsString('journal-voucher', $html);
    }

    public function test_detail_toolbar_links_to_the_new_tab_print_page(): void
    {
        $response = $this->get(route('accounting.journal-entries.show', $this->entry));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener"', $html);
        $this->assertStringContainsString(route('accounting.journal-entries.print', $this->entry), $html);
        $this->assertStringContainsString('Print Voucher', $html);

        /* The overlay button (and its legacy data hook) must be gone. */
        $this->assertStringNotContainsString('data-glj-print"', $html);
        $this->assertStringNotContainsString('glj-print-voucher" data-glj-print', $html);
        $this->assertStringNotContainsString('id="glj-sheet"', $html);
    }

    public function test_pdf_download_returns_a_pdf_attachment(): void
    {
        $response = $this->get(route('accounting.journal-entries.print-pdf', $this->entry));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition'),
        );
        $this->assertStringContainsString(
            'JE-VOUCH-0001-journal-voucher.pdf',
            (string) $response->headers->get('content-disposition'),
        );

        $body = $response->getContent();

        /* A real PDF, not an HTML page or a browser print-to-PDF stub. */
        $this->assertStringStartsWith('%PDF-', $body);
        $this->assertStringContainsString('%%EOF', $body);
    }

    public function test_print_and_pdf_routes_are_read_only(): void
    {
        $before = $this->entry->fresh();

        $this->get(route('accounting.journal-entries.print', $this->entry))->assertOk();
        $this->get(route('accounting.journal-entries.print-pdf', $this->entry))->assertOk();

        $after = $this->entry->fresh();

        $this->assertSame($before->status, $after->status);
        $this->assertSame((float) $before->total_debit, (float) $after->total_debit);
        $this->assertEqualsCanonicalizing(
            $before->lines->pluck('debit')->map(fn ($v) => (float) $v)->all(),
            $after->lines->pluck('debit')->map(fn ($v) => (float) $v)->all(),
        );

        /* No reversal entry, no balance movement. */
        $this->assertSame(0, DB::table('journal_entries')->where('reversal_entry_id', $this->entry->id)->count());
        $this->assertSame(0, JournalEntry::query()->where('status', 'reversed')->count());
    }

    public function test_forged_journal_from_another_company_cannot_be_printed(): void
    {
        $other = Company::create([
            'company_code' => 'OTHERCO',
            'name' => 'Other Co',
            'base_currency' => 'USD',
            'is_active' => true,
            'provisioning_status' => 'pending',
        ]);

        $account = Account::create([
            'company_id' => $other->id,
            'code' => '9999',
            'name' => 'Other Cash',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'is_active' => true,
        ]);

        $foreign = JournalEntry::create([
            'company_id' => $other->id,
            'journal_number' => 'JE-FOREIGN-0001',
            'date' => now()->startOfMonth()->addDays(2)->toDateString(),
            'status' => 'posted',
            'total_debit' => 500,
            'total_credit' => 500,
            'created_by' => $this->user->id,
        ]);

        $foreign->lines()->create([
            'company_id' => $other->id,
            'account_id' => $account->id,
            'memo' => 'Secret movement',
            'debit' => 500,
            'credit' => 0,
        ]);

        $this->get(route('accounting.journal-entries.print', $foreign))->assertNotFound();
        $this->get(route('accounting.journal-entries.print-pdf', $foreign))->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        Auth::logout();

        $this->get(route('accounting.journal-entries.print', $this->entry))->assertRedirect(route('login'));
        $this->get(route('accounting.journal-entries.print-pdf', $this->entry))->assertRedirect(route('login'));
    }
}