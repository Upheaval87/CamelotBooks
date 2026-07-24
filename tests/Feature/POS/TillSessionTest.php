<?php

namespace Tests\Feature\POS;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\Company;
use App\Models\PosCashierSession;
use App\Models\PosPaymentMethod;
use App\Models\PosTerminal;
use App\Models\User;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\FeatureManagement;
use App\Services\POS\PosSetupService;
use App\Services\POS\TillSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TillSessionTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private PosTerminal $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Till Test Co',
            'company_code' => 'TTC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['role' => 'company_admin']);

        session(['current_company_id' => $this->company->id]);
        $this->actingAs($this->user);

        FeatureManagement::enable($this->company->id, 'pos');
        PosSetupService::seedDefaultsForCompany($this->company->id);

        $year = (int) now()->format('Y');
        AccountingPeriod::create([
            'company_id' => $this->company->id,
            'label' => now()->format('F Y'),
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'open',
        ]);

        $this->terminal = PosTerminal::create([
            'company_id' => $this->company->id,
            'name' => 'Front Counter',
            'identifier' => 'T1',
            'is_active' => true,
        ]);
    }

    // =============================================
    // OPEN TILL
    // =============================================

    public function test_open_till_creates_session(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $this->assertNotNull($session);
        $this->assertEquals(200.00, $session->opening_float);
        $this->assertEquals(PosCashierSession::STATUS_OPEN, $session->status);
        $this->assertEquals($this->terminal->id, $session->terminal_id);
        $this->assertEquals($this->user->id, $session->user_id);
    }

    public function test_open_till_via_controller(): void
    {
        $this->post(route('pos.till-sessions.open'), [
            'terminal_id' => $this->terminal->id,
            'opening_float' => 150.00,
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_cashier_sessions', [
            'company_id' => $this->company->id,
            'terminal_id' => $this->terminal->id,
            'opening_float' => 150.00,
            'status' => 'open',
        ]);
    }

    public function test_cannot_open_till_for_inactive_terminal(): void
    {
        $this->terminal->update(['is_active' => false]);

        $this->post(route('pos.till-sessions.open'), [
            'terminal_id' => $this->terminal->id,
            'opening_float' => 100.00,
        ])->assertSessionHasErrors('terminal_id');
    }

    public function test_cannot_open_two_tills_for_same_terminal(): void
    {
        app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $this->expectException(\LogicException::class);
        app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            100.00
        );
    }

    // =============================================
    // CLOSE TILL – NO VARIANCE
    // =============================================

    public function test_close_till_with_exact_cash(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $closed = app(TillSessionService::class)->closeTill($session, 200.00);

        $this->assertEquals(PosCashierSession::STATUS_CLOSED, $closed->status);
        $this->assertEquals(200.00, $closed->expected_cash);
        $this->assertEquals(200.00, $closed->actual_cash_count);
        $this->assertEquals(0.00, $closed->variance);
        $this->assertNotNull($closed->closed_at);
    }

    public function test_close_till_posts_balanced_journal_entry(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $closed = app(TillSessionService::class)->closeTill($session, 200.00);

        $this->assertNotNull($closed->journal_entry_id);

        $je = $closed->journalEntry;
        $this->assertEquals('posted', $je->status);
        $this->assertEquals($this->company->id, $je->company_id);
        $this->assertEquals('pos', $je->source_module);
        $this->assertStringContainsString('TILL-CLOSE', $je->reference);

        $lines = $je->lines;
        $this->assertCount(2, $lines);

        $debits = $lines->where('debit', '>', 0);
        $credits = $lines->where('credit', '>', 0);

        $this->assertEquals(200.00, $debits->first()->debit);
        $this->assertEquals(200.00, $credits->first()->credit);
    }

    // =============================================
    // CLOSE TILL – OVERAGE
    // =============================================

    public function test_close_till_with_overage(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $closed = app(TillSessionService::class)->closeTill($session, 205.00);

        $this->assertEquals(5.00, $closed->variance);
        $this->assertEquals(205.00, $closed->actual_cash_count);
    }

    public function test_close_till_overage_posts_three_line_je(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $closed = app(TillSessionService::class)->closeTill($session, 205.00);

        $lines = $closed->journalEntry->lines;
        $this->assertCount(3, $lines);

        $cashOverage = Account::where('company_id', $this->company->id)->where('code', '7400')->first();
        $overageLine = $lines->where('account_id', $cashOverage->id)->first();
        $this->assertNotNull($overageLine);
        $this->assertEquals(5.00, $overageLine->credit);
    }

    // =============================================
    // CLOSE TILL – SHORTAGE
    // =============================================

    public function test_close_till_with_shortage(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $closed = app(TillSessionService::class)->closeTill($session, 192.00);

        $this->assertEquals(-8.00, $closed->variance);
        $this->assertEquals(192.00, $closed->actual_cash_count);
    }

    public function test_close_till_shortage_posts_three_line_je(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $closed = app(TillSessionService::class)->closeTill($session, 192.00);

        $lines = $closed->journalEntry->lines;
        $this->assertCount(3, $lines);

        $cashShortage = Account::where('company_id', $this->company->id)->where('code', '6900')->first();
        $shortageLine = $lines->where('account_id', $cashShortage->id)->first();
        $this->assertNotNull($shortageLine);
        $this->assertEquals(8.00, $shortageLine->debit);
    }

    // =============================================
    // EDGE CASES
    // =============================================

    public function test_cannot_close_already_closed_session(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );
        app(TillSessionService::class)->closeTill($session, 200.00);

        $this->expectException(\LogicException::class);
        app(TillSessionService::class)->closeTill($session->fresh(), 200.00);
    }

    public function test_close_till_with_zero_float_posts_correctly(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            0.00
        );

        $closed = app(TillSessionService::class)->closeTill($session, 0.00);

        $this->assertEquals(0.00, $closed->expected_cash);
        $this->assertNull($closed->journal_entry_id);
    }

    // =============================================
    // CONTROLLER ROUTES
    // =============================================

    public function test_index_loads(): void
    {
        $this->get(route('pos.till-sessions.index'))->assertOk();
    }

    public function test_close_via_controller(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $this->post(route('pos.till-sessions.close', $session), [
            'actual_cash_count' => 210.00,
        ])->assertRedirect();

        $session->refresh();
        $this->assertEquals(PosCashierSession::STATUS_CLOSED, $session->status);
        $this->assertEquals(10.00, $session->variance);
    }

    public function test_show_loads(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );
        app(TillSessionService::class)->closeTill($session, 200.00);

        $this->get(route('pos.till-sessions.show', $session->fresh()))->assertOk();
    }

    // =============================================
    // ISOLATION
    // =============================================

    public function test_company_isolation(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $otherTerminal = PosTerminal::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Terminal',
            'identifier' => 'OT1',
            'is_active' => true,
        ]);

        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $this->assertNull(PosCashierSession::where('company_id', $otherCompany->id)->first());
    }

    public function test_terminal_in_different_company_cannot_close_our_session(): void
    {
        $session = app(TillSessionService::class)->openTill(
            $this->company->id,
            $this->terminal->id,
            $this->user->id,
            200.00
        );

        $otherCompany = Company::create([
            'name' => 'Other Co',
            'company_code' => 'OC',
            'base_currency' => 'USD',
            'fiscal_year_start_month' => 1,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create());
        session(['current_company_id' => $otherCompany->id]);

        $this->post(route('pos.till-sessions.close', $session), [
            'actual_cash_count' => 200.00,
        ])->assertForbidden();
    }
}
