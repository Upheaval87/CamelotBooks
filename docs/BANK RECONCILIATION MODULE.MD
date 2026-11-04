# FULL IMPLEMENTATION PROMPT — COMPREHENSIVE BANK RECONCILIATION MODULE

## CRITICAL INSTRUCTION TO OPENCODE: DO NOT SKIP ANY ELEMENT, DO NOT SIMPLIFY, DO NOT REPLACE, DO NOT SUMMARIZE, DO NOT LEAVE OUT ANY PAGE, TAB, WIZARD STEP, MODAL, SLIDE-OVER, TABLE, FIELD, BUTTON, FILTER, BADGE, TOOLTIP, ANIMATION, INTERACTION, VALIDATION, OR BACKEND FUNCTIONALITY IMPLIED BY THESE MOCKUPS.

You are acting as a senior Laravel/PHP enterprise architect, backend engineer, database designer, and premium UI/UX frontend developer.

Build a **comprehensive enterprise Bank Reconciliation Module** inside the existing PHP/Laravel ERP/accounting system, exactly matching the two authoritative HTML mockups embedded at the end of this prompt:

1. **`bank-reconciliation.html`** — Reconciliation Index/Register + Matching Workspace + 5-step Import Statement wizard (upload → worksheet picker → column mapping → preview → import).
2. **`outstanding-items.html`** — Reconciliation Control Centre: six working tabs (Reconciliations, Bank Statements, Adjustments, Outstanding Items, Reports, Audit Trail), the extra-wide Transaction Matcher modal, the wide Quick-Add Journal modal, report preview modals with CSV export, adjustment modal, and the four slide-over workflows (Carry Forward, Investigate/Assign, Void/Reverse, Bank Error).

These mockups are the **source of truth** for layout, visual design, spacing, typography, color tokens, gradients, glass surfaces, shadows, cards, tables, pills, badges, steppers, modals, slide-overs, form fields, parsing/mapping behavior, matching behavior, validation states, animations, and micro-interactions.

You must redesign/implement **all necessary pages, views, tabs, wizard steps, modals, slide-overs, components, elements, workflows, validations, backend endpoints, database structures, policies, services, controllers, Blade views, JavaScript/Alpine behavior, notifications, jobs, tests, and seeders** required to make this module production-ready within the existing system.

---

## ABSOLUTE NON-NEGOTIABLE REQUIREMENTS

1. **Do not skip any element from the mockups.**
2. **Do not remove any tab** (Reconciliations, Bank Statements, Adjustments, Outstanding Items, Reports, Audit Trail) — each must be a real, data-driven pane.
3. **Do not remove any wizard step** (Statement, Import, Matching, Review, Complete) or the stepper's done/active/locked visuals and sequential unlocking.
4. **Do not remove any field** in any form, mapper, modal, or slide-over.
5. **Do not remove any button, chip, pill, badge, menu item, or icon** (including the More menu's three items, the history toggle, Expand/Collapse fullscreen buttons on the Outstanding register, Transaction Matcher card, and Import wizard card).
6. **Do not remove any animation** (fadeUp stagger, sheen on highlight/difference KPIs, popIn, panelIn, slide-over translate, toast spring, row entrance, menu open transitions).
7. **Do not replace the custom design system** with generic Bootstrap/Tailwind/Material UI.
8. **Do not simplify the import pipeline**: worksheet chips with row counts, header-row/skip-rows/date-format controls, column mapping with auto-detection badges and per-field sample values, mutual-exclusion rules (Amount vs Debit/Credit), 8-row sticky-header preview with right-aligned tabular numerals, import statistics chips, and the footer readiness chip gating the Import button are ALL mandatory.
9. **Do not simplify the matcher**: two panels with independent search, click-to-select + Match Selected bar, hover unmatch, auto-match confidence engine (amount ±0.01, Δdate ≤ 10d, confidence = 96 − 3·days, 99 with related references, auto-accept ≥ 85), suggestion cards with Accept/Dismiss below 85.
10. **Do not simplify Outstanding Items**: ageing policy badges (red stale >180d, orange aged >90d, amber in-transit >3d / unrecorded >30d) with tooltips and row urgency tinting, Required Action column, context-aware row menus (bank-only vs book-only), bulk Carry Forward / Assign Investigation, and all five dialogs with their gating rules.
11. **Do not omit the Quick-Add Journal suggestion engine** (Dr Bank Charges/Cr Bank for fees, Dr Bank/Cr Interest or AR for deposits, reversal pairs for book-only) nor its balanced-only save gate (±0.01, all accounts chosen, memo + date required), nor its add/remove line repeater (min 2 lines).
12. **Do not omit the Void/Reverse automatic reversing journal**, the Bank Error dispute queue, or the Investigate assignee notification with due date.
13. **Do not omit the reconciliation calculation** (book, statement, book adjustments, statement adjustments, adjusted book, adjusted bank, difference total row, in/out-of-balance banner) nor the balance summary.
14. **Do not omit the status lifecycle** Draft → In Progress → Ready for Review → Reconciled, the lock banner, the finalize confirm modal, and rejection of mutations on locked periods.
15. **Do not omit the register KPI strip** (6 tiles incl. orange-gradient Difference with sheen) nor the register table columns, Continue flow, and history toggle.
16. **Do not omit the audit trail**: every mutating action (create period, parse/import, auto-match, manual match, unmatch, adjustment add/remove, quick-JE post, carry forward, void, bank error, investigate, mark ready, finalize) writes an immutable event.
17. **Do not move or restyle the faint centred BOTTOM gradient hairline** on cards.
18. **Do not regress the fixed behaviours**: row action menus must paint above all rows and escape table/card clipping while open; Expand must reparent the card to `<body>` so fullscreen never disappears; closed slide-overs must not leak shadows at the viewport right; wide tooltips on card-head controls must open below, left-aligned, in front of card content.
19. **Currency is MWK with symbol "K"** everywhere; mono tabular numerals; negatives red; differences orange.
20. **Inspect the existing codebase first** and match its directory structure, naming conventions, base controllers, model traits (TenantScoped/SoftDeletes), middleware, existing journal-entry/account models, and asset pipeline. Integrate — do not fork a parallel architecture.
21. **If the implementation is too large for one response, split it into multiple file writes, but still complete every required element.**
22. **No placeholders** like "implement similarly", "add remaining fields", or "TODO".
23. **Every visible mockup element must map to a real artifact**: Blade view/partial, Alpine component, controller method, route, form-request rule, service method, migration column, policy ability, notification, test case, or seeder record.

---

## TARGET STACK

- Laravel 11+ (match existing), PHP 8.3+ (match existing)
- Blade + Alpine.js (or Livewire if already the project standard — preserve identical visuals/behavior)
- Custom CSS token system from the mockups compiled through the existing pipeline (Vite/Mix)
- Client-side workbook preview may use SheetJS, but **server-side parsing is authoritative** (OpenSpout/PhpSpreadsheet for XLSX/XLS/CSV) with pluggable adapter interfaces reserved for OFX/QFX/MT940/CAMT.053 (chips shown as "soon")
- MySQL/PostgreSQL (match existing), Form Requests, Policies, Services, Feature tests, Seeders/Factories, multi-tenant scoping if present, queue for heavy parses/postings

---

## MODULE SCOPE — PAGE BY PAGE

### A. Reconciliation Index / Register (`bank-reconciliation.html`)

- Centred-container chrome (topnav row 1, module rail with Banking active) — **no icon sidebar** for this module.
- Page header: title, subtitle, actions: **Import Statement**, **Refresh**, **More** dropdown (Export register CSV, Jump to Audit Trail, Show/Hide finalized periods), **New Reconciliation**.
- Six tab pills with live counts; each pane real:
  - **Reconciliations**: filter bar (Bank Account select, Period from→to, Filter, Clear, periods chip), 6-KPI strip aggregated over filtered periods, Reconciliation Register table (Bank Account, Statement, Period End, Opening, Closing, Book, Statement Bal., orange Difference, Status pill, Continue →) with row-click continue and "Reconciliation history →" toggle.
  - **Bank Statements**: import log (file, period, lines, imported at, by, status) + Import button → wizard.
  - **Adjustments**: cross-period adjustments table + New Adjustment modal.
  - **Outstanding Items**: FULL workflow per Mockup 2 (this pane hosts the control-centre card).
  - **Reports**: four runnable report cards → preview modal + Export CSV.
  - **Audit Trail**: immutable event feed.
- Continue logic: draft period with no lines → wizard; otherwise workspace.

### B. Import Statement Wizard (5 steps)

- Stepper with done/active/locked states, sequential unlocking, locked toast.
- **Step 1 Statement**: bank account, currency (MWK fixed), period start/end, opening balance, closing balance (auto-fillable from mapping); changes persist to the period.
- **Step 2 Import**: format chips (XLSX/CSV active; OFX/QFX/MT940/CAMT.053 "soon" with tooltips), drag-drop + browse with type/size validation, parsed-file chip with remove, **"Load sample statement"** fallback, then: (1) worksheet chips with row counts; (2) header row, skip rows, date format (auto DD/MM-first, DMY, MDY, YMD, Excel serial); (3) mapping grid for Date*, Reference, Description, Debit (Withdrawal), Credit (Deposit), Amount (signed alternative), Balance with auto badges, sample values, mutual exclusion; (4) 8-row preview, right-aligned numerics, negatives red / credits green; (5) statistics chips (lines, withdrawals, deposits, range, closing); (6) footer readiness chip ("Ready to import — N lines" / "Missing: …") gating Import.
- **Step 3 Matching**: embedded matcher + suggestions + Auto Match.
- **Step 4 Review**: calculation card, balance summary, adjustments manager (type/sign/side/amount/account/description + list + remove).
- **Step 5 Complete**: finalize checklist (difference state, matched pairs, carried items, adjustments to post) + Finalize & Lock (confirm modal → posts adjustment JEs, locks period, audit event, returns to register).

### C. Matching Workspace

- Header: status pill, account/period/currency subtitle, actions: Register, Import Statement, Auto Match, Mark Ready, Review & Finalize; lock banner when reconciled.
- 4-KPI strip (Statement Lines, Matched Pairs, Unmatched, Difference card flipping teal/amber with sheen).
- Matcher card (with Expand/Collapse): selection bar (Match Selected / Clear), Book Transactions panel (ledger count, search, empty state) and Statement Lines panel (imported count, search, empty state); rows show date, stacked description+reference, signed mono amount, status pill (Matched/Bank-only/Book-only), hover unmatch.
- Right column: Balance Summary, Suggestions (confidence circle, pair recap, Accept/Dismiss), Imports list. Left column: Reconciliation Calculation, Adjustments card + form.

### D. Outstanding Items Control Centre (`outstanding-items.html`)

- Page header: **Go to Matcher** (opens matcher modal), Refresh.
- Tab pills (six) with counts; Outstanding pane default.
- Register card (with Expand/Collapse + "?" icon carrying the ageing-policy wide tooltip): seg filters All/Bank-only/Book-only/Urgent with counts, search, visible chip, bulk bar (Carry Forward Selected, Assign Investigation, Clear), table (select, Ageing badge+tooltip, Kind pill, Period, Date, Reference, Description, Amount, Required Action, context-aware ⋮ menu), urgency row tints, empty state.
- Row menus: bank-only → Record in Books (Quick JE), Manual Match, Mark as Bank Error; book-only → Carry Forward, Investigate/Assign, Void/Reverse (debits), plus Add Investigation Note everywhere.
- **Matcher modal (xwide)**: book-only vs bank-only pools with searches, selection bar showing both amounts + Δ, Match enabled only when |Δ| ≤ 0.01, matched-pairs session counter, audit event on match.
- **Quick Journal modal (jwide)**: source recap, suggestion banner, date/source/memo, JE line editor (account select, mutually exclusive debit/credit, memo, add/remove ≥2), live balanced indicator gating "Post Journal & Clear Item"; posts JE (source_type reconciliation_outstanding), resolves and removes the line, audit event.
- **Carry Forward slide-over**: target period select (next open auto), reason note; bulk-capable.
- **Investigate/Assign slide-over** (amber header): assignee, due ≥ today, instruction; notifies assignee; bulk-capable.
- **Void/Reverse slide-over** (red header): warning banner, source recap, mandatory reason; posts automatic reversing JE; audit event.
- **Bank Error slide-over** (red header): mandatory dispute note; excludes line from books; queues for Bank Error Dispute Report.
- Reports pane cards open preview modals (Summary, Unmatched, Adjustment Ledger, Audit) with real CSV export.
- Adjustments pane: ledger table + New Adjustment modal (period, type, side, sign, amount > 0, account, description) writing to ledger + audit.
- Audit pane: event feed appended live by every mutation.

### E. Cross-cutting behaviours

- Fullscreen Expand/Collapse reparents the card to `<body>` (anchor comment restore on collapse; Escape exits; body scroll lock).
- Row menus: open-wrap z-boost, 10px offset below trigger, table-wrap + card overflow released while open, row entrance animation must not retain transforms (fill-mode backwards / `transform:none` end keyframes).
- Tooltips: default above; `tip-below` for card-head controls; `tip-wide tip-start` multi-line left-aligned for the ageing-policy "?" icon.
- Closed slide-overs `visibility:hidden` (no right-edge shadow leak).
- Toast feedback (ok/warn/err) for every action.
- Permissions: `banking.reconciliations.*` (view, create, import, match, adjust, resolve-outstanding, finalize); finalized periods reject mutations server-side.
- Ageing thresholds (3/30/90/180 days) configurable via `config('banking.reconciliation.ageing')`.

---

## DESIGN SYSTEM REQUIREMENTS

Preserve exactly:

```css
--deep-1:#17565D; --deep-2:#0C3539; --deep-3:#0A2E32;
--row1:#11454B; --row2:#DCE6E7;
--sec:#107C7B; --sec-2:#0C7E7D;
--ink:#0B2A2D; --ink-soft:#52696B; --ink-muted:#5F7A7C;
--mist-a:#F4F8F8; --mist-b:#E8F0F0; --line:#E2ECEC; --border:#DCEAEA;
--card:rgba(255,255,255,.75);
--green:#15803D; --warn:#D97706; --red:#DC2626; --red-2:#B91C1C;
--tint:#DFF7F6; --mint:#7FD1C0;
--blue:#1D6FB8; --purple:#7C3AED; --orange:#EA580C;
--teal-btn-shadow / --cta-btn-shadow (as in mockups)
```

Required signatures: deep-teal gradient topnav with mint radial glow; module rail active deep-gradient pill; centred max-width container (no icon rail); glass cards; **faint centred BOTTOM hairline** on every card; orange-gradient Difference KPI with sheen; teal-gradient highlight KPIs; stepper done/active/locked; sheet chips; mapping rows with teal mapped state + auto badges; sticky-header preview table with right-aligned tabular numerals; two-panel matcher with inset selection bar; slide-over panels with teal/amber/red gradient headers; ageing badges; row urgency tints; modal headers with mint bottom hairline; toast stack bottom-right.

---

## BACKEND IMPLEMENTATION REQUIREMENTS

### Migrations / tables (adapt names to existing conventions)

- `reconciliations`: tenant/company, bank_account_id, statement_name, period_start, period_end, currency_id default MWK, opening_balance, closing_balance, book_balance, statement_balance_override nullable, status enum(draft,in_progress,ready_for_review,reconciled), finalized_by/at nullable, locked boolean, created_by, timestamps, softDeletes.
- `reconciliation_lines`: reconciliation_id, side enum(book,statement), line_date, reference, description, debit, credit, amount signed, running_balance nullable, status enum(unmatched,matched,carried,voided,disputed), match_id nullable, **outstanding_type enum(bank_only,book_only) nullable, action_taken enum(none,recorded_in_books,carried_forward,voided_stale,bank_error_disputed,investigating,manual_match) default none, assigned_to nullable FK users, investigate_due_date nullable, investigation_notes text nullable, resolved_by/resolved_at nullable, journal_entry_id nullable FK, carried_to_period_id nullable FK reconciliations, source_import_id nullable FK**, timestamps. Indexes on (reconciliation_id, side, status) and (outstanding_type, action_taken).
- `reconciliation_matches`: reconciliation_id, book_line_id, statement_line_id, method enum(auto,manual,suggested), confidence tinyint, created_by, timestamps.
- `reconciliation_adjustments`: reconciliation_id, type string, sign tinyint(±1), side enum(book,statement), amount decimal(18,2), account_id nullable, description nullable, journal_entry_id nullable (posted on finalize), created_by, timestamps.
- `bank_statement_imports`: reconciliation_id, file_name, original_mime, storage_path, sheet_name, header_row, skip_rows, date_format, column_map json, lines_detected, lines_imported, totals json nullable, status enum(parsed,imported,failed), error_message nullable, imported_by, timestamps.
- `bank_statement_column_maps` (reusable per bank/account): bank_account_id nullable, label, column_map json, created_by, timestamps — wizard offers "reuse last mapping".
- `reconciliation_audit_events`: reconciliation_id nullable, actor_id, event string, payload json nullable, timestamps.
- Reuse existing `journal_entries` / `journal_entry_lines` with source_type values `reconciliation_adjustment`, `reconciliation_outstanding`, `reconciliation_outstanding_void`.

### Enums (PHP 8.1+)

`ReconciliationStatus`, `LineSide`, `LineStatus`, `OutstandingType`, `OutstandingAction`, `MatchMethod`, `DateFormatMode`.

### Models

`Reconciliation` (hasMany lines/matches/adjustments/imports/audit; scopes open(), finalized(); accessors difference), `ReconciliationLine` (scopes outstanding(), bankOnly(), bookOnly(), unmatched(); accessors age_days; methods agingBadge(), requiredAction(), availableActions(); relations reconciliation, journalEntry, assignee, carriedToPeriod), `ReconciliationMatch`, `ReconciliationAdjustment`, `BankStatementImport` (cast column_map array), `BankStatementColumnMap`, `ReconciliationAuditEvent`.

### Services

- `BankStatementImportService`: validate (type/size), parse server-side, list sheets, extract by header/skip, normalize dates per mode (serial/ymd/dmy/mdy/auto-DD-MM-first), normalize amounts (commas, parentheses negatives, DR/CR suffixes, symbols), apply column map, preview + statistics, persist import + lines (side=statement, outstanding_type=bank_only), auto-fill closing balance, audit event.
- `ColumnMappingGuesser`: keyword scoring identical to mockup GUESS map; returns auto badges.
- `ReconciliationMatchingService`: candidates(), autoMatch() (≥85), suggestions() (<85, dismissible), matchManual(), unmatch(), state recalculation in DB transaction.
- `ReconciliationCalculationService`: adjusted book/bank, difference, summary, in-balance check.
- `OutstandingItemService`: suggestJournalLines(), recordInBooks(), voidStale() (auto reversing JE), carryForward(), markBankError(), investigate() (+ notification), bulkCarry(), bulkInvestigate().
- `ReconciliationFinalizeService`: guard locked, post pending adjustment JEs, set reconciled + locked, carry unmatched metadata, audit event.
- `ReconciliationRegisterService`: filtered register rows, aggregate KPIs, streamed CSV export.
- `BankReconciliationReportService`: summary / unmatched / adjustment ledger / audit datasets + CSV.
- `BankErrorDisputeReportService`: dispute report grouped by bank + period (PDF/CSV).

### Controllers & Routes (namespace `App\Http\Controllers\Banking`)

```php
Route::middleware(['auth','tenant'])->prefix('banking/reconciliations')->name('banking.reconciliations.')->group(function () {
    Route::get('/', [ReconciliationController::class,'index'])->name('index');
    Route::get('/export', [ReconciliationController::class,'exportRegister'])->name('export');
    Route::post('/', [ReconciliationController::class,'store'])->name('store');
    Route::get('/{reconciliation}/workspace', [ReconciliationController::class,'workspace'])->name('workspace');
    Route::post('/{reconciliation}/ready', [ReconciliationController::class,'markReady'])->name('ready');
    Route::post('/{reconciliation}/finalize', [ReconciliationController::class,'finalize'])->name('finalize');

    Route::get('/{reconciliation}/import', [BankStatementImportController::class,'create'])->name('import.create');
    Route::post('/{reconciliation}/import/parse', [BankStatementImportController::class,'parse'])->name('import.parse');
    Route::post('/{reconciliation}/import', [BankStatementImportController::class,'store'])->name('import.store');

    Route::post('/{reconciliation}/match/auto', [MatchingController::class,'autoMatch'])->name('match.auto');
    Route::post('/{reconciliation}/match', [MatchingController::class,'match'])->name('match.store');
    Route::delete('/match/{match}', [MatchingController::class,'unmatch'])->name('match.destroy');
    Route::get('/{reconciliation}/suggestions', [MatchingController::class,'suggestions'])->name('suggestions');
    Route::post('/suggestions/{line}/dismiss', [MatchingController::class,'dismiss'])->name('suggestions.dismiss');

    Route::post('/{reconciliation}/adjustments', [AdjustmentController::class,'store'])->name('adjustments.store');
    Route::delete('/adjustments/{adjustment}', [AdjustmentController::class,'destroy'])->name('adjustments.destroy');

    Route::get('/outstanding', [OutstandingItemController::class,'index'])->name('outstanding.index');
    Route::get('/outstanding/{line}/suggest', [OutstandingItemController::class,'suggest'])->name('outstanding.suggest');
    Route::post('/outstanding/{line}/record-in-books', [OutstandingItemController::class,'recordInBooks'])->name('outstanding.record');
    Route::post('/outstanding/{line}/carry-forward', [OutstandingItemController::class,'carryForward'])->name('outstanding.carry');
    Route::post('/outstanding/{line}/void', [OutstandingItemController::class,'voidStale'])->name('outstanding.void');
    Route::post('/outstanding/{line}/bank-error', [OutstandingItemController::class,'bankError'])->name('outstanding.bank-error');
    Route::post('/outstanding/{line}/investigate', [OutstandingItemController::class,'investigate'])->name('outstanding.investigate');
    Route::post('/outstanding/bulk/carry', [OutstandingItemController::class,'bulkCarry'])->name('outstanding.bulk-carry');
    Route::post('/outstanding/bulk/investigate', [OutstandingItemController::class,'bulkInvestigate'])->name('outstanding.bulk-investigate');

    Route::get('/reports/{report}', [ReportController::class,'show'])->name('reports.show');
    Route::get('/reports/{report}/export', [ReportController::class,'export'])->name('reports.export');
    Route::get('/audit', [ReconciliationController::class,'audit'])->name('audit');
});
```

### Form Requests

`StoreReconciliationRequest`, `ParseStatementRequest` (file mimes csv,xlsx,xls; max 10MB), `ImportStatementRequest` (sheet string required, header_row int ≥1, skip_rows int ≥0, date_format in modes, column_map array requiring date + (debit&credit|amount)), `StoreMatchRequest`, `StoreAdjustmentRequest` (amount > 0, side in book/statement, sign ±1), `RecordInBooksRequest` (date, memo, lines array min 2; each line account exists + exactly one side; **whole journal balanced ±0.01** in `withValidator`), `OutstandingActionRequest` (note/target/assignee/due rules), `BulkOutstandingRequest`.

### Policy

`ReconciliationPolicy`: viewAny, view, create, update, import, match, adjust, resolveOutstanding, finalize — tenant-scoped; `finalize` requires explicit permission and rejects locked periods.

### Notifications / Jobs

`InvestigateOutstandingItem` (mail + database); `ParseBankStatementJob` for files > 2MB; `PostFinalizationJournalsJob` where ledger volume warrants.

### Seeders & tests

- Seeder reproduces mock register: three periods for account 1000 (`asdasdas` Nov 02 2026 opening 622,222.00 closing 555,551.00; `were` Aug 13 2026 opening 900,000.00 closing 6,540,000.00 with the 21 Jul statement lines + 8 Aug book lines + 2 imports; `15542` Jul 31 2026 opening 1,500,000.00 closing 8,965,420.00), book balance 4,000.00 each, statuses draft/in-progress/draft; plus the outstanding item set (stale cheque >180d, aged cheque >90d, deposit in transit >3d, recent cheque, bank charges/interest/duplicate deposit unrecorded) and the two seed adjustments/audit events.
- Feature tests: register KPI aggregation & CSV; wizard parse→map→import (column map persisted, preview stats, readiness rules); auto-match confidence boundaries (84 vs 85); manual match/unmatch; matcher modal amount-equality gate; adjustment CRUD affects calculation; outstanding suggest matrix (fee/interest/deposit/stale); record-in-books posts balanced JE and clears line; unbalanced JE rejected; void posts exact reversal; carry-forward targets next open period; bank-error exclusion + dispute report queue; investigate notification dispatch; finalize locks period and rejects further mutations; policy denial across tenants; fullscreen/tooltip behaviours covered by browser tests where the stack provides them.

---

## FRONTEND IMPLEMENTATION REQUIREMENTS

Blade structure (adapt to existing layout):

```text
resources/views/banking/reconciliations/index.blade.php            (register + 6 tab panes)
resources/views/banking/reconciliations/workspace.blade.php         (matcher + calc/summary/suggestions/adjustments/imports)
resources/views/banking/reconciliations/import-wizard.blade.php     (5-step wizard)
resources/views/banking/reconciliations/partials/register-table.blade.php
resources/views/banking/reconciliations/partials/kpi-strips.blade.php
resources/views/banking/reconciliations/partials/matcher.blade.php
resources/views/banking/reconciliations/partials/calc-summary.blade.php
resources/views/banking/reconciliations/partials/adjustments-manager.blade.php
resources/views/banking/reconciliations/partials/outstanding-register.blade.php
resources/views/banking/reconciliations/modals/matcher.blade.php            (xwide)
resources/views/banking/reconciliations/modals/quick-journal.blade.php      (jwide)
resources/views/banking/reconciliations/modals/report-preview.blade.php
resources/views/banking/reconciliations/modals/new-adjustment.blade.php
resources/views/banking/reconciliations/modals/finalize-confirm.blade.php
resources/views/banking/reconciliations/slideovers/carry-forward.blade.php
resources/views/banking/reconciliations/slideovers/investigate.blade.php
resources/views/banking/reconciliations/slideovers/void-reverse.blade.php
resources/views/banking/reconciliations/slideovers/bank-error.blade.php
resources/css/bank-reconciliation.css
resources/js/components/reconciliation-register.js
resources/js/components/import-wizard.js
resources/js/components/matcher.js
resources/js/components/outstanding-items.js
resources/js/components/fullscreen-card.js
```

Alpine/JS components must reproduce: register filtering + KPI recomputation + history toggle + CSV export; wizard state machine with step locking, parse endpoint (or SheetJS preview) driving sheet chips/mapping/preview/stats and the readiness chip; matcher selection/auto-match/unmatch/suggestions; adjustments forms (workspace + wizard + modal); outstanding filters/bulk/menus/slide-overs with JE balance gating; report previews + CSV; audit feed; toasts; Escape/backdrop closing; body scroll lock; fullscreen reparenting.

Server-rendered equivalents must produce identical markup so core navigation works without JS.

---

## CURRENCY RULE

All monetary values render as MWK with symbol **K** (e.g. `K 16,048,971.00`), mono tabular numerals, right-aligned in numeric columns, negatives red, differences orange. Never default to USD/$.

---

## ACCEPTANCE CHECKLIST

### Index / Register
- [ ] Centred chrome; Banking active; no icon sidebar.
- [ ] Header actions: Import Statement, Refresh, More menu (3 items), New Reconciliation.
- [ ] Six tab pills with live counts; every pane renders real data.
- [ ] Filter bar drives KPIs + register; history toggle includes finalized.
- [ ] KPI strip: 6 tiles; Matched green; Unmatched orange; Difference orange-gradient + sheen.
- [ ] Register: all 9 columns + Continue; row click continues; draft-without-lines routes to wizard.
- [ ] Statements pane lists every import; Adjustments pane lists ledger + modal writes; Reports pane previews + exports; Audit pane feeds live.

### Wizard
- [ ] Stepper visuals + locking + sequential unlock + locked toast.
- [ ] Step 1 persists account/period/balances; closing auto-fills from mapping when empty.
- [ ] Step 2: dropzone drag states, type/size errors, parsed chip with remove, sample loader, format chips with tooltips.
- [ ] Worksheet chips with counts; switching re-maps; header/skip/date-format re-parse live.
- [ ] Mapping grid: 7 fields, auto badges, samples, mutual exclusion; Date required.
- [ ] Preview: 8 rows, right-aligned numerics, red negatives/green credits, sticky header.
- [ ] Stats chips: lines, withdrawals, deposits, range, closing.
- [ ] Readiness chip states + Import gating; import persists bank-only outstanding lines, sets statement name, status in-progress, audit event, advances to step 3.
- [ ] Steps 3–5 embed matcher, calculation+adjustments, finalize checklist; finalize posts JEs, locks, returns to register.

### Workspace
- [ ] Status pill + subtitle + lock banner states.
- [ ] 4 KPI tiles with difference colour flip.
- [ ] Matcher: panels, searches, empty states, selection bar, match/unmatch, pills, stacked desc/ref, hover behaviours; Expand/Collapse works.
- [ ] Auto Match toasts; suggestions accept/dismiss; confidence circles.
- [ ] Calculation rows + total + banner; summary rows; adjustments sync workspace/wizard; imports list.

### Outstanding Items
- [ ] Seg filters + counts + urgent definition; search; visible chip; bulk bar show/hide; bulk carry & investigate.
- [ ] Table: ageing badges with tooltips + row tints; kind pills; period; mono amounts; Required Action per policy; empty state.
- [ ] Row menus context-aware; open menu paints above all rows and escapes clipping; outside-click closes.
- [ ] Matcher modal xwide: pools, searches, selection bar with Δ, gate at 0.01, session counter, audit on match.
- [ ] Quick Journal modal jwide: recap, suggestion banner, prefilled memo/date, suggested lines per type, add/remove lines, debit↔credit exclusivity, balance indicator states, save gate, post → JE + line cleared + toast + audit; **no horizontal scroll on JE rows**.
- [ ] Carry Forward (target select + note + bulk), Investigate (assignee/due/note + notification + bulk), Void (red header, warning, recap, mandatory reason, reversing JE), Bank Error (red header, mandatory note, dispute queue).
- [ ] Expand/Collapse on register card reparents to body; Escape exits; "?" icon shows wide below-left tooltip in front of card; no subtitle under register title.
- [ ] Closed slide-overs leak no shadow at viewport right.

### Global
- [ ] Faint centred BOTTOM hairlines on all cards.
- [ ] All animations present and performant; no retained transforms breaking fixed/absolute descendants.
- [ ] MWK "K" everywhere; mono tabular figures; red negatives; orange differences.
- [ ] Policies enforced server-side; locked periods reject writes.
- [ ] Audit event written for every mutation listed above.
- [ ] Tests green; seeders reproduce mock datasets exactly.

---

## AUTHORITATIVE HTML MOCKUPS — IMPLEMENT EXACTLY

### MOCKUP 1 — `bank-reconciliation.html` (Index + Workspace + Import Wizard)

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bank Reconciliation | CamelotBooks ERP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
<style>
  :root{
    --deep-1:#17565D; --deep-2:#0C3539; --deep-3:#0A2E32;
    --row1:#11454B; --row2:#DCE6E7;
    --sec:#107C7B; --sec-2:#0C7E7D;
    --ink:#0B2A2D; --ink-soft:#52696B; --ink-muted:#5F7A7C;
    --mist-a:#F4F8F8; --mist-b:#E8F0F0; --line:#E2ECEC; --border:#DCEAEA;
    --card:rgba(255,255,255,.75);
    --shadow-card:0 1px 2px rgba(10,42,46,.05),0 12px 32px -8px rgba(10,42,46,.10),0 2px 6px rgba(10,42,46,.04);
    --shadow-card-hov:0 2px 4px rgba(10,42,46,.06),0 20px 46px -12px rgba(10,42,46,.18),0 4px 10px rgba(10,42,46,.06);
    --green:#15803D; --warn:#D97706; --red:#DC2626; --red-2:#B91C1C;
    --tint:#DFF7F6; --mint:#7FD1C0;
    --green-tint:rgba(21,128,61,.10); --warn-tint:rgba(217,119,6,.12); --red-tint:rgba(220,38,38,.10);
    --blue:#1D6FB8; --blue-tint:rgba(29,111,184,.10);
    --purple:#7C3AED; --purple-tint:rgba(124,58,237,.10);
    --sec-ring:rgba(16,124,123,.16);
    --teal-btn-shadow:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);
    --cta-btn-shadow:0 1px 2px rgba(6,32,35,.3),0 10px 20px -10px rgba(12,53,57,.6),inset 0 1px 0 rgba(255,255,255,.12);
    --r-md:12px; --r-lg:16px; --r-xl:20px;
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',sans-serif;color:var(--ink);min-height:100vh;overflow-x:hidden;
    background:radial-gradient(1100px 480px at 88% -12%, rgba(127,209,192,.28), transparent 60%),
      radial-gradient(900px 420px at -8% -4%, rgba(16,124,123,.12), transparent 55%),
      linear-gradient(180deg,var(--mist-a) 0%,#ECF4F4 45%,var(--mist-b) 100%);
    background-attachment:fixed;}
  body.modal-open,body.locked{overflow:hidden}
  ::selection{background:var(--tint);color:var(--deep-2)}
  .glass{background:var(--card);backdrop-filter:blur(14px) saturate(1.15);-webkit-backdrop-filter:blur(14px) saturate(1.15)}
  @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  @keyframes panelIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
  @keyframes sheen{0%{transform:translateX(-130%) skewX(-12deg)}55%,100%{transform:translateX(320%) skewX(-12deg)}}
  @keyframes pulseMint{0%{box-shadow:0 0 0 0 rgba(127,209,192,.6)}70%{box-shadow:0 0 0 7px rgba(127,209,192,0)}100%{box-shadow:0 0 0 0 rgba(127,209,192,0)}}
  @keyframes popIn{0%{transform:scale(.6);opacity:0}70%{transform:scale(1.12)}100%{transform:scale(1);opacity:1}}
  @keyframes toastIn{from{opacity:0;transform:translateY(12px) scale(.96)}to{opacity:1;transform:none}}
  .anim-1{animation:fadeUp .4s ease both;animation-delay:.05s}
  .anim-2{animation:fadeUp .4s ease both;animation-delay:.12s}
  .anim-3{animation:fadeUp .4s ease both;animation-delay:.19s}

  /* chrome (centred container, no icon rail) */
  .nav-inner{max-width:1720px;margin:0 auto;padding:0 32px;width:100%}
  .topnav{position:fixed;top:0;left:0;right:0;z-index:100;height:60px;display:flex;align-items:center;background:linear-gradient(180deg,var(--row1) 0%,var(--deep-2) 100%);box-shadow:0 2px 18px rgba(10,46,50,.35),inset 0 1px 0 rgba(255,255,255,.08)}
  .topnav::after{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(600px 120px at 82% 0%,rgba(127,209,192,.16),transparent 70%)}
  .topnav .nav-inner{display:flex;align-items:center;position:relative;z-index:1}
  .topnav-brand{display:flex;align-items:center;gap:10px;padding-right:24px;border-right:1px solid rgba(255,255,255,.14);margin-right:20px;flex-shrink:0}
  .topnav-logo{width:32px;height:32px;background:linear-gradient(180deg,var(--mint),var(--sec));border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:var(--deep-3)}
  .topnav-app{font-size:14px;font-weight:700;color:#fff}
  .topnav-sep{color:rgba(255,255,255,.32);margin:0 8px;font-size:13px}
  .topnav-org{font-size:13px;color:rgba(255,255,255,.60)}
  .topnav-org strong{color:rgba(255,255,255,.95);font-weight:600}
  .topnav-center{flex:1;display:flex;justify-content:center;gap:8px}
  .topnav-pill{display:flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:20px;color:rgba(255,255,255,.78);font-size:12.5px;font-weight:600;cursor:pointer;transition:.2s;text-decoration:none}
  .topnav-pill:hover{background:rgba(255,255,255,.15);color:#fff}
  .topnav-pill .badge-count{background:linear-gradient(180deg,var(--mint),var(--sec));color:var(--deep-3);font-size:10px;font-weight:800;padding:1px 6px;border-radius:10px;min-width:18px;text-align:center}
  .topnav-search{display:flex;align-items:center;gap:8px;padding:6px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:20px;color:rgba(255,255,255,.55);font-size:12.5px;cursor:pointer;min-width:180px}
  .topnav-search kbd{margin-left:auto;background:rgba(255,255,255,.12);padding:1px 5px;border-radius:4px;font-size:10px;color:rgba(255,255,255,.6)}
  .topnav-right{display:flex;align-items:center;gap:12px;margin-left:20px}
  .topnav-icon-btn{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:8px;cursor:pointer;color:rgba(255,255,255,.68);transition:.2s;position:relative;background:transparent;border:none;font-size:14px}
  .topnav-icon-btn:hover{background:rgba(255,255,255,.13);color:#fff}
  .topnav-icon-btn .notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;background:var(--mint);border-radius:50%;border:1.5px solid var(--deep-2);animation:pulseMint 2.4s infinite}
  .topnav-user{display:flex;align-items:center;gap:10px;padding:4px 12px 4px 4px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:24px;cursor:pointer}
  .topnav-avatar{width:30px;height:30px;background:linear-gradient(135deg,var(--mint),var(--sec));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:var(--deep-3)}
  .topnav-user-name{font-size:12px;font-weight:600;color:#fff}
  .topnav-user-role{font-size:10px;color:rgba(255,255,255,.55)}
  .modulenav{position:fixed;top:60px;left:0;right:0;z-index:99;height:48px;background:linear-gradient(180deg,#E4EDEE,var(--row2));border-bottom:1px solid var(--line)}
  .modulenav .nav-inner{display:flex;align-items:center;height:100%;gap:2px}
  .module-item{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;white-space:nowrap;text-decoration:none}
  .module-item i{font-size:13px;opacity:.75}
  .module-item:hover{background:rgba(255,255,255,.65);color:var(--deep-2)}
  .module-item.active{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff;font-weight:700;box-shadow:var(--cta-btn-shadow)}
  .module-item.active i{opacity:1;color:var(--mint)}
  .module-item .chevron{font-size:9px;opacity:.5;margin-left:2px}
  .main{margin-top:108px;padding:30px 32px 70px;max-width:1720px;margin-left:auto;margin-right:auto}
  .breadcrumb{display:flex;align-items:center;gap:6px;margin-bottom:18px;font-size:12.5px}
  .breadcrumb a{color:var(--ink-muted);text-decoration:none;font-weight:500;cursor:pointer}
  .breadcrumb a:hover{color:var(--sec)}
  .breadcrumb .sep{color:var(--border);font-size:10px}
  .breadcrumb .current{color:var(--ink);font-weight:600}
  .page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;gap:16px;flex-wrap:wrap}
  .page-title{font-size:26px;font-weight:800;letter-spacing:-.6px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
  .page-subtitle{font-size:13px;color:var(--ink-soft);font-weight:500;margin-top:6px}
  .page-actions{display:flex;gap:9px;flex-wrap:wrap;align-items:center}

  /* buttons */
  .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 17px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;border:none;text-decoration:none;font-family:'Inter',sans-serif;white-space:nowrap}
  .btn:active{transform:scale(.97)}
  .btn:disabled{opacity:.45;cursor:not-allowed;transform:none!important;filter:none!important}
  .btn i{font-size:12px}
  .btn-sm{padding:7px 13px;font-size:12px}
  .btn-xs{padding:5px 10px;font-size:11px;border-radius:8px}
  .btn-ghost{background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-soft)}
  .btn-ghost:hover:not(:disabled){border-color:var(--mint);color:var(--deep-2);background:#fff;transform:translateY(-1px)}
  .btn-primary{background:linear-gradient(180deg,#14918F 0%,var(--sec) 45%,var(--sec-2) 100%);color:#fff;box-shadow:var(--teal-btn-shadow)}
  .btn-primary:hover:not(:disabled){filter:brightness(1.07);transform:translateY(-1px)}
  .btn-deep{background:linear-gradient(180deg,var(--deep-1) 0%,var(--deep-2) 55%,var(--deep-3) 100%);color:#fff;box-shadow:var(--cta-btn-shadow)}
  .btn-deep:hover:not(:disabled){filter:brightness(1.12);transform:translateY(-1px)}
  .btn-danger-ghost{background:var(--red-tint);border:1px solid rgba(220,38,38,.25);color:var(--red-2)}

  /* cards */
  .card{border:1px solid var(--border);border-radius:var(--r-xl);box-shadow:var(--shadow-card);overflow:hidden;position:relative;transition:.2s;background:rgba(255,255,255,.78)}
  .card:hover{box-shadow:var(--shadow-card-hov)}
  .card::before{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:6px 6px 0 0;background:linear-gradient(90deg,transparent 0%,rgba(127,209,192,.35) 22%,rgba(16,124,123,.45) 50%,rgba(127,209,192,.35) 78%,transparent 100%);box-shadow:0 -1px 6px rgba(16,124,123,.12);pointer-events:none;z-index:1}
  .card.menu-open{overflow:visible}
  .card.fs{position:fixed;top:70px;left:16px;right:16px;bottom:16px;height:auto;z-index:260;margin:0;width:auto;max-width:none;overflow:auto;background:#fff;border-radius:16px;box-shadow:0 30px 90px -20px rgba(10,46,50,.55);animation:panelIn .25s ease both}
  .card.fs::before{display:none}
  .card.fs .table-scroll{max-height:none}
  .card.fs .card-body{padding-bottom:32px}
  .fs-btn i{font-size:11px}
  .card-head{display:flex;align-items:center;gap:11px;padding:15px 20px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.5)}
  .card-head-icon{width:30px;height:30px;border-radius:8px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;box-shadow:var(--teal-btn-shadow);flex-shrink:0}
  .card-head-icon.amber{background:linear-gradient(180deg,#E8930C,var(--warn))}
  .card-head-icon.blue{background:linear-gradient(180deg,#2B7FC4,var(--blue))}
  .card-head-icon.purple{background:linear-gradient(180deg,#8B5CF6,var(--purple))}
  .card-title{font-size:14px;font-weight:800;color:var(--deep-2)}
  .card-sub{font-size:11px;color:var(--ink-muted);margin-top:1px}
  .card-head-right{margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .card-body{padding:20px}
  .head-link{font-size:12px;font-weight:700;color:var(--sec);cursor:pointer;text-decoration:none}
  .head-link:hover{text-decoration:underline}

  /* pills / chips */
  .pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:20px;font-size:10.5px;font-weight:700;white-space:nowrap}
  .pill .pdot{width:6px;height:6px;border-radius:50%}
  .pill-draft{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-draft .pdot{background:#9DB4B4}
  .pill-progress{background:var(--warn-tint);color:var(--warn);border:1px solid rgba(217,119,6,.3)}
  .pill-progress .pdot{background:var(--warn)}
  .pill-review{background:var(--blue-tint);color:var(--blue);border:1px solid rgba(29,111,184,.25)}
  .pill-review .pdot{background:var(--blue)}
  .pill-done{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .pill-done .pdot{background:var(--green)}
  .pill-matched{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .pill-bank{background:var(--red-tint);color:var(--red-2);border:1px solid rgba(220,38,38,.22)}
  .pill-book{background:var(--blue-tint);color:var(--blue);border:1px solid rgba(29,111,184,.22)}
  .chip-count{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;background:var(--tint);border:1px solid rgba(16,124,123,.25);color:var(--sec)}
  .chip-muted{background:var(--mist-b);border-color:var(--border);color:var(--ink-soft)}

  /* tab pills */
  .ptabs{display:flex;gap:9px;flex-wrap:wrap;margin-bottom:18px}
  .ptab{padding:9px 18px;border-radius:22px;border:1px solid var(--border);background:rgba(255,255,255,.8);font-size:12.5px;font-weight:700;color:var(--ink-soft);cursor:pointer;transition:.18s;font-family:'Inter',sans-serif}
  .ptab:hover{border-color:var(--mint);color:var(--deep-2);transform:translateY(-1px)}
  .ptab.on{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:transparent;color:#fff;box-shadow:var(--teal-btn-shadow)}
  .ptab .n{font-size:10px;font-weight:800;background:var(--mist-b);color:var(--ink-soft);padding:1px 7px;border-radius:9px;margin-left:6px}
  .ptab.on .n{background:rgba(255,255,255,.2);color:#fff}

  /* filter bar */
  .filter-bar{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:14px 18px;border:1px solid var(--border);border-radius:14px;background:rgba(255,255,255,.75);box-shadow:var(--shadow-card);margin-bottom:18px}
  .fb-label{font-size:10px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-muted)}
  .fb-select,.fb-date{padding:9px 12px;border:1.5px solid var(--border);border-radius:9px;font-family:inherit;font-size:12.5px;color:var(--ink);background:#fff;outline:none;transition:.15s}
  .fb-select{min-width:220px;appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235F7A7C' d='M6 8L2 4h8z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;cursor:pointer}
  .fb-select:focus,.fb-date:focus{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .fb-arrow{color:var(--ink-muted);font-size:11px}
  .filter-bar .right{margin-left:auto}

  /* KPI strips */
  .kpi-strip6{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:18px}
  @media(max-width:1250px){.kpi-strip6{grid-template-columns:repeat(3,1fr)}}
  @media(max-width:760px){.kpi-strip6{grid-template-columns:1fr 1fr}}
  .kpi{border:1px solid var(--border);border-radius:var(--r-md);padding:16px 18px;background:rgba(255,255,255,.75);box-shadow:var(--shadow-card);position:relative;overflow:hidden;transition:.22s;animation:fadeUp .45s ease both}
  .kpi:nth-child(2){animation-delay:.05s}.kpi:nth-child(3){animation-delay:.1s}.kpi:nth-child(4){animation-delay:.15s}.kpi:nth-child(5){animation-delay:.2s}.kpi:nth-child(6){animation-delay:.25s}
  .kpi:hover{transform:translateY(-2px);box-shadow:var(--shadow-card-hov)}
  .kpi-label{font-size:9.5px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:8px}
  .kpi-value{font-size:21px;font-weight:800;letter-spacing:-.5px;font-variant-numeric:tabular-nums;line-height:1.1;color:var(--ink)}
  .kpi-value.green{color:var(--green)}
  .kpi-value.orange{color:#C2410C}
  .kpi-sub{font-size:10.5px;color:var(--ink-soft);font-weight:500;margin-top:5px}
  .kpi.warnhl{background:linear-gradient(135deg,#EA8A0A 0%,#C2610C 60%,#9A4E0A 130%);border-color:rgba(217,119,6,.55)}
  .kpi.warnhl::before{content:'';position:absolute;top:0;bottom:0;left:0;width:38%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.22),transparent);animation:sheen 4.2s ease-in-out infinite}
  .kpi.warnhl .kpi-label,.kpi.warnhl .kpi-sub{color:rgba(255,255,255,.8)}
  .kpi.warnhl .kpi-value{color:#fff;font-size:22px}
  .kpi-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
  .kpi.hl{background:linear-gradient(135deg,var(--sec) 0%,var(--sec-2) 55%,var(--deep-1) 135%);border-color:rgba(16,124,123,.6)}
  .kpi.hl::before{content:'';position:absolute;top:0;bottom:0;left:0;width:38%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.20),transparent);animation:sheen 4.2s ease-in-out infinite}
  .kpi.hl .kpi-label,.kpi.hl .kpi-sub{color:rgba(255,255,255,.72)}
  .kpi.hl .kpi-value{color:#fff}
  .kpi-row{display:flex;align-items:center;gap:13px}
  .kpi-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;background:var(--mist-b);color:var(--ink-soft);flex-shrink:0}
  .kpi-icon.teal{background:var(--tint);color:var(--sec)}
  .kpi-icon.red{background:var(--red-tint);color:var(--red)}
  .kpi.hl .kpi-icon{background:rgba(255,255,255,.16);color:#fff}

  /* tables */
  .table-wrap{border:1px solid var(--border);border-radius:14px;overflow:hidden;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card)}
  .table-scroll{overflow-x:auto}
  .data-table{width:100%;border-collapse:collapse;font-size:13px}
  .data-table thead th{background:linear-gradient(180deg,var(--mist-a),var(--mist-b));text-align:left;padding:12px 14px;font-size:9.5px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-soft);border-bottom:1px solid var(--line);white-space:nowrap}
  .data-table tbody td{padding:14px;border-bottom:1px solid var(--line);vertical-align:middle}
  .data-table tbody tr:nth-child(even) td{background:rgba(244,248,248,.5)}
  .data-table tbody tr:last-child td{border-bottom:none}
  .data-table tbody tr{transition:background .15s}
  .data-table tbody tr.clickable-row{cursor:pointer}
  .data-table tbody tr.clickable-row:hover td{background:var(--tint)}
  .data-table tbody tr.clickable-row:active td{background:rgba(16,124,123,.14)}
  .td-mono{font-family:'JetBrains Mono',monospace;font-size:12.5px;font-variant-numeric:tabular-nums}
  .td-right{text-align:right}
  .td-strong{font-weight:700}
  .td-orange{color:#C2410C;font-weight:800}
  .td-red{color:var(--red-2);font-weight:800}
  .cell-acct{font-size:12.5px;font-weight:600;color:var(--ink-soft);max-width:190px;line-height:1.45}
  .cont-link{font-size:12.5px;font-weight:800;color:var(--sec);cursor:pointer;white-space:nowrap;background:none;border:none;font-family:'Inter',sans-serif}
  .cont-link:hover{color:var(--deep-1);text-decoration:underline}
  .mempty{padding:44px 18px;text-align:center;color:var(--ink-muted);font-size:12.5px}
  .mempty i{display:block;font-size:22px;color:var(--border);margin-bottom:8px}

  /* index tab panels */
  .idx-pane{display:none;animation:panelIn .3s ease both}
  .idx-pane.on{display:block}
  .rep-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
  @media(max-width:1100px){.rep-grid{grid-template-columns:1fr 1fr}}
  .rep-card{border:1px solid var(--border);border-radius:14px;padding:18px;background:rgba(255,255,255,.78);box-shadow:var(--shadow-card);transition:.2s;display:flex;flex-direction:column;gap:10px}
  .rep-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-card-hov)}
  .rep-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;background:var(--tint);color:var(--sec)}
  .rep-name{font-size:13px;font-weight:800;color:var(--deep-2)}
  .rep-desc{font-size:11.5px;color:var(--ink-soft);line-height:1.5;flex:1}
  .audit-item{display:flex;gap:12px;padding:12px 4px;border-bottom:1px solid var(--line);font-size:12.5px}
  .audit-item:last-child{border-bottom:none}
  .audit-dot{width:9px;height:9px;border-radius:50%;background:var(--mint);border:2px solid var(--sec);margin-top:4px;flex-shrink:0}
  .audit-txt b{color:var(--deep-2)}
  .audit-time{font-size:10.5px;color:var(--ink-muted);margin-top:2px}

  /* dropdown menu */
  .menu-wrap{position:relative}
  .menu{position:absolute;right:0;top:calc(100% + 8px);min-width:210px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow-card-hov);padding:6px;display:none;z-index:80;animation:panelIn .18s ease both}
  .menu.open{display:block}
  .menu-item{display:flex;align-items:center;gap:10px;width:100%;padding:9px 12px;border:none;background:none;border-radius:8px;font-size:12.5px;font-weight:600;color:var(--ink-soft);cursor:pointer;font-family:'Inter',sans-serif;text-align:left}
  .menu-item:hover{background:var(--tint);color:var(--deep-2)}
  .menu-item i{width:16px;text-align:center;color:var(--sec)}

  /* stepper */
  .stepper{display:flex;align-items:center;gap:0;padding:18px 22px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.45);overflow-x:auto}
  .step{display:flex;align-items:center;gap:10px;cursor:pointer;padding:4px 6px;border-radius:10px;transition:.18s;flex-shrink:0}
  .step:hover{background:var(--tint)}
  .step.locked{cursor:not-allowed;opacity:.55}
  .step-dot{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;background:var(--mist-b);color:var(--ink-muted);border:1.5px solid var(--border);transition:.25s;flex-shrink:0}
  .step.done .step-dot{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:transparent;color:#fff;box-shadow:var(--teal-btn-shadow)}
  .step.active .step-dot{background:#fff;border-color:var(--sec);color:var(--sec);box-shadow:0 0 0 4px var(--sec-ring)}
  .step-lbl{font-size:12.5px;font-weight:700;color:var(--ink-muted)}
  .step.active .step-lbl{color:var(--deep-2)}
  .step.done .step-lbl{color:var(--sec)}
  .step-line{flex:0 0 34px;height:1.5px;background:var(--border);margin:0 6px}
  .step-line.done{background:var(--mint)}
  .fmt-chips{display:flex;gap:6px;flex-wrap:wrap}
  .fmt{padding:4px 12px;border-radius:20px;font-size:10.5px;font-weight:800;letter-spacing:.4px;border:1px solid var(--border);background:var(--mist-b);color:var(--ink-muted);cursor:default}
  .fmt.on{background:var(--tint);border-color:rgba(16,124,123,.35);color:var(--sec)}
  .fmt.soon{opacity:.6;cursor:help}
  .wz-pane{display:none;animation:panelIn .3s ease both}
  .wz-pane.on{display:block}
  .wz-foot{display:flex;align-items:center;gap:10px;padding:16px 22px;border-top:1px solid var(--line);background:linear-gradient(180deg,var(--mist-a),var(--mist-b));flex-wrap:wrap}
  .wz-foot .right{margin-left:auto;display:flex;gap:10px;flex-wrap:wrap}

  /* forms */
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px 18px}
  .form-grid.cols-3{grid-template-columns:repeat(3,1fr)}
  .form-grid .full{grid-column:1/-1}
  .form-field label{display:block;font-size:10.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:6px}
  .form-field label .req{color:var(--red)}
  .form-control{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:9px;font-family:inherit;font-size:13px;color:var(--ink);background:#fff;outline:none;transition:.15s}
  .form-control:focus{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .form-control:disabled{background:var(--mist-a);color:var(--ink-muted)}
  select.form-control{appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235F7A7C' d='M6 8L2 4h8z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;cursor:pointer}
  .mono{font-family:'JetBrains Mono',monospace;font-variant-numeric:tabular-nums}
  .info-banner{display:flex;gap:10px;align-items:flex-start;padding:12px 16px;border-radius:10px;background:var(--blue-tint);border:1px solid rgba(29,111,184,.22);color:#155E93;font-size:12.5px;font-weight:600;line-height:1.55;margin-bottom:18px}
  .info-banner i{margin-top:2px}
  .ok-banner{background:var(--green-tint);border-color:rgba(21,128,61,.25);color:var(--green)}
  .warn-banner{background:var(--warn-tint);border-color:rgba(217,119,6,.3);color:var(--warn)}

  /* dropzone / sheets / mapping / preview */
  .dropzone{border:2px dashed var(--border);border-radius:14px;padding:34px 20px;text-align:center;color:var(--ink-soft);background:rgba(255,255,255,.6);cursor:pointer;transition:.2s}
  .dropzone:hover,.dropzone.drag{border-color:var(--sec);background:var(--tint);transform:scale(1.004)}
  .dropzone i{font-size:26px;color:var(--sec);margin-bottom:10px;display:block}
  .dz-title{font-size:14px;font-weight:800;color:var(--ink);margin-bottom:4px}
  .dz-sub{font-size:11.5px}
  .dz-file{display:flex;align-items:center;gap:12px;padding:12px 16px;border:1px solid rgba(16,124,123,.35);border-radius:12px;background:var(--tint);margin-top:14px;animation:popIn .3s ease both}
  .dz-file i{color:var(--sec);font-size:18px}
  .dz-file .fn{font-size:12.5px;font-weight:700;color:var(--deep-2)}
  .dz-file .fm{font-size:10.5px;color:var(--ink-muted)}
  .dz-file .rm{margin-left:auto;background:none;border:none;color:var(--red-2);cursor:pointer;font-size:12px}
  .sheet-chips{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0 16px}
  .sheet-chip{padding:7px 14px;border-radius:10px;border:1.5px solid var(--border);background:rgba(255,255,255,.8);font-size:12px;font-weight:700;color:var(--ink-soft);cursor:pointer;transition:.18s;display:flex;align-items:center;gap:8px}
  .sheet-chip:hover{border-color:var(--mint)}
  .sheet-chip.on{border-color:var(--sec);background:var(--tint);color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .sheet-chip .n{font-size:9.5px;font-weight:800;background:var(--mist-b);padding:1px 7px;border-radius:8px;color:var(--ink-muted)}
  .sheet-chip.on .n{background:rgba(16,124,123,.15);color:var(--sec)}
  .sec-lbl{font-size:10.5px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--deep-2);margin:20px 0 8px;display:flex;align-items:center;gap:8px}
  .sec-lbl::after{content:'';flex:1;height:1px;background:var(--line)}
  .sec-lbl:first-child{margin-top:0}
  .map-grid{display:flex;flex-direction:column;gap:8px;margin:12px 0}
  .map-row{display:grid;grid-template-columns:230px 34px 1fr 190px;gap:10px;align-items:center;padding:10px 14px;border:1px solid var(--border);border-radius:11px;background:rgba(255,255,255,.75);transition:.18s}
  @media(max-width:900px){.map-row{grid-template-columns:1fr;gap:6px}.map-arrow{display:none}}
  .map-row.mapped{border-color:rgba(16,124,123,.35);background:linear-gradient(90deg,var(--tint),rgba(255,255,255,.7))}
  .map-field{display:flex;align-items:center;gap:9px;font-size:12.5px;font-weight:700;color:var(--deep-2)}
  .map-field i{width:26px;height:26px;border-radius:7px;background:var(--mist-b);color:var(--ink-soft);display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0}
  .map-row.mapped .map-field i{background:var(--sec);color:#fff}
  .map-field .req{color:var(--red)}
  .map-arrow{color:var(--mint);font-size:12px;text-align:center}
  .map-sample{font-size:10.5px;color:var(--ink-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .map-sample b{color:var(--sec);font-weight:800}
  .auto-badge{font-size:9px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;background:var(--green-tint);color:var(--green);padding:2px 7px;border-radius:8px;margin-left:6px}
  .pv-wrap{border:1px solid var(--border);border-radius:12px;overflow:auto;max-height:280px;background:#fff}
  .pv-table{width:100%;border-collapse:collapse;font-size:12px}
  .pv-table th{position:sticky;top:0;background:linear-gradient(180deg,var(--mist-a),var(--mist-b));padding:8px 12px;font-size:9.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--ink-soft);text-align:left;border-bottom:1px solid var(--line);white-space:nowrap;z-index:2}
  .pv-table td{padding:8px 12px;border-bottom:1px solid var(--line);white-space:nowrap;color:var(--ink-soft)}
  .pv-table tr:last-child td{border-bottom:none}
  .pv-table td.neg{color:var(--red-2);font-weight:700}
  .pv-table td.pos{color:var(--green);font-weight:700}
  .pv-table .rn{color:#9DB4B4;font-size:10px}
  #pv-table th:nth-child(n+5),#pv-table td:nth-child(n+5){text-align:right;font-variant-numeric:tabular-nums}
  #sheet-zone{margin-top:32px;padding-top:26px;border-top:1px solid var(--line)}
  #sheet-zone .sec-lbl:first-child{margin-top:0}
  .stat-chips{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0 0}
  .st-chip{display:flex;align-items:center;gap:9px;padding:9px 14px;border-radius:11px;border:1px solid var(--border);background:rgba(255,255,255,.75);font-size:11.5px;font-weight:600;color:var(--ink-soft)}
  .st-chip i{color:var(--sec)}
  .st-chip b{font-family:'JetBrains Mono',monospace;color:var(--deep-2);font-size:12.5px}

  /* matcher */
  .match-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media(max-width:1000px){.match-grid{grid-template-columns:1fr}}
  .mpanel{border:1px solid var(--border);border-radius:14px;overflow:hidden;background:rgba(255,255,255,.8);display:flex;flex-direction:column;max-height:520px}
  .mpanel-head{display:flex;align-items:center;gap:9px;padding:12px 14px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,var(--mist-a),var(--mist-b))}
  .mpanel-title{font-size:11px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--deep-2)}
  .mpanel-sub{font-size:10px;color:var(--ink-muted);font-weight:600}
  .mpanel-search{margin-left:auto;display:flex;align-items:center;gap:6px;padding:5px 10px;background:#fff;border:1px solid var(--border);border-radius:8px;font-size:11px;color:var(--ink-muted);width:150px}
  .mpanel-search input{border:none;outline:none;font-family:inherit;font-size:11px;width:100%;color:var(--ink)}
  .mpanel-body{overflow-y:auto;flex:1}
  .mrow{display:grid;grid-template-columns:76px 1fr 96px 92px 26px;gap:8px;align-items:center;padding:10px 12px;border-bottom:1px solid var(--line);cursor:pointer;transition:.15s;font-size:12px}
  .mrow:hover{background:var(--tint)}
  .mrow.sel{background:rgba(16,124,123,.10);box-shadow:inset 3px 0 0 var(--sec)}
  .mrow.matched{opacity:.62}
  .mrow .d{color:var(--ink-muted);font-size:11px;font-weight:600}
  .mrow .desc{min-width:0}
  .mrow .desc .t{display:block;font-weight:600;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:1.35}
  .mrow .desc .r{display:block;margin-top:3px;font-size:10px;color:var(--ink-muted);font-family:'JetBrains Mono',monospace;line-height:1.2}
  .mrow .amt{text-align:right;font-family:'JetBrains Mono',monospace;font-weight:700;font-size:12px}
  .mrow .amt.neg{color:var(--red-2)}
  .mrow .unm{background:none;border:none;color:var(--ink-muted);cursor:pointer;font-size:11px;opacity:0;transition:.15s}
  .mrow:hover .unm{opacity:1}
  .mrow .unm:hover{color:var(--red-2)}
  .match-bar{display:none;align-items:center;gap:10px;padding:10px 16px;border-radius:12px;background:linear-gradient(135deg,var(--deep-2),var(--deep-3));color:#fff;margin-bottom:14px;animation:panelIn .25s ease both;box-shadow:var(--cta-btn-shadow)}
  .match-bar.show{display:flex}
  .match-bar .txt{font-size:12.5px;font-weight:700}
  .match-bar .txt span{color:var(--mint)}
  .match-bar .right{margin-left:auto;display:flex;gap:8px}

  /* calc / summary / suggestions / adjustments / imports */
  .calc-row{display:flex;justify-content:space-between;align-items:center;padding:10px 2px;border-bottom:1px solid var(--line);font-size:12.5px}
  .calc-row:last-of-type{border-bottom:none}
  .calc-row .l{color:var(--ink-soft);font-weight:600}
  .calc-row .v{font-family:'JetBrains Mono',monospace;font-weight:800;color:var(--deep-2);font-size:13px}
  .calc-row.total{border-top:1.5px solid var(--border);margin-top:4px;padding-top:12px}
  .calc-row.total .l{color:var(--ink);font-weight:800}
  .calc-row.total .v{font-size:15px}
  .calc-row.total .v.bad{color:var(--red-2)}
  .calc-row.total .v.good{color:var(--green)}
  .sugg{display:flex;align-items:center;gap:12px;padding:11px 12px;border:1px solid var(--border);border-radius:11px;background:rgba(255,255,255,.75);margin-bottom:8px;animation:fadeUp .3s ease both}
  .sugg .conf{width:38px;height:38px;border-radius:50%;background:var(--warn-tint);color:var(--warn);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;border:1px solid rgba(217,119,6,.3)}
  .sugg .pair{flex:1;min-width:0;font-size:11.5px;color:var(--ink-soft);line-height:1.5}
  .sugg .pair b{color:var(--deep-2);font-weight:700;display:block;font-size:12px}
  .adj-form{display:grid;grid-template-columns:1.1fr .8fr .8fr .9fr 1.1fr 1.4fr auto;gap:9px;align-items:end;padding:14px;border:1px dashed var(--border);border-radius:12px;background:rgba(255,255,255,.6)}
  @media(max-width:1100px){.adj-form{grid-template-columns:1fr 1fr}}
  .adj-form label{font-size:9.5px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-muted);display:block;margin-bottom:5px}
  .adj-form .form-control{padding:8px 10px;font-size:12px}
  .imp{display:flex;align-items:center;gap:11px;padding:10px 2px;border-bottom:1px solid var(--line);font-size:12px}
  .imp:last-child{border-bottom:none}
  .imp i{color:var(--sec)}
  .imp .fn{font-weight:700;color:var(--deep-2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px}
  .imp .meta{font-size:10.5px;color:var(--ink-muted)}
  .imp .lines{margin-left:auto;font-family:'JetBrains Mono',monospace;font-weight:700;font-size:11.5px;color:var(--ink-soft)}
  .fin-list{display:flex;flex-direction:column;gap:10px;margin:16px 0}
  .fin-item{display:flex;align-items:center;gap:12px;padding:13px 16px;border:1px solid var(--border);border-radius:12px;background:rgba(255,255,255,.75);font-size:12.5px;font-weight:600;color:var(--ink-soft)}
  .fin-item i{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
  .fin-item .i-ok{background:var(--green-tint);color:var(--green)}
  .fin-item .i-warn{background:var(--warn-tint);color:var(--warn)}
  .fin-item .i-info{background:var(--blue-tint);color:var(--blue)}
  .fin-item b{color:var(--deep-2);font-family:'JetBrains Mono',monospace}
  .big-check{width:86px;height:86px;margin:6px auto 16px;border-radius:50%;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:34px;box-shadow:var(--teal-btn-shadow);animation:popIn .5s cubic-bezier(.2,.9,.3,1.4) both}
  .ws-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:18px;align-items:start;margin-top:18px}
  @media(max-width:1100px){.ws-grid{grid-template-columns:1fr}}
  .col{display:flex;flex-direction:column;gap:18px}

  /* toast + modal */
  .toast-wrap{position:fixed;bottom:24px;right:24px;z-index:400;display:flex;flex-direction:column;gap:10px}
  .toast{display:flex;align-items:flex-start;gap:10px;min-width:300px;max-width:420px;padding:13px 16px;border-radius:12px;background:linear-gradient(135deg,var(--deep-2),var(--deep-3));color:#fff;box-shadow:var(--cta-btn-shadow);animation:toastIn .3s cubic-bezier(.2,.9,.3,1.2) both}
  .toast.ok i{color:var(--mint)}.toast.warn i{color:#FBBF24}.toast.err i{color:#FCA5A5}
  .toast .t-title{font-size:12.5px;font-weight:800}
  .toast .t-sub{font-size:11px;color:rgba(255,255,255,.65);margin-top:3px;line-height:1.5}
  .modal-overlay{position:fixed;inset:0;background:rgba(10,46,50,.55);backdrop-filter:blur(5px);z-index:300;display:none;align-items:flex-start;justify-content:center;padding:48px 20px;overflow-y:auto}
  .modal-overlay.open{display:flex;animation:fadeIn .2s ease both}
  .modal{background:#fff;border-radius:16px;width:100%;max-width:480px;box-shadow:0 24px 70px -18px rgba(10,46,50,.5);animation:popIn .3s cubic-bezier(.2,.9,.3,1.2) both;overflow:hidden}
  .modal-header{display:flex;align-items:center;gap:14px;padding:18px 24px;background:radial-gradient(120% 150% at 88% -30%, rgba(127,209,192,.28), transparent 55%),linear-gradient(135deg,var(--deep-2),var(--deep-3))}
  .modal-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;box-shadow:var(--teal-btn-shadow)}
  .modal-title{font-size:16px;font-weight:800;color:#fff}
  .modal-subtitle{font-size:12px;color:rgba(255,255,255,.62);margin-top:2px}
  .modal-body{padding:22px}
  .modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:15px 22px;border-top:1px solid var(--line);background:linear-gradient(180deg,var(--mist-a),var(--mist-b))}
  [data-tip]{position:relative}
  [data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--deep-2);color:#fff;font-size:11px;font-weight:500;padding:4px 9px;border-radius:6px;white-space:nowrap;pointer-events:none;z-index:200;animation:fadeIn .15s ease both}
  ::-webkit-scrollbar{width:5px;height:5px}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
  ::-webkit-scrollbar-thumb:hover{background:var(--sec)}
</style>
</head>
<body>

<nav class="topnav">
  <div class="nav-inner">
    <div class="topnav-brand"><div class="topnav-logo">L</div><span class="topnav-app">CamelotBooks</span><span class="topnav-sep">|</span><span class="topnav-org"><strong>Acme Corporation</strong> &nbsp;·&nbsp; Headquarters</span></div>
    <div class="topnav-center"><a class="topnav-pill" href="#"><i class="fa-solid fa-list-check"></i> My Tasks <span class="badge-count">3</span></a><a class="topnav-pill" href="#"><i class="fa-solid fa-star"></i> Favourites <span class="badge-count">16</span></a><div class="topnav-search"><i class="fa-solid fa-magnifying-glass"></i> Search… <kbd>Ctrl K</kbd></div></div>
    <div class="topnav-right"><button class="topnav-icon-btn" data-tip="Notifications"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button><button class="topnav-icon-btn" data-tip="Help"><i class="fa-solid fa-circle-question"></i></button><div class="topnav-user"><div class="topnav-avatar">ES</div><div><div class="topnav-user-name">Elvis Seyama</div><div class="topnav-user-role">SYSTEM_ADMIN</div></div></div></div>
  </div>
</nav>
<nav class="modulenav">
  <div class="nav-inner">
    <a class="module-item" href="#"><i class="fa-solid fa-cart-shopping"></i> Sales <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-truck-field"></i> Purchasing <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-cubes"></i> Inventory <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item active" href="#"><i class="fa-solid fa-building-columns"></i> Banking <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-book-open"></i> Accounting <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-building"></i> Assets <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-users"></i> Payroll <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-chart-bar"></i> Reports <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-gear"></i> System <i class="fa-solid fa-chevron-down chevron"></i></a>
  </div>
</nav>

<main class="main">

<!-- ══════════ VIEW: INDEX (REGISTER) ══════════ -->
<div id="view-index">
  <div class="breadcrumb anim-1"><a>Banking</a><i class="fa-solid fa-chevron-right sep"></i><span class="current">Reconciliation</span></div>

  <div class="page-header anim-1">
    <div>
      <h1 class="page-title">Bank Reconciliation</h1>
      <div class="page-subtitle">Match bank statement lines to book transactions and clear them on completion.</div>
    </div>
    <div class="page-actions">
      <button class="btn btn-ghost" onclick="startImport()"><i class="fa-solid fa-file-import"></i> Import Statement</button>
      <button class="btn btn-ghost" onclick="refreshIndex()"><i class="fa-solid fa-rotate"></i> Refresh</button>
      <div class="menu-wrap">
        <button class="btn btn-ghost" onclick="toggleMenu(event)"><span>More</span> <i class="fa-solid fa-chevron-down"></i></button>
        <div class="menu" id="more-menu">
          <button class="menu-item" onclick="exportRegister()"><i class="fa-solid fa-download"></i> Export register (CSV)</button>
          <button class="menu-item" onclick="setTab('audit');closeMenu()"><i class="fa-solid fa-clock-rotate-left"></i> Jump to Audit Trail</button>
          <button class="menu-item" onclick="toggleHistory()"><i class="fa-solid fa-box-archive"></i> <span id="hist-label">Show finalized periods</span></button>
        </div>
      </div>
      <button class="btn btn-deep" onclick="newReconciliation()"><i class="fa-solid fa-plus"></i> New Reconciliation</button>
    </div>
  </div>

  <div class="ptabs anim-2" id="ptabs">
    <button class="ptab on" data-tab="recs">Reconciliations</button>
    <button class="ptab" data-tab="stmts">Bank Statements</button>
    <button class="ptab" data-tab="adjs">Adjustments</button>
    <button class="ptab" data-tab="outst">Outstanding Items</button>
    <button class="ptab" data-tab="reps">Reports</button>
    <button class="ptab" data-tab="audit">Audit Trail</button>
  </div>

  <div class="idx-pane on" id="pane-recs">
    <div class="filter-bar anim-2">
      <span class="fb-label">Bank Account</span>
      <select class="fb-select" id="f-account"><option value="">All accounts</option></select>
      <span class="fb-label">Period</span>
      <input type="date" class="fb-date" id="f-from">
      <span class="fb-arrow"><i class="fa-solid fa-arrow-right-long"></i></span>
      <input type="date" class="fb-date" id="f-to">
      <button class="btn btn-ghost btn-sm" onclick="applyFilters()"><i class="fa-solid fa-filter"></i> Filter</button>
      <button class="btn btn-ghost btn-sm" onclick="clearFilters()">Clear</button>
      <span class="chip-count right" id="period-chip">3 periods</span>
    </div>
    <div class="kpi-strip6 anim-3" id="kpi-strip"></div>
    <div class="card glass anim-3">
      <div class="card-head">
        <div class="card-title" style="font-size:15px">Reconciliation Register</div>
        <span class="chip-count" id="reg-chip">3 periods</span>
        <div class="card-head-right"><a class="head-link" onclick="toggleHistory()">Reconciliation history →</a></div>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead><tr>
            <th>Bank Account</th><th>Statement</th><th>Period End</th>
            <th class="td-right">Opening (K)</th><th class="td-right">Closing (K)</th><th class="td-right">Book (K)</th>
            <th class="td-right">Statement Bal. (K)</th><th class="td-right">Difference</th><th>Status</th><th></th>
          </tr></thead>
          <tbody id="register-rows"></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="idx-pane" id="pane-stmts">
    <div class="card glass">
      <div class="card-head"><div class="card-head-icon"><i class="fa-solid fa-file-invoice"></i></div><div><div class="card-title">Bank Statements</div><div class="card-sub">Every file imported into a reconciliation period</div></div>
        <div class="card-head-right"><button class="btn btn-ghost btn-sm" onclick="startImport()"><i class="fa-solid fa-plus"></i> Import</button></div></div>
      <div class="card-body" id="stmts-body"></div>
    </div>
  </div>

  <div class="idx-pane" id="pane-adjs">
    <div class="card glass">
      <div class="card-head"><div class="card-head-icon purple"><i class="fa-solid fa-sliders"></i></div><div><div class="card-title">Adjustments</div><div class="card-sub">Book & statement side corrections across all periods</div></div></div>
      <div class="card-body" id="adjs-body"></div>
    </div>
  </div>

  <div class="idx-pane" id="pane-outst">
    <div class="card glass">
      <div class="card-head"><div class="card-head-icon amber"><i class="fa-solid fa-circle-question"></i></div><div><div class="card-title">Outstanding Items</div><div class="card-sub">Unmatched bank lines and book transactions needing attention — full workflow per Outstanding Items mockup</div></div></div>
      <div class="card-body" id="outst-body"></div>
    </div>
  </div>

  <div class="idx-pane" id="pane-reps">
    <div class="rep-grid">
      <div class="rep-card"><div class="rep-icon"><i class="fa-solid fa-scale-balanced"></i></div><div class="rep-name">Reconciliation Summary</div><div class="rep-desc">Period-by-period book vs bank position with differences and status.</div><button class="btn btn-primary btn-sm" onclick="runReport('Reconciliation Summary')"><i class="fa-solid fa-play"></i> Run</button></div>
      <div class="rep-card"><div class="rep-icon" style="background:var(--warn-tint);color:var(--warn)"><i class="fa-solid fa-hourglass-half"></i></div><div class="rep-name">Unmatched Items</div><div class="rep-desc">All bank-only and book-only lines carried across open periods.</div><button class="btn btn-primary btn-sm" onclick="runReport('Unmatched Items')"><i class="fa-solid fa-play"></i> Run</button></div>
      <div class="rep-card"><div class="rep-icon" style="background:var(--purple-tint);color:var(--purple)"><i class="fa-solid fa-sliders"></i></div><div class="rep-name">Adjustment Ledger</div><div class="rep-desc">Posted and pending reconciliation adjustments by account and side.</div><button class="btn btn-primary btn-sm" onclick="runReport('Adjustment Ledger')"><i class="fa-solid fa-play"></i> Run</button></div>
      <div class="rep-card"><div class="rep-icon" style="background:var(--blue-tint);color:var(--blue)"><i class="fa-solid fa-clock-rotate-left"></i></div><div class="rep-name">Audit Trail</div><div class="rep-desc">Who imported, matched, adjusted and finalized — with timestamps.</div><button class="btn btn-primary btn-sm" onclick="runReport('Audit Trail')"><i class="fa-solid fa-play"></i> Run</button></div>
    </div>
  </div>

  <div class="idx-pane" id="pane-audit">
    <div class="card glass">
      <div class="card-head"><div class="card-head-icon blue"><i class="fa-solid fa-clock-rotate-left"></i></div><div><div class="card-title">Audit Trail</div><div class="card-sub">Immutable event log for this module</div></div></div>
      <div class="card-body" id="audit-body"></div>
    </div>
  </div>
</div>

<!-- ══════════ VIEW: WORKSPACE ══════════ -->
<div id="view-workspace" style="display:none">
  <div class="breadcrumb anim-1"><a onclick="showView('index')">Banking</a><i class="fa-solid fa-chevron-right sep"></i><a onclick="showView('index')">Reconciliation</a><i class="fa-solid fa-chevron-right sep"></i><span class="current" id="ws-crumb">Period</span></div>
  <div class="page-header anim-1">
    <div>
      <h1 class="page-title">Bank Reconciliation <span id="ws-status-pill" class="pill pill-progress"><span class="pdot"></span> In Progress</span></h1>
      <div class="page-subtitle" id="ws-subtitle">1000 — Cash and Cash Equivalents · Aug 01, 2026 – Aug 31, 2026 · MWK (K)</div>
    </div>
    <div class="page-actions">
      <button class="btn btn-ghost" onclick="showView('index')"><i class="fa-solid fa-arrow-left"></i> Register</button>
      <button class="btn btn-ghost" onclick="startImport()"><i class="fa-solid fa-file-import"></i> Import Statement</button>
      <button class="btn btn-primary" onclick="autoMatch()"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto Match</button>
      <button class="btn btn-ghost" onclick="markReady()"><i class="fa-solid fa-flag-checkered"></i> Mark Ready</button>
      <button class="btn btn-deep" onclick="openWizard(4)"><i class="fa-solid fa-eye"></i> Review &amp; Finalize</button>
    </div>
  </div>
  <div id="ws-lock" style="display:none" class="info-banner ok-banner"><i class="fa-solid fa-lock"></i><span>This reconciliation is <b>finalized & locked</b>. Import a new statement or reopen the period to make changes.</span></div>
  <div class="kpi-strip anim-2">
    <div class="kpi"><div class="kpi-row"><div class="kpi-icon teal"><i class="fa-solid fa-file-lines"></i></div><div><div class="kpi-label">Statement Lines</div><div class="kpi-value" id="k-lines">0</div><div class="kpi-sub" id="k-lines-sub">imported</div></div></div></div>
    <div class="kpi"><div class="kpi-row"><div class="kpi-icon" style="background:var(--green-tint);color:var(--green)"><i class="fa-solid fa-link"></i></div><div><div class="kpi-label">Matched Pairs</div><div class="kpi-value" id="k-matched">0</div><div class="kpi-sub">book ↔ statement</div></div></div></div>
    <div class="kpi"><div class="kpi-row"><div class="kpi-icon red"><i class="fa-solid fa-circle-question"></i></div><div><div class="kpi-label">Unmatched</div><div class="kpi-value" id="k-unmatched">0</div><div class="kpi-sub" id="k-unmatched-sub">bank / book</div></div></div></div>
    <div class="kpi hl" id="k-diff-card"><div class="kpi-row"><div class="kpi-icon"><i class="fa-solid fa-not-equal"></i></div><div><div class="kpi-label">Difference</div><div class="kpi-value" id="k-diff">K 0.00</div><div class="kpi-sub">target K 0.00</div></div></div></div>
  </div>
  <div class="card glass anim-3">
    <div class="card-head">
      <div class="card-head-icon"><i class="fa-solid fa-link"></i></div>
      <div><div class="card-title">Transaction Matcher</div><div class="card-sub">Select one book transaction and one statement line, then match</div></div>
      <div class="card-head-right">
        <span class="chip-count" id="ws-lines-chip">0 statement lines</span>
        <button class="btn btn-ghost btn-sm fs-btn tip-below" data-tip="Expand to full width" onclick="toggleFs(this)"><i class="fa-solid fa-expand"></i> Expand</button>
        <button class="btn btn-primary btn-sm" onclick="autoMatch()"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto Match</button>
      </div>
    </div>
    <div class="card-body">
      <div class="match-bar" id="match-bar">
        <span class="txt"><span id="mb-book">0</span> book + <span id="mb-stmt">0</span> statement selected</span>
        <div class="right"><button class="btn btn-primary btn-sm" onclick="matchSelected()"><i class="fa-solid fa-link"></i> Match Selected</button><button class="btn btn-ghost btn-sm" onclick="clearSel()">Clear</button></div>
      </div>
      <div class="match-grid" id="ws-matcher"></div>
    </div>
  </div>
  <div class="ws-grid">
    <div class="col">
      <div class="card glass"><div class="card-head"><div class="card-head-icon amber"><i class="fa-solid fa-calculator"></i></div><div><div class="card-title">Reconciliation Calculation</div><div class="card-sub">Adjusted book vs adjusted bank</div></div></div><div class="card-body" id="ws-calc"></div></div>
      <div class="card glass"><div class="card-head"><div class="card-head-icon purple"><i class="fa-solid fa-sliders"></i></div><div><div class="card-title">Adjustments</div><div class="card-sub" id="ws-adj-count">0 recorded</div></div><div class="card-head-right"><span class="chip-count chip-muted" id="ws-adj-chip">0</span></div></div>
        <div class="card-body">
          <div id="ws-adj-list" style="margin-bottom:14px"></div>
          <div class="adj-form">
            <div><label>Type</label><select class="form-control" id="adj-type"><option>Bank Fees</option><option>Interest Income</option><option>NSF Cheque</option><option>Deposit in Transit</option><option>Outstanding Cheque</option><option>Error Correction</option></select></div>
            <div><label>Sign</label><select class="form-control" id="adj-sign"><option value="1">Add (+)</option><option value="-1">Subtract (−)</option></select></div>
            <div><label>Side</label><select class="form-control" id="adj-side"><option value="book">Book</option><option value="statement">Statement</option></select></div>
            <div><label>Amount (K)</label><input class="form-control mono" id="adj-amt" type="number" step="0.01" placeholder="0.00"></div>
            <div><label>Account</label><select class="form-control" id="adj-acct"><option value="">—</option><option>6100 — Bank Charges</option><option>4100 — Interest Income</option><option>1100 — Accounts Receivable</option><option>2100 — Accounts Payable</option></select></div>
            <div><label>Description</label><input class="form-control" id="adj-desc" placeholder="Optional note"></div>
            <button class="btn btn-primary" onclick="addAdjustment(false)"><i class="fa-solid fa-plus"></i> Add</button>
          </div>
        </div></div>
    </div>
    <div class="col">
      <div class="card glass"><div class="card-head"><div class="card-head-icon blue"><i class="fa-solid fa-chart-simple"></i></div><div><div class="card-title">Balance Summary</div><div class="card-sub">Live position</div></div></div><div class="card-body" id="ws-summary"></div></div>
      <div class="card glass"><div class="card-head"><div class="card-head-icon amber"><i class="fa-solid fa-lightbulb"></i></div><div><div class="card-title">Suggestions</div><div class="card-sub">Auto-match candidates</div></div></div><div class="card-body" id="ws-suggestions"></div></div>
      <div class="card glass"><div class="card-head"><div class="card-head-icon"><i class="fa-solid fa-file-import"></i></div><div><div class="card-title">Imports</div><div class="card-sub">Statement files in this period</div></div><div class="card-head-right"><button class="btn btn-ghost btn-sm" onclick="startImport()"><i class="fa-solid fa-plus"></i> Import</button></div></div><div class="card-body" id="ws-imports"></div></div>
    </div>
  </div>
</div>

<!-- ══════════ VIEW: WIZARD ══════════ -->
<div id="view-wizard" style="display:none">
  <div class="breadcrumb anim-1"><a onclick="showView('index')">Banking</a><i class="fa-solid fa-chevron-right sep"></i><a onclick="showView('index')">Reconciliation</a><i class="fa-solid fa-chevron-right sep"></i><span class="current">Import Bank Statement</span></div>
  <div class="page-header anim-1">
    <div><h1 class="page-title">Import Bank Statement</h1><div class="page-subtitle" id="wz-subtitle">1000 — Cash and Cash Equivalents · Aug 01, 2026 – Aug 31, 2026</div></div>
    <div class="page-actions"><button class="btn btn-ghost" onclick="leaveWizard()"><i class="fa-solid fa-arrow-left"></i> Back</button></div>
  </div>
  <div class="card glass anim-2">
    <div class="card-head">
      <div class="card-head-icon"><i class="fa-solid fa-file-import"></i></div>
      <div><div class="card-title">Import Bank Statement</div><div class="card-sub">Upload, choose sheet, map columns, preview, import</div></div>
      <div class="card-head-right">
        <div class="fmt-chips"><span class="fmt on">XLSX</span><span class="fmt on">CSV</span><span class="fmt soon" data-tip="Planned — engine-ready">OFX</span><span class="fmt soon" data-tip="Planned — engine-ready">QFX</span><span class="fmt soon" data-tip="Planned — engine-ready">MT940</span><span class="fmt soon" data-tip="Planned — engine-ready">CAMT.053</span></div>
        <button class="btn btn-ghost btn-sm fs-btn tip-below" data-tip="Expand to full width" onclick="toggleFs(this)"><i class="fa-solid fa-expand"></i> Expand</button>
      </div>
    </div>
    <div class="stepper" id="stepper">
      <div class="step done" data-step="1" onclick="goStep(1)"><span class="step-dot"><i class="fa-solid fa-check"></i></span><span class="step-lbl">Statement</span></div>
      <div class="step-line done"></div>
      <div class="step active" data-step="2" onclick="goStep(2)"><span class="step-dot">2</span><span class="step-lbl">Import</span></div>
      <div class="step-line"></div>
      <div class="step locked" data-step="3" onclick="goStep(3)"><span class="step-dot">3</span><span class="step-lbl">Matching</span></div>
      <div class="step-line"></div>
      <div class="step locked" data-step="4" onclick="goStep(4)"><span class="step-dot">4</span><span class="step-lbl">Review</span></div>
      <div class="step-line"></div>
      <div class="step locked" data-step="5" onclick="goStep(5)"><span class="step-dot">5</span><span class="step-lbl">Complete</span></div>
    </div>

    <div class="wz-pane" id="pane-1">
      <div class="card-body">
        <div class="info-banner"><i class="fa-solid fa-circle-info"></i><span>Define the statement you are reconciling. The period locks which book transactions are eligible for matching.</span></div>
        <div class="form-grid">
          <div class="form-field"><label>Bank Account <span class="req">*</span></label>
            <select class="form-control" id="s1-account"><option value="1000" selected>1000 — Cash and Cash Equivalents</option><option value="1020">1020 — Stanbic Operating Account</option><option value="1030">1030 — Mobile Money Wallet</option></select></div>
          <div class="form-field"><label>Statement Currency</label><input class="form-control" value="MWK (K)" disabled></div>
          <div class="form-field"><label>Period Start <span class="req">*</span></label><input class="form-control" type="date" id="s1-start" value="2026-08-01"></div>
          <div class="form-field"><label>Period End <span class="req">*</span></label><input class="form-control" type="date" id="s1-end" value="2026-08-31"></div>
          <div class="form-field"><label>Opening Balance (K)</label><input class="form-control mono" type="number" step="0.01" id="s1-open" value="0.00"></div>
          <div class="form-field"><label>Closing Balance per Statement (K)</label><input class="form-control mono" type="number" step="0.01" id="s1-close" placeholder="auto-detected after mapping"></div>
        </div>
      </div>
      <div class="wz-foot"><div class="right"><button class="btn btn-deep" onclick="goStep(2)">Continue to Import <i class="fa-solid fa-arrow-right"></i></button></div></div>
    </div>

    <div class="wz-pane on" id="pane-2">
      <div class="card-body">
        <div class="info-banner"><i class="fa-solid fa-circle-info"></i><span>Start with Excel and CSV. The engine architecture allows OFX, MT940 and CAMT.053 later without touching core accounting logic. After uploading, choose the <b>worksheet</b> and map your file's column names to system fields.</span></div>
        <div class="dropzone" id="dropzone" onclick="document.getElementById('file-input').click()">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <div class="dz-title">Drop your bank statement here or click to browse</div>
          <div class="dz-sub">CSV, XLSX, XLS · max 10 MB · first row should contain column headers</div>
          <input type="file" id="file-input" accept=".csv,.xlsx,.xls" style="display:none">
        </div>
        <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap;align-items:center">
          <button class="btn btn-ghost btn-sm" onclick="loadSample()"><i class="fa-solid fa-flask"></i> Load sample statement (no file needed)</button>
          <span class="chip-count chip-muted" id="parse-state">No file parsed</span>
        </div>
        <div id="dz-file-box"></div>
        <div id="sheet-zone" style="display:none">
          <div class="sec-lbl">1 · Choose Worksheet</div>
          <div class="sheet-chips" id="sheet-chips"></div>
          <div class="form-grid cols-3">
            <div class="form-field"><label>Header Row</label><input class="form-control" type="number" min="1" id="opt-header" value="1"></div>
            <div class="form-field"><label>Skip Rows After Header</label><input class="form-control" type="number" min="0" id="opt-skip" value="0"></div>
            <div class="form-field"><label>Date Format</label><select class="form-control" id="opt-datefmt"><option value="auto">Auto-detect (DD/MM first)</option><option value="dmy">DD/MM/YYYY</option><option value="mdy">MM/DD/YYYY</option><option value="ymd">YYYY-MM-DD</option><option value="serial">Excel serial</option></select></div>
          </div>
          <div class="sec-lbl">2 · Map Columns</div>
          <div class="map-grid" id="map-grid"></div>
          <div class="sec-lbl">3 · Preview (first 8 mapped rows)</div>
          <div class="pv-wrap"><table class="pv-table" id="pv-table"></table></div>
          <div class="stat-chips" id="import-stats"></div>
        </div>
      </div>
      <div class="wz-foot">
        <button class="btn btn-ghost" onclick="goStep(1)"><i class="fa-solid fa-arrow-left"></i> Back</button>
        <span class="chip-count chip-muted" id="import-ready"><i class="fa-solid fa-circle-exclamation"></i> Missing: statement file</span>
        <div class="right">
          <button class="btn btn-ghost" onclick="leaveWizard()">Cancel</button>
          <button class="btn btn-deep" id="btn-import" disabled onclick="doImport()">Import &amp; Continue to Matching <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </div>
    </div>

    <div class="wz-pane" id="pane-3">
      <div class="card-body">
        <div class="match-bar" id="match-bar-w">
          <span class="txt"><span id="mb-book-w">0</span> book + <span id="mb-stmt-w">0</span> statement selected</span>
          <div class="right"><button class="btn btn-primary btn-sm" onclick="matchSelected()"><i class="fa-solid fa-link"></i> Match Selected</button><button class="btn btn-ghost btn-sm" onclick="clearSel()">Clear</button></div>
        </div>
        <div class="match-grid" id="wz-matcher"></div>
        <div class="sec-lbl" style="margin-top:20px">Suggestions</div>
        <div id="wz-suggestions"></div>
      </div>
      <div class="wz-foot">
        <button class="btn btn-ghost" onclick="goStep(2)"><i class="fa-solid fa-arrow-left"></i> Back</button>
        <div class="right"><button class="btn btn-primary" onclick="autoMatch()"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto Match</button><button class="btn btn-deep" onclick="goStep(4)">Continue to Review <i class="fa-solid fa-arrow-right"></i></button></div>
      </div>
    </div>

    <div class="wz-pane" id="pane-4">
      <div class="card-body">
        <div class="ws-grid" style="margin-top:0">
          <div class="col">
            <div><div class="sec-lbl" style="margin-top:0">Reconciliation Calculation</div><div id="wz-calc" class="card glass" style="padding:18px;border-radius:14px"></div></div>
            <div><div class="sec-lbl">Adjustments</div><div id="wz-adj-list" style="margin-bottom:12px"></div>
              <div class="adj-form">
                <div><label>Type</label><select class="form-control" id="adj-type-w"><option>Bank Fees</option><option>Interest Income</option><option>NSF Cheque</option><option>Deposit in Transit</option><option>Outstanding Cheque</option><option>Error Correction</option></select></div>
                <div><label>Sign</label><select class="form-control" id="adj-sign-w"><option value="1">Add (+)</option><option value="-1">Subtract (−)</option></select></div>
                <div><label>Side</label><select class="form-control" id="adj-side-w"><option value="book">Book</option><option value="statement">Statement</option></select></div>
                <div><label>Amount (K)</label><input class="form-control mono" id="adj-amt-w" type="number" step="0.01" placeholder="0.00"></div>
                <div><label>Account</label><select class="form-control" id="adj-acct-w"><option value="">—</option><option>6100 — Bank Charges</option><option>4100 — Interest Income</option><option>1100 — Accounts Receivable</option><option>2100 — Accounts Payable</option></select></div>
                <div><label>Description</label><input class="form-control" id="adj-desc-w" placeholder="Optional note"></div>
                <button class="btn btn-primary" onclick="addAdjustment(true)"><i class="fa-solid fa-plus"></i> Add</button>
              </div></div>
          </div>
          <div class="col"><div><div class="sec-lbl" style="margin-top:0">Balance Summary</div><div id="wz-summary" class="card glass" style="padding:18px;border-radius:14px"></div></div></div>
        </div>
      </div>
      <div class="wz-foot">
        <button class="btn btn-ghost" onclick="goStep(3)"><i class="fa-solid fa-arrow-left"></i> Back</button>
        <div class="right"><button class="btn btn-deep" onclick="goStep(5)">Continue to Complete <i class="fa-solid fa-arrow-right"></i></button></div>
      </div>
    </div>

    <div class="wz-pane" id="pane-5">
      <div class="card-body" id="finalize-body"></div>
      <div class="wz-foot">
        <button class="btn btn-ghost" onclick="goStep(4)"><i class="fa-solid fa-arrow-left"></i> Back</button>
        <div class="right"><button class="btn btn-deep" id="btn-finalize" onclick="finalize()"><i class="fa-solid fa-lock"></i> Finalize &amp; Lock Reconciliation</button></div>
      </div>
    </div>
  </div>
</div>

</main>

<!-- FINALIZE MODAL -->
<div class="modal-overlay" id="modal-finalize">
  <div class="modal">
    <div class="modal-header"><div class="modal-icon"><i class="fa-solid fa-lock"></i></div><div><div class="modal-title">Finalize Reconciliation?</div><div class="modal-subtitle" id="fin-sub"></div></div></div>
    <div class="modal-body">
      <p style="font-size:13px;line-height:1.65;color:var(--ink-soft)">Finalizing locks the period, posts adjustment entries to the general ledger, and marks remaining unmatched lines as carried forward. This action requires the <b>Reconcile</b> permission and is recorded in the audit trail.</p>
      <div class="info-banner warn-banner" style="margin:14px 0 0"><i class="fa-solid fa-triangle-exclamation"></i><span id="fin-warn"></span></div>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modal-finalize')">Cancel</button><button class="btn btn-deep" onclick="confirmFinalize()"><i class="fa-solid fa-check"></i> Yes, Finalize</button></div>
  </div>
</div>

<div class="toast-wrap" id="toasts"></div>

<script>
/* ═════════ STATE ═════════ */
const ACCOUNTS = {'1000':'Cash and Cash Equivalents','1020':'Stanbic Operating Account','1030':'Mobile Money Wallet'};
let recSeq = 4;
const S = {
  view:'index', tab:'recs', step:2, maxStep:2,
  activeRecId:null, showHistory:false,
  filters:{account:'', from:'', to:''},
  audit:[],
  searchBook:'', searchStmt:'', selBook:null, selStmt:null, suggestionsDismissed:[],
  wb:null, sheetName:null, columns:[], rows:[], fileName:null,
  map:{date:'',ref:'',desc:'',debit:'',credit:'',balance:'',amount:''},
  auto:{},
  recs:[]
};
const $ = id => document.getElementById(id);
const fmt = n => (Number(n)||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
const fmtK = n => 'K '+(n<0?'-':'')+fmt(Math.abs(n));
const dstr = d => (d instanceof Date && !isNaN(d)) ? d.toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'}) : '—';
const D = s => new Date(+s.slice(0,4), +s.slice(5,7)-1, +s.slice(8,10));
const R = () => S.recs.find(r=>r.id===S.activeRecId);
function audit(rec, msg){ S.audit.unshift({at:new Date(), rec:rec? (rec.account.code+' · '+rec.statementName) : '—', msg}); }

/* ═════════ SEED REGISTER ═════════ */
function seed(){
  const mk=(id,name,pend,opening,closing,status)=>({
    id, account:{code:'1000',name:ACCOUNTS['1000']}, statementName:name,
    period:{start:'2026-07-01', end:pend}, opening, closing, bookBalance:4000.00,
    stmtBalanceOverride:closing, status,
    statement:[], book:[], matches:[], adjustments:[], imports:[]
  });
  const r1=mk('r1','asdasdas','2026-11-02',622222.00,555551.00,'draft');
  const r2=mk('r2','were','2026-08-13',900000.00,6540000.00,'in-progress');
  const r3=mk('r3','15542','2026-07-31',1500000.00,8965420.00,'draft');
  const L=[['2026-07-01','','Opening Balance',0],['2026-07-02','ATM-0702-001','ATM Withdrawal',-150000],['2026-07-03','DEP-0703-014','Deposit - Customer Payment',325000],['2026-07-04','CHG-0704-001','Bank Charges',-12500],['2026-07-05','TRF-0705-008','Supplier Payment - ABC Supplies',-275000],['2026-07-07','DEP-0707-021','Deposit - Member Collections',450000],['2026-07-08','EFT-0708-004','Electronic Transfer - MFI Hub',-185000],['2026-07-10','INT-0710-001','Interest Received',18750],['2026-07-11','CHQ-10452','Cheque Payment - CHQ 10452',-95000],['2026-07-14','DEP-0714-033','Deposit - Customer Payment',275000],['2026-07-15','CHG-0715-002','Bank Charges',-8500],['2026-07-16','SO-0716-001','Standing Order - Rent',-120000],['2026-07-18','DEP-0718-045','Deposit - Member Collections',380000],['2026-07-21','CHQ-10453','Cheque Payment - CHQ 10453',-165000],['2026-07-22','EFT-0722-009','Electronic Transfer - Utilities',-72500],['2026-07-24','DEP-0724-051','Deposit - Customer Payment',210000],['2026-07-25','ATM-0725-003','ATM Withdrawal',-100000],['2026-07-28','CHG-0728-003','Bank Charges',-15000],['2026-07-29','DEP-0729-064','Deposit - Member Collections',295000],['2026-07-30','CHQ-10454','Cheque Payment - CHQ 10454',-220000],['2026-07-31','CHG-0731-004','Monthly Account Fee',-10000]];
  let bal=0;
  r2.statement = L.map((r,i)=>{ bal+=r[3]; return {id:'s'+(i+1), date:D(r[0]), ref:r[1], desc:r[2], amount:r[3], balance:bal, status:'bank-only'}; });
  const B=[['2026-08-03','DEP-0803-014','Customer payment — Metro Ltd',45000],['2026-08-07','DEP-0807-021','Customer payment — Chipanga Stores',27800],['2026-08-12','TRF-0812-005','Transfer received — Head Office',12500],['2026-08-18','DEP-0818-045','Customer payment — Nkhoma Pharma',9600],['2026-08-21','CHQ-20452','Cheque payment — Supplier ABC',-8900],['2026-08-26','CHQ-20453','Cheque payment — Utilities MW',-3250],['2026-08-29','CHQ-20454','Cheque payment — Rent Q3 (outstanding)',-15000],['2026-08-31','DEP-0831-064','Deposit in transit — Branch collections',22500]];
  r2.book = B.map((r,i)=>({id:'b'+(i+1), date:D(r[0]), ref:r[1], desc:r[2], amount:r[3], status:'unmatched'}));
  r2.imports=[{file:'sample_bank_statement_jul2026.xlsx', lines:21, at:new Date('2026-08-13T10:19:00'), by:'Elvis Seyama', status:'Imported'}];
  S.recs=[r1,r2,r3];
  audit(r2,'Reconciliation period created'); audit(r2,'Statement imported — 21 lines');
  audit(r1,'Reconciliation period created (draft)'); audit(r3,'Reconciliation period created (draft)');
}

/* ═════════ INDEX RENDER ═════════ */
function filteredRecs(){
  return S.recs.filter(r=>{
    if(!S.showHistory && r.status==='reconciled') return false;
    if(S.filters.account && r.account.code!==S.filters.account) return false;
    if(S.filters.from && r.period.end < S.filters.from) return false;
    if(S.filters.to && r.period.end > S.filters.to) return false;
    return true;
  });
}
function recDiff(r){ return (r.stmtBalanceOverride ?? r.closing) - r.bookBalance; }
function renderIndex(){
  const codes=[...new Set(S.recs.map(r=>r.account.code))];
  $('f-account').innerHTML = '<option value="">All accounts</option>'+codes.map(c=>`<option value="${c}" ${S.filters.account===c?'selected':''}>${c} — ${ACCOUNTS[c]||''}</option>`).join('');
  const recs=filteredRecs();
  $('period-chip').textContent = recs.length+' period'+(recs.length===1?'':'s');
  $('reg-chip').textContent = recs.length+' period'+(recs.length===1?'':'s');
  const stmt=recs.reduce((s,r)=>s+(r.stmtBalanceOverride??r.closing),0);
  const book=recs.reduce((s,r)=>s+r.bookBalance,0);
  const matched=recs.reduce((s,r)=>s+r.matches.length,0);
  const unmatched=recs.reduce((s,r)=>s+r.statement.filter(l=>l.status==='bank-only').length,0);
  const adjs=recs.reduce((s,r)=>s+r.adjustments.length,0);
  const diff=recs.reduce((s,r)=>s+recDiff(r),0);
  $('kpi-strip').innerHTML = `
    <div class="kpi"><div class="kpi-label">Statement Balance</div><div class="kpi-value">${fmt(stmt)}</div><div class="kpi-sub">per bank statement</div></div>
    <div class="kpi"><div class="kpi-label">Book Balance</div><div class="kpi-value">${fmt(book)}</div><div class="kpi-sub">per general ledger</div></div>
    <div class="kpi"><div class="kpi-label">Matched</div><div class="kpi-value green">${matched}</div><div class="kpi-sub">transactions</div></div>
    <div class="kpi"><div class="kpi-label">Unmatched</div><div class="kpi-value orange">${unmatched}</div><div class="kpi-sub">need attention</div></div>
    <div class="kpi"><div class="kpi-label">Adjustments</div><div class="kpi-value">${adjs}</div><div class="kpi-sub">pending</div></div>
    <div class="kpi warnhl"><div class="kpi-label">Difference</div><div class="kpi-value">K ${fmt(diff)}</div><div class="kpi-sub">across ${recs.length} period(s)</div></div>`;
  $('register-rows').innerHTML = recs.length ? recs.map(r=>`
    <tr class="clickable-row" onclick="continueRec('${r.id}')">
      <td><div class="cell-acct">${r.account.code} — ${r.account.name}</div></td>
      <td style="font-weight:600">${r.statementName}</td>
      <td>${dstr(D(r.period.end))}</td>
      <td class="td-right td-mono td-strong">${fmt(r.opening)}</td>
      <td class="td-right td-mono td-strong">${fmt(r.closing)}</td>
      <td class="td-right td-mono td-strong">${fmt(r.bookBalance)}</td>
      <td class="td-right td-mono td-strong">${fmt(r.stmtBalanceOverride??r.closing)}</td>
      <td class="td-right td-mono td-orange">${fmt(recDiff(r))}</td>
      <td>${statusPill(r.status)}</td>
      <td style="text-align:right"><button class="cont-link" onclick="event.stopPropagation();continueRec('${r.id}')">Continue →</button></td>
    </tr>`).join('') :
    `<tr><td colspan="10"><div class="mempty"><i class="fa-solid fa-scale-balanced"></i>No reconciliation periods match your filters.</div></td></tr>`;
  renderIndexTabs();
}
function statusPill(st){
  if(st==='reconciled') return '<span class="pill pill-done"><span class="pdot"></span> Reconciled</span>';
  if(st==='ready') return '<span class="pill pill-review"><span class="pdot"></span> Ready for Review</span>';
  if(st==='in-progress') return '<span class="pill pill-progress"><span class="pdot"></span> In Progress</span>';
  return '<span class="pill pill-draft"><span class="pdot"></span> Draft</span>';
}
function renderIndexTabs(){
  const imps=[]; S.recs.forEach(r=>r.imports.forEach(i=>imps.push({...i, rec:r})));
  $('stmts-body').innerHTML = imps.length ? `<div class="table-wrap" style="box-shadow:none"><div class="table-scroll"><table class="data-table"><thead><tr><th>File</th><th>Period</th><th class="td-right">Lines</th><th>Imported</th><th>By</th><th>Status</th></tr></thead><tbody>`+
    imps.map(i=>`<tr><td style="font-weight:700;color:var(--deep-2)"><i class="fa-solid fa-file-excel" style="color:var(--sec);margin-right:8px"></i>${i.file}</td><td>${i.rec.account.code} · ${i.rec.statementName}</td><td class="td-right td-mono td-strong">${i.lines}</td><td>${i.at.toLocaleString('en-US',{month:'short',day:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})}</td><td>${i.by}</td><td><span class="pill pill-done"><span class="pdot"></span> ${i.status}</span></td></tr>`).join('')+`</tbody></table></div></div>` :
    `<div class="mempty"><i class="fa-solid fa-file-import"></i>No statements imported yet.</div>`;
  const adjs=[]; S.recs.forEach(r=>r.adjustments.forEach(a=>adjs.push({...a, rec:r})));
  $('adjs-body').innerHTML = adjs.length ? `<div class="table-wrap" style="box-shadow:none"><div class="table-scroll"><table class="data-table"><thead><tr><th>Period</th><th>Type</th><th>Side</th><th class="td-right">Amount (K)</th><th>Account</th><th>Description</th></tr></thead><tbody>`+
    adjs.map(a=>`<tr><td>${a.rec.account.code} · ${a.rec.statementName}</td><td style="font-weight:700">${a.type}</td><td>${a.side==='book'?'Book':'Statement'} ${a.sign>0?'(+)':'(−)'}</td><td class="td-right td-mono td-strong">${fmt(a.amount)}</td><td class="td-mono">${a.account||'—'}</td><td>${a.desc||'—'}</td></tr>`).join('')+`</tbody></table></div></div>` :
    `<div class="mempty"><i class="fa-solid fa-sliders"></i>No adjustments recorded in any period.</div>`;
  const out=[]; S.recs.forEach(r=>{ r.statement.filter(l=>l.status==='bank-only').forEach(l=>out.push({rec:r,l,kind:'Bank-only'})); r.book.filter(b=>b.status==='unmatched').forEach(b=>out.push({rec:r,l:b,kind:'Book-only'})); });
  $('outst-body').innerHTML = out.length ? `<div class="table-wrap" style="box-shadow:none"><div class="table-scroll"><table class="data-table"><thead><tr><th>Period</th><th>Kind</th><th>Date</th><th>Reference</th><th>Description</th><th class="td-right">Amount (K)</th></tr></thead><tbody>`+
    out.slice(0,60).map(o=>`<tr><td>${o.rec.account.code} · ${o.rec.statementName}</td><td>${o.kind==='Bank-only'?'<span class="pill pill-bank"><span class="pdot"></span> Bank-only</span>':'<span class="pill pill-book"><span class="pdot"></span> Book-only</span>'}</td><td>${dstr(o.l.date)}</td><td class="td-mono">${o.l.ref||'—'}</td><td style="font-weight:600">${o.l.desc}</td><td class="td-right td-mono ${o.l.amount<0?'td-red':'td-strong'}">${fmt(o.l.amount)}</td></tr>`).join('')+`</tbody></table></div></div>
    <div class="info-banner" style="margin:14px 0 0"><i class="fa-solid fa-circle-info"></i><span>Full outstanding workflow (ageing, required actions, slide-overs) is specified in the Outstanding Items mockup — implement it here in production.</span></div>` :
    `<div class="mempty"><i class="fa-solid fa-circle-check"></i>Nothing outstanding — every line is matched or explained.</div>`;
  $('audit-body').innerHTML = S.audit.length ? S.audit.map(a=>`<div class="audit-item"><span class="audit-dot"></span><div class="audit-txt"><b>${a.rec}</b> — ${a.msg}<div class="audit-time">${a.at.toLocaleString('en-US',{month:'short',day:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})} · Elvis Seyama</div></div></div>`).join('') :
    `<div class="mempty"><i class="fa-solid fa-clock-rotate-left"></i>No events recorded yet.</div>`;
}
function setTab(t){
  S.tab=t;
  document.querySelectorAll('.ptab').forEach(b=>b.classList.toggle('on',b.dataset.tab===t));
  document.querySelectorAll('.idx-pane').forEach(p=>p.classList.remove('on'));
  $('pane-'+t).classList.add('on');
}
document.querySelectorAll('.ptab').forEach(b=>b.addEventListener('click',()=>setTab(b.dataset.tab)));
function applyFilters(){ S.filters={account:$('f-account').value, from:$('f-from').value, to:$('f-to').value}; renderIndex(); toast('Filters applied', filteredRecs().length+' period(s) match.'); }
function clearFilters(){ $('f-account').value=''; $('f-from').value=''; $('f-to').value=''; S.filters={account:'',from:'',to:''}; renderIndex(); toast('Filters cleared','Showing all periods.'); }
['f-account','f-from','f-to'].forEach(id=>$(id).addEventListener('change',applyFilters));
function refreshIndex(){ renderIndex(); toast('Refreshed','Register and KPIs recalculated.'); }
function toggleHistory(){ S.showHistory=!S.showHistory; $('hist-label').textContent = S.showHistory?'Hide finalized periods':'Show finalized periods'; renderIndex(); toast(S.showHistory?'History shown':'History hidden', S.showHistory?'Finalized periods included.':'Finalized periods excluded.'); closeMenu(); }
function exportRegister(){
  const recs=filteredRecs();
  const head=['Account','Statement','Period End','Opening','Closing','Book','Statement Bal','Difference','Status'];
  const csv=[head.join(','),...recs.map(r=>[r.account.code+' '+r.account.name,r.statementName,r.period.end,r.opening,r.closing,r.bookBalance,r.stmtBalanceOverride??r.closing,recDiff(r),r.status].map(f=>`"${String(f??'').replace(/"/g,'""')}"`).join(','))].join('\n');
  const blob=new Blob([csv],{type:'text/csv'}); const url=URL.createObjectURL(blob);
  const a=document.createElement('a'); a.href=url; a.download='reconciliation-register.csv'; a.click(); URL.revokeObjectURL(url);
  toast('Register exported', recs.length+' period(s) downloaded.'); closeMenu();
}
function runReport(name){ toast('Report queued', name+' — generating preview…'); }
function toggleMenu(e){ e.stopPropagation(); $('more-menu').classList.toggle('open'); }
function closeMenu(){ $('more-menu').classList.remove('open'); }
document.addEventListener('click',e=>{ if(!e.target.closest('.menu-wrap')) closeMenu(); });

/* ═════════ NAVIGATION ═════════ */
function showView(v){
  S.view=v;
  $('view-index').style.display = v==='index'?'':'none';
  $('view-workspace').style.display = v==='workspace'?'':'none';
  $('view-wizard').style.display = v==='wizard'?'':'none';
  if(v==='index') renderIndex();
  if(v==='workspace') renderWorkspace();
  window.scrollTo({top:0});
}
function continueRec(id){
  S.activeRecId=id;
  const r=R();
  if(r.status==='draft' && r.statement.length===0){ startImport(); return; }
  showView('workspace');
}
function newReconciliation(){
  const rec={ id:'r'+(recSeq++), account:{code:'1000',name:ACCOUNTS['1000']}, statementName:'New statement',
    period:{start:$('s1-start').value||'2026-08-01', end:$('s1-end').value||'2026-08-31'},
    opening:0, closing:0, bookBalance:4000.00, stmtBalanceOverride:null, status:'draft',
    statement:[], book:[], matches:[], adjustments:[], imports:[] };
  S.recs.unshift(rec); S.activeRecId=rec.id;
  audit(rec,'Reconciliation period created');
  openWizard(1);
}
function startImport(){
  if(!S.activeRecId || !R()){ S.activeRecId = S.recs[0]?.id || null; }
  if(!S.activeRecId){ newReconciliation(); return; }
  openWizard(R().statement.length?2:1);
}
function leaveWizard(){ const r=R(); showView(r && r.statement.length ? 'workspace' : 'index'); }

/* ═════════ WORKSPACE RENDER ═════════ */
function renderWorkspace(){
  const r=R(); if(!r){ showView('index'); return; }
  $('ws-crumb').textContent = r.account.code+' · '+r.statementName;
  $('ws-subtitle').textContent = `${r.account.code} — ${r.account.name} · ${dstr(D(r.period.start))} – ${dstr(D(r.period.end))} · MWK (K)`;
  const sp=$('ws-status-pill');
  sp.outerHTML = statusPill(r.status).replace('class="pill','id="ws-status-pill" class="pill');
  $('ws-lock').style.display = r.status==='reconciled'?'flex':'none';
  const c=calc();
  $('k-lines').textContent=r.statement.length;
  $('k-lines-sub').textContent=r.imports.length? 'from '+r.imports.length+' import(s)':'imported';
  $('k-matched').textContent=c.matched;
  $('k-unmatched').textContent=c.bankOnly+c.bookOnly;
  $('k-unmatched-sub').textContent=c.bankOnly+' bank / '+c.bookOnly+' book';
  $('k-diff').textContent=fmtK(c.diff);
  $('k-diff-card').className='kpi '+(Math.abs(c.diff)<0.01?'hl':'warnhl');
  $('ws-lines-chip').textContent=r.statement.length+' statement lines';
  $('ws-matcher').innerHTML=matcherHTML();
  $('wz-matcher').innerHTML=matcherHTML();
  const hasSel=S.selBook&&S.selStmt;
  ['match-bar','match-bar-w'].forEach(id=>$(id).classList.toggle('show',hasSel));
  $('mb-book').textContent=S.selBook?1:0; $('mb-stmt').textContent=S.selStmt?1:0;
  $('mb-book-w').textContent=S.selBook?1:0; $('mb-stmt-w').textContent=S.selStmt?1:0;
  $('ws-calc').innerHTML=calcHTML(); $('wz-calc').innerHTML=calcHTML();
  $('ws-summary').innerHTML=summaryHTML(); $('wz-summary').innerHTML=summaryHTML();
  $('ws-suggestions').innerHTML=suggestionsHTML(); $('wz-suggestions').innerHTML=suggestionsHTML();
  $('ws-adj-list').innerHTML=adjustmentsHTML(); $('wz-adj-list').innerHTML=adjustmentsHTML();
  $('ws-adj-chip').textContent=r.adjustments.length; $('ws-adj-count').textContent=r.adjustments.length+' recorded';
  $('ws-imports').innerHTML=importsHTML();
  if(S.step===5) $('finalize-body').innerHTML=finalizeHTML();
  $('wz-subtitle').textContent = $('ws-subtitle').textContent;
}
function renderAll(){ if(S.view==='index') renderIndex(); else renderWorkspace(); }

/* ═════════ MATCHING / CALC ═════════ */
function candidates(){
  const r=R(); const out=[];
  r.book.filter(b=>b.status==='unmatched').forEach(b=>{
    r.statement.filter(s=>s.status==='unmatched').forEach(s=>{
      if(Math.abs(b.amount - s.amount) > 0.01) return;
      const dd = Math.abs(b.date - s.date)/86400000;
      if(dd > 10) return;
      let conf = 96 - dd*3;
      if(b.ref && s.ref && (b.ref===s.ref || s.ref.includes(b.ref) || b.ref.includes(s.ref))) conf = 99;
      out.push({b,s,conf:Math.round(conf)});
    });
  });
  return out.sort((x,y)=>y.conf-x.conf);
}
function autoMatch(){
  const r=R(); if(!r) return;
  if(r.status==='reconciled'){ toast('Locked','This reconciliation is finalized.','warn'); return; }
  const cands = candidates().filter(c=>c.conf>=85);
  let n=0;
  cands.forEach(c=>{ if(c.b.status!=='unmatched'||c.s.status!=='unmatched') return; createMatch(c.b,c.s,'auto',c.conf); n++; });
  if(n) audit(r,'Auto-match created '+n+' pair(s)');
  toast(n?`Auto-matched ${n} pair${n>1?'s':''}`:'No new auto-matches', n?'Amount + reference/date confidence ≥ 85%.':'Try manual selection or review suggestions.', n?'ok':'warn');
  renderAll();
}
function createMatch(b,s,method,conf){ const r=R(); const m={id:'m'+(r.matches.length+1), b:b.id, s:s.id, method, conf}; r.matches.push(m); b.status='matched'; s.status='matched'; b.matchId=m.id; s.matchId=m.id; return m; }
function suggestions(){ return candidates().filter(c=>c.conf<85 && !S.suggestionsDismissed.includes(c.b.id+'|'+c.s.id)).slice(0,6); }
function acceptSuggestion(bid,sid,conf){ const r=R(); const b=r.book.find(x=>x.id===bid), s=r.statement.find(x=>x.id===sid); if(!b||!s)return; createMatch(b,s,'suggested',conf); audit(r,'Suggestion accepted '+b.ref+' ↔ '+s.ref); toast('Suggestion accepted', b.ref+' ↔ '+s.ref,'ok'); renderAll(); }
function dismissSuggestion(bid,sid){ S.suggestionsDismissed.push(bid+'|'+sid); renderAll(); }
function matchSelected(){ const r=R(); if(!S.selBook||!S.selStmt) return; const b=r.book.find(x=>x.id===S.selBook), s=r.statement.find(x=>x.id===S.selStmt); createMatch(b,s,'manual',100); audit(r,'Manual match '+b.ref+' ↔ '+s.ref); S.selBook=null; S.selStmt=null; toast('Matched manually', b.ref+' ↔ '+s.ref,'ok'); renderAll(); }
function unmatch(mid){ const r=R(); const m=r.matches.find(x=>x.id===mid); if(!m) return; const b=r.book.find(x=>x.id===m.b), s=r.statement.find(x=>x.id===m.s); if(b){b.status='unmatched';delete b.matchId;} if(s){s.status='unmatched';delete s.matchId;} r.matches=r.matches.filter(x=>x.id!==mid); toast('Match removed','Lines returned to unmatched pools.','warn'); renderAll(); }
function clearSel(){ S.selBook=null; S.selStmt=null; renderAll(); }
function pickBook(id){ S.selBook = S.selBook===id?null:id; renderAll(); }
function pickStmt(id){ S.selStmt = S.selStmt===id?null:id; renderAll(); }
function calc(){
  const r=R(); if(!r) return {book:0,stmt:0,adjBook:0,adjStmt:0,adjBookV:0,adjStmtV:0,diff:0,matched:0,bankOnly:0,bookOnly:0,adjCount:0};
  const adjBook = r.adjustments.filter(a=>a.side==='book').reduce((s,a)=>s+a.sign*a.amount,0);
  const adjStmt = r.adjustments.filter(a=>a.side==='statement').reduce((s,a)=>s+a.sign*a.amount,0);
  const stmtBal = r.stmtBalanceOverride ?? r.statement.reduce((s,l)=>s+l.amount,0);
  const adjBookV = r.bookBalance + adjBook;
  const adjStmtV = stmtBal + adjStmt;
  return {book:r.bookBalance, stmt:stmtBal, adjBook, adjStmt, adjBookV, adjStmtV, diff:adjBookV-adjStmtV,
          matched:r.matches.length, bankOnly:r.statement.filter(l=>l.status==='bank-only').length,
          bookOnly:r.book.filter(l=>l.status==='unmatched').length, adjCount:r.adjustments.length};
}
function addAdjustment(fromWizard){
  const r=R(); if(!r) return;
  const p = fromWizard?'-w':'';
  const amt = parseFloat($('adj-amt'+p).value);
  if(!amt || amt<=0){ toast('Invalid amount','Enter an adjustment amount greater than zero.','err'); return; }
  const type=$('adj-type'+p).value, sign=parseInt($('adj-sign'+p).value), side=$('adj-side'+p).value, account=$('adj-acct'+p).value, desc=$('adj-desc'+p).value;
  r.adjustments.push({id:'a'+(r.adjustments.length+1), type, sign, side, amount:amt, account, desc});
  $('adj-amt'+p).value=''; $('adj-desc'+p).value='';
  audit(r,'Adjustment added: '+type+' '+fmt(amt)+' ('+side+')');
  toast('Adjustment added', type+' recorded on '+side+' side.','ok');
  renderAll();
}
function removeAdjustment(id){ const r=R(); r.adjustments=r.adjustments.filter(a=>a.id!==id); audit(r,'Adjustment removed'); toast('Adjustment removed','','warn'); renderAll(); }

/* ═════════ RENDER FRAGMENTS ═════════ */
function matcherHTML(){
  const r=R(); if(!r) return '';
  const bk = r.book.filter(b=> (b.desc+b.ref).toLowerCase().includes(S.searchBook.toLowerCase()));
  const st = r.statement.filter(s=> (s.desc+s.ref).toLowerCase().includes(S.searchStmt.toLowerCase()));
  const rowB = b=>`<div class="mrow ${S.selBook===b.id?'sel':''} ${b.status==='matched'?'matched':''}" onclick="pickBook('${b.id}')">
      <span class="d">${dstr(b.date)}</span>
      <span class="desc"><span class="t">${b.desc}</span><span class="r">${b.ref}</span></span>
      <span class="amt ${b.amount<0?'neg':''}">${fmt(b.amount)}</span>
      <span>${b.status==='matched'?'<span class="pill pill-matched"><span class="pdot"></span> Matched</span>':'<span class="pill pill-book"><span class="pdot"></span> Book-only</span>'}</span>
      <button class="unm" onclick="event.stopPropagation();${b.matchId?`unmatch('${b.matchId}')`:''}" data-tip="Unmatch">${b.matchId?'<i class="fa-solid fa-link-slash"></i>':''}</button></div>`;
  const rowS = s=>`<div class="mrow ${S.selStmt===s.id?'sel':''} ${s.status==='matched'?'matched':''}" onclick="pickStmt('${s.id}')">
      <span class="d">${dstr(s.date)}</span>
      <span class="desc"><span class="t">${s.desc}</span><span class="r">${s.ref||'—'}</span></span>
      <span class="amt ${s.amount<0?'neg':''}">${fmt(s.amount)}</span>
      <span>${s.status==='matched'?'<span class="pill pill-matched"><span class="pdot"></span> Matched</span>':'<span class="pill pill-bank"><span class="pdot"></span> Bank-only</span>'}</span>
      <button class="unm" onclick="event.stopPropagation();${s.matchId?`unmatch('${s.matchId}')`:''}" data-tip="Unmatch">${s.matchId?'<i class="fa-solid fa-link-slash"></i>':''}</button></div>`;
  return `
   <div class="mpanel"><div class="mpanel-head"><span class="mpanel-title">Book Transactions</span><span class="mpanel-sub">ledger · ${r.book.filter(b=>b.status==='unmatched').length} open</span>
     <label class="mpanel-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="Search book…" oninput="S.searchBook=this.value;renderAll()"></label></div>
     <div class="mpanel-body">${bk.length?bk.map(rowB).join(''):`<div class="mempty"><i class="fa-solid fa-inbox"></i>No unmatched book transactions for this bank account.</div>`}</div></div>
   <div class="mpanel"><div class="mpanel-head"><span class="mpanel-title">Statement Lines</span><span class="mpanel-sub">imported · ${r.statement.length}</span>
     <label class="mpanel-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="Search statement…" oninput="S.searchStmt=this.value;renderAll()"></label></div>
     <div class="mpanel-body">${st.length?st.map(rowS).join(''):`<div class="mempty"><i class="fa-solid fa-file-import"></i>No statement lines yet — import a bank statement to begin.</div>`}</div></div>`;
}
function calcHTML(){
  const c=calc(); const good=Math.abs(c.diff)<0.01;
  return `
   <div class="calc-row"><span class="l">Book balance</span><span class="v">${fmtK(c.book)}</span></div>
   <div class="calc-row"><span class="l">Statement balance</span><span class="v">${fmtK(c.stmt)}</span></div>
   <div class="calc-row"><span class="l">Book adjustments</span><span class="v">${fmtK(c.adjBook)}</span></div>
   <div class="calc-row"><span class="l">Statement adjustments</span><span class="v">${fmtK(c.adjStmt)}</span></div>
   <div class="calc-row"><span class="l">Adjusted book</span><span class="v">${fmtK(c.adjBookV)}</span></div>
   <div class="calc-row"><span class="l">Adjusted bank</span><span class="v">${fmtK(c.adjStmtV)}</span></div>
   <div class="calc-row total"><span class="l">Difference</span><span class="v ${good?'good':'bad'}">${fmtK(c.diff)}</span></div>
   <div style="margin-top:12px" class="info-banner ${good?'ok-banner':'warn-banner'}"><i class="fa-solid ${good?'fa-circle-check':'fa-triangle-exclamation'}"></i><span>${good?'In balance — adjusted book equals adjusted bank.':'Out of balance — difference of '+fmtK(c.diff)+' remains. Match lines or add adjustments.'}</span></div>`;
}
function summaryHTML(){
  const c=calc();
  return `
   <div class="calc-row"><span class="l">Book Balance</span><span class="v">${fmt(c.book)}</span></div>
   <div class="calc-row"><span class="l">Statement Balance</span><span class="v">${fmt(c.stmt)}</span></div>
   <div class="calc-row"><span class="l">Adjustments</span><span class="v">${c.adjCount}</span></div>
   <div class="calc-row"><span class="l">Matched</span><span class="v" style="color:var(--green)">${c.matched}</span></div>
   <div class="calc-row"><span class="l">Unmatched bank lines</span><span class="v" style="color:var(--red-2)">${c.bankOnly}</span></div>
   <div class="calc-row"><span class="l">Unmatched book lines</span><span class="v" style="color:var(--blue)">${c.bookOnly}</span></div>`;
}
function suggestionsHTML(){
  const s=suggestions();
  if(!s.length) return `<div class="mempty" style="padding:26px"><i class="fa-solid fa-wand-magic-sparkles"></i>No auto-match suggestions. Match statement lines manually above.</div>`;
  return s.map(c=>`<div class="sugg"><span class="conf">${c.conf}%</span>
    <span class="pair"><b>${c.b.desc}</b><span class="mono">${c.b.ref} · ${dstr(c.b.date)} · ${fmt(c.b.amount)}</span><br>↔ <b>${c.s.desc}</b><span class="mono">${c.s.ref||'—'} · ${dstr(c.s.date)}</span></span>
    <button class="btn btn-primary btn-xs" onclick="acceptSuggestion('${c.b.id}','${c.s.id}',${c.conf})">Accept</button>
    <button class="btn btn-ghost btn-xs" onclick="dismissSuggestion('${c.b.id}','${c.s.id}')">Dismiss</button></div>`).join('');
}
function adjustmentsHTML(){
  const r=R(); if(!r) return '';
  if(!r.adjustments.length) return `<div class="mempty" style="padding:22px"><i class="fa-solid fa-sliders"></i>No adjustments. Use the form below when a statement line or book entry needs correcting.</div>`;
  return `<table class="pv-table" style="font-size:12px"><thead><tr><th>Type</th><th>Side</th><th class="td-right">Amount (K)</th><th>Account</th><th>Description</th><th></th></tr></thead><tbody>`+
    r.adjustments.map(a=>`<tr><td><b>${a.type}</b></td><td>${a.side==='book'?'Book':'Statement'} ${a.sign>0?'(+)':'(−)'}</td><td class="mono td-right" style="font-weight:700">${fmt(a.amount)}</td><td class="mono">${a.account||'—'}</td><td>${a.desc||'—'}</td><td style="text-align:right"><button class="btn btn-danger-ghost btn-xs" onclick="removeAdjustment('${a.id}')"><i class="fa-solid fa-trash-can"></i></button></td></tr>`).join('')+`</tbody></table>`;
}
function importsHTML(){
  const r=R(); if(!r) return '';
  if(!r.imports.length) return `<div class="mempty" style="padding:22px"><i class="fa-solid fa-file-import"></i>No statements imported yet.</div>`;
  return r.imports.map(im=>`<div class="imp"><i class="fa-solid fa-file-excel"></i><div style="min-width:0"><div class="fn">${im.file}</div><div class="meta">${im.at.toLocaleString('en-US',{month:'short',day:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})} · ${im.by}</div></div><span class="lines">${im.lines} lines</span><span class="pill pill-done"><span class="pdot"></span> ${im.status}</span></div>`).join('');
}
function finalizeHTML(){
  const c=calc(); const good=Math.abs(c.diff)<0.01;
  return `<div style="text-align:center;padding:8px 0 4px"><div class="big-check"><i class="fa-solid fa-flag-checkered"></i></div>
      <div style="font-size:18px;font-weight:800;color:var(--deep-2)">Ready to finalize</div>
      <div style="font-size:12.5px;color:var(--ink-soft);margin-top:6px">Review the checklist below, then lock this reconciliation.</div></div>
    <div class="fin-list">
      <div class="fin-item"><i class="fa-solid ${good?'fa-circle-check i-ok':'fa-triangle-exclamation i-warn'}"></i><span>Difference is <b>${fmtK(c.diff)}</b> — ${good?'balanced and ready to lock.':'NOT balanced; finalize will carry the difference forward.'}</span></div>
      <div class="fin-item"><i class="fa-solid fa-link i-ok"></i><span><b>${c.matched}</b> matched pairs will be locked.</span></div>
      <div class="fin-item"><i class="fa-solid fa-circle-question i-warn"></i><span><b>${c.bankOnly}</b> bank-only and <b>${c.bookOnly}</b> book-only lines will carry to the next period.</span></div>
      <div class="fin-item"><i class="fa-solid fa-sliders i-info"></i><span><b>${c.adjCount}</b> adjustment(s) will post to the general ledger.</span></div>
    </div>`;
}

/* ═════════ WIZARD ═════════ */
function openWizard(step){
  if(!R()) newReconciliation();
  bindStep1();
  showView('wizard');
  goStep(step||2);
}
function bindStep1(){
  const r=R(); if(!r) return;
  $('s1-account').value=r.account.code;
  $('s1-start').value=r.period.start; $('s1-end').value=r.period.end;
  $('s1-open').value=r.opening.toFixed(2);
  $('s1-close').value=r.closing? r.closing.toFixed(2):'';
}
['s1-account','s1-start','s1-end','s1-open','s1-close'].forEach(id=>$(id).addEventListener('change',()=>{
  const r=R(); if(!r) return;
  const code=$('s1-account').value;
  r.account={code, name:ACCOUNTS[code]||code};
  r.period={start:$('s1-start').value, end:$('s1-end').value};
  r.opening=parseFloat($('s1-open').value)||0;
  r.closing=parseFloat($('s1-close').value)||0;
  if($('s1-close').value) r.stmtBalanceOverride=r.closing;
  renderAll();
}));
function goStep(n){
  const r=R();
  if(n>=3 && (!r || r.statement.length===0)){ toast('No statement lines','Import a bank statement first.','warn'); n=2; }
  if(n>S.maxStep){ const seq=(n===S.maxStep+1)&&r&&r.statement.length>0; if(!seq){ toast('Step locked','Complete the previous step first.','warn'); return; } }
  S.maxStep=Math.max(S.maxStep,n); S.step=n;
  document.querySelectorAll('.wz-pane').forEach(p=>p.classList.remove('on'));
  $('pane-'+n).classList.add('on');
  document.querySelectorAll('#stepper .step').forEach(st=>{
    const sn=+st.dataset.step;
    st.classList.toggle('active',sn===n); st.classList.toggle('done',sn<n); st.classList.toggle('locked',sn>S.maxStep);
    st.querySelector('.step-dot').innerHTML = sn<n? '<i class="fa-solid fa-check"></i>' : sn;
  });
  document.querySelectorAll('#stepper .step-line').forEach((l,i)=> l.classList.toggle('done', i+1<n));
  renderAll();
}

/* ═════════ PARSING / MAPPING ═════════ */
function sampleRows(){ return [
  ['Transaction Date','Reference','Description','Withdrawal','Deposit','Balance'],
  ['03/08/2026','DEP-0803-014','Deposit - Customer Payment','','45000.00','45000.00'],
  ['07/08/2026','DEP-0807-021','Deposit - Customer Payment','','27800.00','72800.00'],
  ['12/08/2026','TRF-0812-005','Electronic Transfer - Head Office','','12500.00','85300.00'],
  ['15/08/2026','CHG-0815-002','Bank Charges','1250.00','','84050.00'],
  ['18/08/2026','DEP-0818-045','Deposit - Customer Payment','','9600.00','93650.00'],
  ['21/08/2026','CHQ-20452','Cheque Payment','8900.00','','84750.00'],
  ['26/08/2026','CHQ-20453','Cheque Payment','3250.00','','81500.00'],
  ['30/08/2026','INT-0830-001','Interest Received','','3750.00','85250.00'] ]; }
function loadSample(){
  S.wb={sheets:{'Statement Aug 2026':sampleRows(),'Cover Sheet':[['Bank','ABC'],['Account','1000']]}};
  S.fileName='sample_bank_statement_aug2026.xlsx';
  onWorkbookReady();
  toast('Sample loaded','2 sheets detected — choose one and map columns.','ok');
}
function onFile(file){
  if(!file) return;
  if(file.size > 10*1024*1024){ toast('File too large','Maximum size is 10 MB.','err'); return; }
  const ext = file.name.split('.').pop().toLowerCase();
  if(!['csv','xlsx','xls'].includes(ext)){ toast('Unsupported format','Use CSV, XLSX or XLS.','err'); return; }
  if(typeof XLSX === 'undefined'){ toast('Parser unavailable','SheetJS failed to load — use the sample statement.','warn'); return; }
  const reader = new FileReader();
  reader.onload = e => {
    try{
      const wb = XLSX.read(new Uint8Array(e.target.result), {type:'array', cellDates:true});
      S.wb = {sheets:{}};
      wb.SheetNames.forEach(n=>{ S.wb.sheets[n] = XLSX.utils.sheet_to_json(wb.Sheets[n], {header:1, raw:false, defval:''}); });
      S.fileName = file.name;
      onWorkbookReady();
      toast('File parsed', Object.keys(S.wb.sheets).length+' worksheet(s) found.','ok');
    }catch(err){ toast('Parse error','Could not read this file: '+err.message,'err'); }
  };
  reader.readAsArrayBuffer(file);
}
function onWorkbookReady(){
  try{
    $('parse-state').textContent = 'Parsed: '+S.fileName;
    $('dz-file-box').innerHTML = `<div class="dz-file"><i class="fa-solid fa-file-excel"></i><div><div class="fn">${S.fileName}</div><div class="fm">${Object.keys(S.wb.sheets).length} sheet(s) · ready to map</div></div><button class="rm" onclick="clearFile()" data-tip="Remove"><i class="fa-solid fa-xmark"></i></button></div>`;
    const names = Object.keys(S.wb.sheets);
    S.sheetName = names[0];
    $('sheet-zone').style.display='';
    selectSheet(names[0]);
  }catch(e){ console.error('onWorkbookReady:',e); }
  validateImport();
}
function clearFile(){ S.wb=null; S.fileName=null; S.columns=[]; S.rows=[]; S.sheetName=null;
  $('sheet-zone').style.display='none'; $('dz-file-box').innerHTML=''; $('parse-state').textContent='No file parsed'; validateImport(); }
function renderSheetChips(){
  $('sheet-chips').innerHTML = Object.keys(S.wb.sheets).map(n=>{
    const r = (S.wb.sheets[n]||[]).filter(x=>Array.isArray(x)&&x.some(c=>String(c).trim()!==''));
    return `<button class="sheet-chip ${n===S.sheetName?'on':''}" onclick="selectSheet(${JSON.stringify(n)})"><i class="fa-solid fa-table"></i> ${n} <span class="n">${r.length} rows</span></button>`;
  }).join('');
}
function selectSheet(name){
  try{
    S.sheetName=name; renderSheetChips();
    const all = (S.wb.sheets[name]||[]).filter(r=>Array.isArray(r)&&r.some(c=>String(c).trim()!==''));
    const h = Math.max(1, parseInt($('opt-header').value)||1);
    S.columns = (all[h-1]||[]).map((c,i)=>String(c).trim()||('Column '+(i+1)));
    S.rows = all.slice(h + (Math.max(0, parseInt($('opt-skip').value)||0)));
    autoMap(); renderMapping(); renderPreview();
  }catch(e){ console.error('selectSheet:',e); }
  validateImport();
}
const FIELDS = [
  {k:'date',lbl:'Date',icon:'fa-calendar-day',req:true},
  {k:'ref',lbl:'Reference',icon:'fa-hashtag',req:false},
  {k:'desc',lbl:'Description',icon:'fa-align-left',req:false},
  {k:'debit',lbl:'Debit (Withdrawal)',icon:'fa-arrow-down',req:false},
  {k:'credit',lbl:'Credit (Deposit)',icon:'fa-arrow-up',req:false},
  {k:'amount',lbl:'Amount (signed, alternative)',icon:'fa-plus-minus',req:false},
  {k:'balance',lbl:'Balance',icon:'fa-scale-balanced',req:false}];
const GUESS = {
  date:['date','txn','transaction','value','posting','day'],
  ref:['ref','reference','no.','no','number','cheque','chq','trace','voucher'],
  desc:['desc','details','narration','particulars','memo','narr'],
  debit:['withdrawal','debit','dr','paidout','outflow','withdraw'],
  credit:['deposit','credit','cr','paidin','inflow','lodgement'],
  balance:['balance','bal','running'],
  amount:['amount','net']};
function autoMap(){
  S.map={date:'',ref:'',desc:'',debit:'',credit:'',balance:'',amount:''}; S.auto={};
  const norm = c => c.toLowerCase().replace(/[^a-z.]/g,'');
  FIELDS.forEach(f=>{ let best=null;
    (S.columns||[]).forEach(col=>{ const n=norm(col); if(GUESS[f.k].some(k=>n.includes(k))){ if(!best) best=col; } });
    if(best && !Object.values(S.map).includes(best)){ S.map[f.k]=best; S.auto[f.k]=true; } });
  if(S.map.debit && S.map.credit) S.map.amount='';
}
function renderMapping(){
  $('map-grid').innerHTML = FIELDS.map(f=>{
    const val=S.map[f.k]; const idx=val?S.columns.indexOf(val):-1;
    const sample=(val&&S.rows[0]&&idx>=0)?String(S.rows[0][idx]??''):'';
    return `<div class="map-row ${val?'mapped':''}">
      <div class="map-field"><i class="fa-solid ${f.icon}"></i> ${f.lbl} ${f.req?'<span class="req">*</span>':''}${val&&S.auto[f.k]?'<span class="auto-badge">auto</span>':''}</div>
      <div class="map-arrow"><i class="fa-solid fa-arrow-right-long"></i></div>
      <select class="form-control" style="padding:8px 10px;font-size:12.5px" onchange="setMap('${f.k}',this.value)">
        <option value="">— not mapped —</option>${(S.columns||[]).map(c=>`<option ${c===val?'selected':''}>${c}</option>`).join('')}
      </select>
      <div class="map-sample">${val?`e.g. <b>${sample.slice(0,26)||'—'}</b>`:'<span style="opacity:.55">optional</span>'}</div></div>`;
  }).join('');
}
function setMap(k,v){ S.map[k]=v; S.auto[k]=false;
  if((k==='debit'||k==='credit')&&v) S.map.amount='';
  if(k==='amount'&&v){ S.map.debit=''; S.map.credit=''; }
  renderMapping(); renderPreview(); validateImport(); }
function parseDate(v){
  if(v instanceof Date && !isNaN(v)) return v;
  if(v==null) return null;
  const s=String(v).trim(); if(!s) return null;
  const mode=$('opt-datefmt').value; let m;
  if(mode==='serial'||(/^\d{4,5}(\.\d+)?$/.test(s)&&+s>20000&&+s<80000)){
    const d=new Date(Math.round((Number(s)-25569)*86400*1000));
    return isNaN(d)?null:new Date(d.getTime()+d.getTimezoneOffset()*60000); }
  m=s.match(/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/); if(m) return new Date(+m[1],+m[2]-1,+m[3]);
  m=s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/);
  if(m){ let a=+m[1],b=+m[2],y=+m[3]; if(y<100)y+=2000;
    if(mode==='mdy'){const t=a;a=b;b=t;} else if(mode!=='dmy'&&a<=12&&b>12){const t=a;a=b;b=t;}
    return new Date(y,b-1,a); }
  const d=new Date(s); return isNaN(d)?null:d;
}
function parseAmt(v){
  if(v===''||v==null) return 0;
  if(typeof v==='number') return v;
  let s=String(v).trim().replace(/[Kk\s,]/g,''); let neg=false;
  if(/^\(.*\)$/.test(s)){neg=true;s=s.slice(1,-1);}
  if(/CR$/i.test(s)) s=s.slice(0,-2);
  if(/DR$/i.test(s)){neg=true;s=s.slice(0,-2);}
  const n=parseFloat(s.replace(/[^0-9.\-]/g,'')); return isNaN(n)?0:(neg?-n:n);
}
function mappedRows(){
  if(!S.wb||!S.sheetName||!S.rows) return [];
  try{
    return S.rows.map((r,i)=>{
      const g=k=>{const x=S.columns.indexOf(S.map[k]); return x>=0?r[x]:'';};
      const date=parseDate(g('date'));
      const debit=parseAmt(g('debit')), credit=parseAmt(g('credit'));
      const amount=S.map.amount?parseAmt(g('amount')):(credit-debit);
      return {i:i+1,date,ref:String(g('ref')||'').trim(),desc:String(g('desc')||'').trim(),debit,credit,amount,balance:parseAmt(g('balance'))};
    }).filter(r=>r.date!==null);
  }catch(e){ console.error('mappedRows:',e); return []; }
}
function renderPreview(){
  const rows=mappedRows().slice(0,8);
  $('pv-table').innerHTML = `<thead><tr><th>#</th><th>Date</th><th>Reference</th><th>Description</th><th class="td-right">Debit</th><th class="td-right">Credit</th><th class="td-right">Amount (K)</th><th class="td-right">Balance</th></tr></thead><tbody>`+
    (rows.length? rows.map(r=>`<tr><td class="rn">${r.i}</td><td>${dstr(r.date)}</td><td class="mono">${r.ref||'—'}</td><td>${r.desc||'—'}</td><td class="mono td-right ${r.debit?'neg':''}">${r.debit?fmt(r.debit):''}</td><td class="mono td-right ${r.credit?'pos':''}">${r.credit?fmt(r.credit):''}</td><td class="mono td-right ${r.amount<0?'neg':'pos'}">${fmt(r.amount)}</td><td class="mono td-right">${r.balance?fmt(r.balance):''}</td></tr>`).join('') : `<tr><td colspan="8" style="text-align:center;padding:26px;color:var(--ink-muted)">Map at least the Date column to preview rows.</td></tr>`)+`</tbody>`;
  const all=mappedRows();
  const debits=all.reduce((s,r)=>s+r.debit,0), credits=all.reduce((s,r)=>s+r.credit,0);
  const dates=all.map(r=>r.date).sort((a,b)=>a-b);
  $('import-stats').innerHTML = all.length? `
    <div class="st-chip"><i class="fa-solid fa-list-ol"></i> Lines detected <b>${all.length}</b></div>
    <div class="st-chip"><i class="fa-solid fa-arrow-down"></i> Total withdrawals <b>${fmt(debits)}</b></div>
    <div class="st-chip"><i class="fa-solid fa-arrow-up"></i> Total deposits <b>${fmt(credits)}</b></div>
    <div class="st-chip"><i class="fa-solid fa-calendar"></i> Range <b>${dstr(dates[0])} – ${dstr(dates[dates.length-1])}</b></div>
    <div class="st-chip"><i class="fa-solid fa-scale-balanced"></i> Closing balance <b>${fmt(all[all.length-1]?.balance||credits-debits)}</b></div>` : '';
}
function validateImport(){
  const reasons=[];
  if(!S.wb) reasons.push('statement file');
  if(!S.map.date) reasons.push('Date mapping');
  if(!(S.map.amount||S.map.debit||S.map.credit)) reasons.push('Debit/Credit or Amount mapping');
  const n=mappedRows().length;
  if(S.wb&&n===0) reasons.push('no readable rows (check header row / date format)');
  const ok=reasons.length===0;
  const btn=$('btn-import'); if(btn) btn.disabled=!ok;
  const chip=$('import-ready');
  if(chip){ chip.className='chip-count '+(ok?'':'chip-muted');
    chip.innerHTML = ok ? '<i class="fa-solid fa-circle-check"></i> Ready to import — '+n+' line'+(n===1?'':'s')
                        : '<i class="fa-solid fa-circle-exclamation"></i> Missing: '+reasons.join(' · '); }
  return ok;
}
function doImport(){
  const r=R(); if(!r) return;
  const rows=mappedRows();
  if(!rows.length){ toast('Nothing to import','No readable rows found.','err'); return; }
  rows.forEach(row=>{ r.statement.push({id:'s'+(r.statement.length+1), date:row.date, ref:row.ref, desc:row.desc||'(no description)', amount:row.amount, balance:row.balance, status:'bank-only'}); });
  const closing = rows[rows.length-1]?.balance || rows.reduce((s,x)=>s+x.amount,0);
  if(!$('s1-close').value) $('s1-close').value = closing.toFixed(2);
  r.closing = parseFloat($('s1-close').value)||closing;
  r.stmtBalanceOverride = r.closing;
  r.statementName = (S.fileName||'sample').replace(/\.[^.]+$/,'');
  if(r.status==='draft') r.status='in-progress';
  r.imports.unshift({file:S.fileName||'sample', lines:rows.length, at:new Date(), by:'Elvis Seyama', status:'Imported'});
  S.maxStep=Math.max(S.maxStep,4);
  audit(r,'Statement imported — '+rows.length+' lines ('+(S.fileName||'sample')+')');
  toast('Statement imported', rows.length+' lines added to the matcher.','ok');
  goStep(3); renderAll();
}

/* ═════════ STATUS / FINALIZE ═════════ */
function markReady(){ const r=R(); if(!r) return; r.status='ready'; audit(r,'Marked ready for review'); toast('Marked ready for review','A reviewer can now finalize.','ok'); renderAll(); }
function finalize(){
  const r=R(); if(!r) return;
  if(r.status==='reconciled'){ toast('Already finalized','','warn'); return; }
  $('fin-sub').textContent=r.account.code+' — '+r.account.name+' · '+r.statementName;
  const c=calc();
  $('fin-warn').innerHTML = Math.abs(c.diff)<0.01 ? 'Balanced — locking will post '+c.adjCount+' adjustment(s).' : 'You still have a difference of '+fmtK(c.diff)+' and unmatched lines — they will be carried to the next period.';
  openModal('modal-finalize');
}
function confirmFinalize(){
  const r=R(); r.status='reconciled'; S.maxStep=5;
  audit(r,'Reconciliation FINALIZED & locked');
  closeModal('modal-finalize');
  toast('Reconciliation finalized','Period locked and adjustments posted to the ledger.','ok');
  showView('index');
}
function openModal(id){ $(id).classList.add('open'); document.body.classList.add('modal-open'); }
function closeModal(id){ $(id).classList.remove('open'); document.body.classList.remove('modal-open'); }
document.querySelectorAll('.modal-overlay').forEach(ov=>ov.addEventListener('mousedown',e=>{if(e.target===ov){ov.classList.remove('open');document.body.classList.remove('modal-open');}}));
document.addEventListener('keydown',e=>{if(e.key==='Escape'){document.querySelectorAll('.modal-overlay.open').forEach(o=>o.classList.remove('open'));document.body.classList.remove('modal-open');closeMenu();
  document.querySelectorAll('.card.fs').forEach(c=>setFs(c,false));}});

/* ═════════ FULLSCREEN (reparent to body) ═════════ */
function setFs(card, on){
  if(!card) return;
  const isOn = card.classList.contains('fs');
  if(on && !isOn){
    const anchor = document.createComment('fs-anchor');
    card.parentNode.insertBefore(anchor, card);
    card._fsAnchor = anchor;
    document.body.appendChild(card);
    card.classList.add('fs');
  } else if(!on && isOn){
    card.classList.remove('fs');
    const anchor = card._fsAnchor;
    if(anchor && anchor.parentNode){ anchor.parentNode.insertBefore(card, anchor); anchor.remove(); }
    card._fsAnchor = null;
  }
  const b = card.querySelector('.fs-btn');
  if(b){
    b.innerHTML = on ? '<i class="fa-solid fa-compress"></i> Collapse' : '<i class="fa-solid fa-expand"></i> Expand';
    b.setAttribute('data-tip', on ? 'Collapse to page width' : 'Expand to full width');
  }
  document.body.classList.toggle('locked', on);
}
function toggleFs(btn){
  const card = btn.closest('.card');
  setFs(card, !card.classList.contains('fs'));
}

/* ═════════ TOASTS / WIRING / BOOT ═════════ */
function toast(title,sub,type){
  const t=document.createElement('div'); t.className='toast '+(type||'ok');
  const icon=type==='err'?'fa-circle-exclamation':type==='warn'?'fa-triangle-exclamation':'fa-circle-check';
  t.innerHTML=`<i class="fa-solid ${icon}"></i><div><div class="t-title">${title}</div><div class="t-sub">${sub||''}</div></div>`;
  $('toasts').appendChild(t);
  setTimeout(()=>{t.style.transition='opacity .4s, transform .4s';t.style.opacity='0';t.style.transform='translateY(8px)';setTimeout(()=>t.remove(),400);},3400);
}
$('file-input').addEventListener('change',e=>onFile(e.target.files[0]));
const dz=$('dropzone');
['dragenter','dragover'].forEach(ev=>dz.addEventListener(ev,e=>{e.preventDefault();dz.classList.add('drag');}));
['dragleave','drop'].forEach(ev=>dz.addEventListener(ev,e=>{e.preventDefault();dz.classList.remove('drag');}));
dz.addEventListener('drop',e=>onFile(e.dataTransfer.files[0]));
['opt-header','opt-skip','opt-datefmt'].forEach(id=>{ const el=$(id);
  const rerun=()=>{ if(S.wb&&S.sheetName) selectSheet(S.sheetName); };
  el.addEventListener('input',rerun); el.addEventListener('change',rerun); });
document.querySelectorAll('.module-item').forEach(el=>el.addEventListener('click',e=>{if(el.getAttribute('href')==='#'){e.preventDefault();document.querySelectorAll('.module-item').forEach(i=>i.classList.remove('active'));el.classList.add('active');}}));
seed();
renderIndex();
</script>
</body>
</html>
```

### MOCKUP 2 — `outstanding-items.html` (Control Centre: tabs, matcher modal, quick journal modal, slide-overs, reports, audit)

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Outstanding Items — Bank Reconciliation | CamelotBooks ERP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root{
    --deep-1:#17565D; --deep-2:#0C3539; --deep-3:#0A2E32;
    --row1:#11454B; --row2:#DCE6E7;
    --sec:#107C7B; --sec-2:#0C7E7D;
    --ink:#0B2A2D; --ink-soft:#52696B; --ink-muted:#5F7A7C;
    --mist-a:#F4F8F8; --mist-b:#E8F0F0; --line:#E2ECEC; --border:#DCEAEA;
    --card:rgba(255,255,255,.75);
    --shadow-card:0 1px 2px rgba(10,42,46,.05),0 12px 32px -8px rgba(10,42,46,.10),0 2px 6px rgba(10,42,46,.04);
    --shadow-card-hov:0 2px 4px rgba(10,42,46,.06),0 20px 46px -12px rgba(10,42,46,.18),0 4px 10px rgba(10,42,46,.06);
    --green:#15803D; --warn:#D97706; --red:#DC2626; --red-2:#B91C1C;
    --tint:#DFF7F6; --mint:#7FD1C0;
    --green-tint:rgba(21,128,61,.10); --warn-tint:rgba(217,119,6,.12); --red-tint:rgba(220,38,38,.10);
    --blue:#1D6FB8; --blue-tint:rgba(29,111,184,.10);
    --orange:#EA580C; --orange-tint:rgba(234,88,12,.12);
    --purple:#7C3AED; --purple-tint:rgba(124,58,237,.10);
    --sec-ring:rgba(16,124,123,.16);
    --teal-btn-shadow:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);
    --cta-btn-shadow:0 1px 2px rgba(6,32,35,.3),0 10px 20px -10px rgba(12,53,57,.6),inset 0 1px 0 rgba(255,255,255,.12);
    --r-md:12px; --r-lg:16px; --r-xl:20px;
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',sans-serif;color:var(--ink);min-height:100vh;overflow-x:hidden;
    background:radial-gradient(1100px 480px at 88% -12%, rgba(127,209,192,.28), transparent 60%),
      radial-gradient(900px 420px at -8% -4%, rgba(16,124,123,.12), transparent 55%),
      linear-gradient(180deg,var(--mist-a) 0%,#ECF4F4 45%,var(--mist-b) 100%);
    background-attachment:fixed;}
  body.locked,body.modal-open{overflow:hidden}
  ::selection{background:var(--tint);color:var(--deep-2)}
  .glass{background:var(--card);backdrop-filter:blur(14px) saturate(1.15);-webkit-backdrop-filter:blur(14px) saturate(1.15)}
  @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  @keyframes panelIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
  @keyframes pulseMint{0%{box-shadow:0 0 0 0 rgba(127,209,192,.6)}70%{box-shadow:0 0 0 7px rgba(127,209,192,0)}100%{box-shadow:0 0 0 0 rgba(127,209,192,0)}}
  @keyframes toastIn{from{opacity:0;transform:translateY(12px) scale(.96)}to{opacity:1;transform:none}}
  @keyframes rowIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
  @keyframes popIn{0%{transform:scale(.6);opacity:0}70%{transform:scale(1.12)}100%{transform:scale(1);opacity:1}}
  .anim-1{animation:fadeUp .4s ease both;animation-delay:.05s}
  .anim-2{animation:fadeUp .4s ease both;animation-delay:.12s}
  .anim-3{animation:fadeUp .4s ease both;animation-delay:.19s}

  /* chrome */
  .nav-inner{max-width:1720px;margin:0 auto;padding:0 32px;width:100%}
  .topnav{position:fixed;top:0;left:0;right:0;z-index:100;height:60px;display:flex;align-items:center;background:linear-gradient(180deg,var(--row1) 0%,var(--deep-2) 100%);box-shadow:0 2px 18px rgba(10,46,50,.35),inset 0 1px 0 rgba(255,255,255,.08)}
  .topnav::after{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(600px 120px at 82% 0%,rgba(127,209,192,.16),transparent 70%)}
  .topnav .nav-inner{display:flex;align-items:center;position:relative;z-index:1}
  .topnav-brand{display:flex;align-items:center;gap:10px;padding-right:24px;border-right:1px solid rgba(255,255,255,.14);margin-right:20px;flex-shrink:0}
  .topnav-logo{width:32px;height:32px;background:linear-gradient(180deg,var(--mint),var(--sec));border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:var(--deep-3)}
  .topnav-app{font-size:14px;font-weight:700;color:#fff}
  .topnav-sep{color:rgba(255,255,255,.32);margin:0 8px;font-size:13px}
  .topnav-org{font-size:13px;color:rgba(255,255,255,.60)}
  .topnav-org strong{color:rgba(255,255,255,.95);font-weight:600}
  .topnav-center{flex:1;display:flex;justify-content:center;gap:8px}
  .topnav-pill{display:flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:20px;color:rgba(255,255,255,.78);font-size:12.5px;font-weight:600;cursor:pointer;transition:.2s;text-decoration:none}
  .topnav-pill:hover{background:rgba(255,255,255,.15);color:#fff}
  .topnav-pill .badge-count{background:linear-gradient(180deg,var(--mint),var(--sec));color:var(--deep-3);font-size:10px;font-weight:800;padding:1px 6px;border-radius:10px;min-width:18px;text-align:center}
  .topnav-search{display:flex;align-items:center;gap:8px;padding:6px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:20px;color:rgba(255,255,255,.55);font-size:12.5px;cursor:pointer;min-width:180px}
  .topnav-search kbd{margin-left:auto;background:rgba(255,255,255,.12);padding:1px 5px;border-radius:4px;font-size:10px;color:rgba(255,255,255,.6)}
  .topnav-right{display:flex;align-items:center;gap:12px;margin-left:20px}
  .topnav-icon-btn{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:8px;cursor:pointer;color:rgba(255,255,255,.68);transition:.2s;position:relative;background:transparent;border:none;font-size:14px}
  .topnav-icon-btn:hover{background:rgba(255,255,255,.13);color:#fff}
  .topnav-icon-btn .notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;background:var(--mint);border-radius:50%;border:1.5px solid var(--deep-2);animation:pulseMint 2.4s infinite}
  .topnav-user{display:flex;align-items:center;gap:10px;padding:4px 12px 4px 4px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:24px;cursor:pointer}
  .topnav-avatar{width:30px;height:30px;background:linear-gradient(135deg,var(--mint),var(--sec));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:var(--deep-3)}
  .topnav-user-name{font-size:12px;font-weight:600;color:#fff}
  .topnav-user-role{font-size:10px;color:rgba(255,255,255,.55)}
  .modulenav{position:fixed;top:60px;left:0;right:0;z-index:99;height:48px;background:linear-gradient(180deg,#E4EDEE,var(--row2));border-bottom:1px solid var(--line)}
  .modulenav .nav-inner{display:flex;align-items:center;height:100%;gap:2px}
  .module-item{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;white-space:nowrap;text-decoration:none}
  .module-item i{font-size:13px;opacity:.75}
  .module-item:hover{background:rgba(255,255,255,.65);color:var(--deep-2)}
  .module-item.active{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff;font-weight:700;box-shadow:var(--cta-btn-shadow)}
  .module-item.active i{opacity:1;color:var(--mint)}
  .module-item .chevron{font-size:9px;opacity:.5;margin-left:2px}
  .main{margin-top:108px;padding:30px 32px 70px;max-width:1720px;margin-left:auto;margin-right:auto}
  .breadcrumb{display:flex;align-items:center;gap:6px;margin-bottom:18px;font-size:12.5px}
  .breadcrumb a{color:var(--ink-muted);text-decoration:none;font-weight:500;cursor:pointer}
  .breadcrumb a:hover{color:var(--sec)}
  .breadcrumb .sep{color:var(--border);font-size:10px}
  .breadcrumb .current{color:var(--ink);font-weight:600}
  .page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:16px;flex-wrap:wrap}
  .page-title{font-size:25px;font-weight:800;letter-spacing:-.6px}
  .page-subtitle{font-size:13px;color:var(--ink-soft);font-weight:500;margin-top:5px}
  .page-actions{display:flex;gap:9px;flex-wrap:wrap}

  /* buttons */
  .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 17px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;border:none;text-decoration:none;font-family:'Inter',sans-serif;white-space:nowrap}
  .btn:active{transform:scale(.97)}
  .btn:disabled{opacity:.45;cursor:not-allowed;transform:none!important;filter:none!important}
  .btn i{font-size:12px}
  .btn-sm{padding:7px 13px;font-size:12px}
  .btn-xs{padding:5px 10px;font-size:11px;border-radius:8px}
  .btn-ghost{background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-soft)}
  .btn-ghost:hover:not(:disabled){border-color:var(--mint);color:var(--deep-2);background:#fff;transform:translateY(-1px)}
  .btn-primary{background:linear-gradient(180deg,#14918F 0%,var(--sec) 45%,var(--sec-2) 100%);color:#fff;box-shadow:var(--teal-btn-shadow)}
  .btn-primary:hover:not(:disabled){filter:brightness(1.07);transform:translateY(-1px)}
  .btn-deep{background:linear-gradient(180deg,var(--deep-1) 0%,var(--deep-2) 55%,var(--deep-3) 100%);color:#fff;box-shadow:var(--cta-btn-shadow)}
  .btn-deep:hover:not(:disabled){filter:brightness(1.12);transform:translateY(-1px)}
  .btn-danger{background:linear-gradient(180deg,#EF4444,var(--red-2));color:#fff;box-shadow:0 1px 2px rgba(127,29,29,.3),0 10px 22px -8px rgba(220,38,38,.5)}
  .btn-danger:hover:not(:disabled){filter:brightness(1.08)}

  /* cards */
  .card{border:1px solid var(--border);border-radius:var(--r-xl);box-shadow:var(--shadow-card);overflow:hidden;position:relative;transition:.2s;background:rgba(255,255,255,.78)}
  .card:hover{box-shadow:var(--shadow-card-hov)}
  .card::before{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:6px 6px 0 0;background:linear-gradient(90deg,transparent 0%,rgba(127,209,192,.35) 22%,rgba(16,124,123,.45) 50%,rgba(127,209,192,.35) 78%,transparent 100%);box-shadow:0 -1px 6px rgba(16,124,123,.12);pointer-events:none;z-index:1}
  .card.menu-open{overflow:visible}
  .card.fs{position:fixed;top:70px;left:16px;right:16px;bottom:16px;height:auto;z-index:260;margin:0;width:auto;max-width:none;overflow:auto;background:#fff;border-radius:16px;box-shadow:0 30px 90px -20px rgba(10,46,50,.55);animation:panelIn .25s ease both}
  .card.fs::before{display:none}
  .card.fs .table-scroll{max-height:none}
  .card.fs .card-body{padding-bottom:32px}
  .fs-btn i{font-size:11px}
  .card-head{display:flex;align-items:center;gap:11px;padding:15px 20px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.5)}
  .card-head-icon{width:30px;height:30px;border-radius:8px;background:linear-gradient(180deg,#E8930C,var(--warn));color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;box-shadow:var(--teal-btn-shadow);flex-shrink:0;cursor:help}
  .card-head-icon.teal{background:linear-gradient(180deg,#14918F,var(--sec-2));cursor:default}
  .card-head-icon.blue{background:linear-gradient(180deg,#2B7FC4,var(--blue));cursor:default}
  .card-head-icon.purple{background:linear-gradient(180deg,#8B5CF6,var(--purple));cursor:default}
  .card-head-icon.green{background:linear-gradient(180deg,#16A34A,var(--green));cursor:default}
  .card-title{font-size:14px;font-weight:800;color:var(--deep-2)}
  .card-sub{font-size:11px;color:var(--ink-muted);margin-top:1px}
  .card-head-right{margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .card-body{padding:20px}

  /* pills / chips */
  .pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:20px;font-size:10.5px;font-weight:700;white-space:nowrap}
  .pill .pdot{width:6px;height:6px;border-radius:50%}
  .pill-bank{background:var(--red-tint);color:var(--red-2);border:1px solid rgba(220,38,38,.22)}
  .pill-bank .pdot{background:var(--red)}
  .pill-book{background:var(--blue-tint);color:var(--blue);border:1px solid rgba(29,111,184,.22)}
  .pill-book .pdot{background:var(--blue)}
  .pill-draft{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-draft .pdot{background:#9DB4B4}
  .pill-progress{background:var(--warn-tint);color:var(--warn);border:1px solid rgba(217,119,6,.3)}
  .pill-progress .pdot{background:var(--warn)}
  .pill-review{background:var(--blue-tint);color:var(--blue);border:1px solid rgba(29,111,184,.25)}
  .pill-review .pdot{background:var(--blue)}
  .pill-done{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .pill-done .pdot{background:var(--green)}
  .chip-count{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;background:var(--tint);border:1px solid rgba(16,124,123,.25);color:var(--sec)}

  /* tab pills + panes */
  .ptabs{display:flex;gap:9px;flex-wrap:wrap;margin-bottom:18px}
  .ptab{padding:9px 18px;border-radius:22px;border:1px solid var(--border);background:rgba(255,255,255,.8);font-size:12.5px;font-weight:700;color:var(--ink-soft);cursor:pointer;transition:.18s;font-family:'Inter',sans-serif}
  .ptab:hover{border-color:var(--mint);color:var(--deep-2);transform:translateY(-1px)}
  .ptab.on{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:transparent;color:#fff;box-shadow:var(--teal-btn-shadow)}
  .ptab .n{font-size:10px;font-weight:800;background:var(--mist-b);color:var(--ink-soft);padding:1px 7px;border-radius:9px;margin-left:6px}
  .ptab.on .n{background:rgba(255,255,255,.2);color:#fff}
  .idx-pane{display:none;animation:panelIn .3s ease both}
  .idx-pane.on{display:block}

  /* toolbar */
  .tab-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap}
  .toolbar-left{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .seg{display:inline-flex;background:var(--mist-b);border:1px solid var(--border);border-radius:9px;padding:3px;gap:2px}
  .seg-btn{padding:6px 13px;border:none;border-radius:7px;background:transparent;font-family:inherit;font-size:11.5px;font-weight:700;color:var(--ink-soft);cursor:pointer;transition:.18s;display:flex;align-items:center;gap:5px}
  .seg-btn:hover{color:var(--ink)}
  .seg-btn.active{background:#fff;color:var(--deep-2);box-shadow:0 1px 3px rgba(10,42,46,.12)}
  .seg-btn .n{font-size:10px;background:var(--mist-b);padding:0 6px;border-radius:8px;color:var(--ink-soft)}
  .seg-btn.active .n{background:var(--tint);color:var(--sec)}
  .toolbar-search{display:flex;align-items:center;gap:8px;padding:8px 12px;background:rgba(255,255,255,.9);border:1.5px solid var(--border);border-radius:8px;min-width:240px;color:var(--ink-muted);font-size:12.5px;transition:.18s}
  .toolbar-search:focus-within{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .toolbar-search input{border:none;outline:none;font-family:inherit;font-size:12.5px;color:var(--ink);width:100%;background:transparent}

  /* tables */
  .table-wrap{border:1px solid var(--border);border-radius:14px;overflow:hidden;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card)}
  .table-wrap.menu-open,.table-wrap.menu-open .table-scroll{overflow:visible}
  .table-scroll{overflow-x:auto}
  .data-table{width:100%;border-collapse:collapse;font-size:13px}
  .data-table thead th{background:linear-gradient(180deg,var(--mist-a),var(--mist-b));text-align:left;padding:11px 14px;font-size:9.5px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-soft);border-bottom:1px solid var(--line);white-space:nowrap}
  .data-table tbody td{padding:12px 14px;border-bottom:1px solid var(--line);vertical-align:middle}
  .data-table tbody tr{transition:background .15s;animation:rowIn .3s ease backwards}
  .data-table tbody tr:hover td{background:var(--tint)}
  .data-table tbody tr:last-child td{border-bottom:none}
  tr.urg-red td{background:rgba(220,38,38,.05)!important}
  tr.urg-orange td{background:rgba(234,88,12,.05)!important}
  tr.urg-red:hover td,tr.urg-orange:hover td{background:var(--tint)!important}
  .td-mono{font-family:'JetBrains Mono',monospace;font-size:12.5px;font-variant-numeric:tabular-nums}
  .td-right{text-align:right}
  .td-strong{font-weight:700}
  .td-red{color:var(--red-2);font-weight:800}
  .td-orange{color:#C2410C;font-weight:800}
  .data-table th.td-right,.pv-table th.td-right{text-align:right}
  .data-table td.td-right,.pv-table td.td-right{text-align:right;font-variant-numeric:tabular-nums}
  .row-check{width:16px;height:16px;accent-color:var(--sec);cursor:pointer}
  .desc-cell{font-weight:600;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .req-cell{font-size:11.5px;font-weight:700;color:var(--ink-soft);max-width:210px}
  .row-icon-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);cursor:pointer;font-size:11px;transition:.15s;position:relative}
  .row-icon-btn:hover{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:transparent;color:#fff;transform:scale(1.1);box-shadow:var(--teal-btn-shadow)}
  .cont-link{font-size:12.5px;font-weight:800;color:var(--sec);cursor:pointer;white-space:nowrap;background:none;border:none;font-family:'Inter',sans-serif}
  .cont-link:hover{color:var(--deep-1);text-decoration:underline}
  .mempty{padding:52px 20px;text-align:center;color:var(--ink-muted);font-size:13px}
  .mempty i{display:block;font-size:24px;color:var(--mint);margin-bottom:10px}

  /* ageing badges */
  .age-badge{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:8px;font-size:10px;font-weight:800;letter-spacing:.3px;white-space:nowrap}
  .age-badge i{font-size:9px}
  .age-amber{background:var(--warn-tint);color:var(--warn);border:1px solid rgba(217,119,6,.3)}
  .age-orange{background:var(--orange-tint);color:#C2410C;border:1px solid rgba(234,88,12,.3)}
  .age-red{background:var(--red-tint);color:var(--red-2);border:1px solid rgba(220,38,38,.28)}
  .age-ok{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}

  /* bulk bar */
  .match-bar{display:none;align-items:center;gap:10px;padding:10px 16px;border-radius:12px;background:linear-gradient(135deg,var(--deep-2),var(--deep-3));color:#fff;margin-bottom:14px;animation:panelIn .25s ease both;box-shadow:var(--cta-btn-shadow)}
  .match-bar.show{display:flex}
  .match-bar .txt{font-size:12.5px;font-weight:700}
  .match-bar .txt span{color:var(--mint)}
  .match-bar .right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}

  /* row dropdown */
  .row-menu-wrap{position:relative;display:inline-block;z-index:1}
  .row-menu-wrap.open{z-index:700}
  .row-menu{position:absolute;right:0;top:calc(100% + 10px);min-width:250px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:0 18px 50px -12px rgba(10,46,50,.35);padding:6px;z-index:750;display:none}
  .row-menu.open{display:block;animation:panelIn .16s ease both}
  .row-menu button{display:flex;align-items:center;gap:10px;width:100%;padding:9px 12px;border:none;background:none;border-radius:8px;font-size:12.5px;font-weight:600;color:var(--ink-soft);cursor:pointer;font-family:'Inter',sans-serif;text-align:left}
  .row-menu button:hover{background:var(--tint);color:var(--deep-2)}
  .row-menu button.danger{color:var(--red-2)}
  .row-menu button.danger:hover{background:var(--red-tint)}
  .row-menu button i{width:16px;text-align:center;color:var(--sec)}
  .row-menu button.danger i{color:var(--red)}
  .row-menu hr{border:none;border-top:1px solid var(--line);margin:5px 8px}

  /* matcher panels */
  .match-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media(max-width:900px){.match-grid{grid-template-columns:1fr}}
  .mpanel{border:1px solid var(--border);border-radius:14px;overflow:hidden;background:rgba(255,255,255,.8);display:flex;flex-direction:column;max-height:420px}
  .modal.xwide .mpanel{max-height:480px}
  .mpanel-head{display:flex;align-items:center;gap:9px;padding:12px 14px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,var(--mist-a),var(--mist-b))}
  .mpanel-title{font-size:11px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--deep-2)}
  .mpanel-sub{font-size:10px;color:var(--ink-muted);font-weight:600}
  .mpanel-search{margin-left:auto;display:flex;align-items:center;gap:6px;padding:5px 10px;background:#fff;border:1px solid var(--border);border-radius:8px;font-size:11px;color:var(--ink-muted);width:150px}
  .mpanel-search input{border:none;outline:none;font-family:inherit;font-size:11px;width:100%;color:var(--ink)}
  .mpanel-body{overflow-y:auto;flex:1}
  .mrow{display:grid;grid-template-columns:74px 1fr 100px;gap:8px;align-items:center;padding:10px 12px;border-bottom:1px solid var(--line);cursor:pointer;transition:.15s;font-size:12px}
  .mrow:hover{background:var(--tint)}
  .mrow.sel{background:rgba(16,124,123,.10);box-shadow:inset 3px 0 0 var(--sec)}
  .mrow .d{color:var(--ink-muted);font-size:11px;font-weight:600}
  .mrow .desc{min-width:0}
  .mrow .desc .t{display:block;font-weight:600;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:1.35}
  .mrow .desc .r{display:block;margin-top:3px;font-size:10px;color:var(--ink-muted);font-family:'JetBrains Mono',monospace;line-height:1.2}
  .mrow .amt{text-align:right;font-family:'JetBrains Mono',monospace;font-weight:700;font-size:12px}
  .mrow .amt.neg{color:var(--red-2)}

  /* reports grid */
  .rep-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
  @media(max-width:1100px){.rep-grid{grid-template-columns:1fr 1fr}}
  .rep-card{border:1px solid var(--border);border-radius:14px;padding:18px;background:rgba(255,255,255,.78);box-shadow:var(--shadow-card);transition:.2s;display:flex;flex-direction:column;gap:10px}
  .rep-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-card-hov)}
  .rep-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;background:var(--tint);color:var(--sec)}
  .rep-name{font-size:13px;font-weight:800;color:var(--deep-2)}
  .rep-desc{font-size:11.5px;color:var(--ink-soft);line-height:1.5;flex:1}

  /* audit list */
  .audit-item{display:flex;gap:12px;padding:12px 4px;border-bottom:1px solid var(--line);font-size:12.5px}
  .audit-item:last-child{border-bottom:none}
  .audit-dot{width:9px;height:9px;border-radius:50%;background:var(--mint);border:2px solid var(--sec);margin-top:4px;flex-shrink:0}
  .audit-txt b{color:var(--deep-2)}
  .audit-time{font-size:10.5px;color:var(--ink-muted);margin-top:2px}

  /* slide-overs — hidden when closed so no shadow leaks at viewport right */
  .slideover-overlay{position:fixed;inset:0;background:rgba(10,46,50,.45);backdrop-filter:blur(4px);z-index:320;opacity:0;pointer-events:none;transition:opacity .25s}
  .slideover-overlay.open{opacity:1;pointer-events:auto}
  .slideover{position:fixed;top:0;right:0;bottom:0;width:560px;max-width:96vw;background:#fff;z-index:321;transform:translateX(102%);visibility:hidden;display:flex;flex-direction:column;
    transition:transform .32s cubic-bezier(.2,.85,.3,1), visibility 0s linear .32s}
  .slideover.open{transform:translateX(0);visibility:visible;box-shadow:-18px 0 60px -18px rgba(10,46,50,.45);
    transition:transform .32s cubic-bezier(.2,.85,.3,1)}
  .slideover.narrow{width:470px}
  .slideover-head{padding:18px 24px;background:radial-gradient(120% 150% at 88% -30%, rgba(127,209,192,.28), transparent 55%),linear-gradient(135deg,var(--deep-2),var(--deep-3));display:flex;align-items:center;gap:14px;position:relative;flex-shrink:0}
  .slideover-head.danger{background:radial-gradient(120% 150% at 88% -30%, rgba(255,120,120,.25), transparent 55%),linear-gradient(135deg,#991B1B,#7F1D1D)}
  .slideover-head.amber{background:radial-gradient(120% 150% at 88% -30%, rgba(251,191,36,.25), transparent 55%),linear-gradient(135deg,#92400E,#78350F)}
  .slideover-head::after{content:'';position:absolute;left:0;right:0;bottom:0;height:1px;background:linear-gradient(90deg,var(--mint),transparent 60%)}
  .so-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;box-shadow:var(--teal-btn-shadow)}
  .slideover-head.danger .so-icon,.slideover-head.amber .so-icon{background:rgba(255,255,255,.15)}
  .slideover-title{font-size:15px;font-weight:800;color:#fff}
  .slideover-sub{font-size:11.5px;color:rgba(255,255,255,.62);margin-top:2px}
  .so-close{margin-left:auto;width:32px;height:32px;border-radius:8px;border:none;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;font-size:13px;transition:.18s;flex-shrink:0}
  .so-close:hover{background:rgba(255,255,255,.22);transform:rotate(90deg)}
  .slideover-body{flex:1;overflow-y:auto;padding:22px 24px;background:linear-gradient(180deg,var(--mist-a),#fff 30%)}
  .slideover-foot{padding:15px 24px;border-top:1px solid var(--line);background:linear-gradient(180deg,var(--mist-a),var(--mist-b));display:flex;gap:10px;justify-content:flex-end;flex-shrink:0}
  .src-recap{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid var(--border);border-radius:12px;background:var(--tint);margin-bottom:18px}
  .src-recap i.lead{color:var(--sec);font-size:16px}
  .src-recap .t{font-size:12.5px;font-weight:800;color:var(--deep-2)}
  .src-recap .s{font-size:10.5px;color:var(--ink-muted);margin-top:1px}
  .src-recap .amt{margin-left:auto;font-family:'JetBrains Mono',monospace;font-weight:800;font-size:15px}
  .src-recap .amt.neg{color:var(--red-2)}

  /* forms */
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 16px}
  .form-grid .full{grid-column:1/-1}
  .form-field label{display:block;font-size:10.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:6px}
  .form-field label .req{color:var(--red)}
  .form-control{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:9px;font-family:inherit;font-size:13px;color:var(--ink);background:#fff;outline:none;transition:.15s}
  .form-control:focus{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .form-control:disabled{background:var(--mist-a);color:var(--ink-muted)}
  select.form-control{appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235F7A7C' d='M6 8L2 4h8z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;cursor:pointer}
  textarea.form-control{resize:vertical;min-height:88px;line-height:1.6}
  .mono{font-family:'JetBrains Mono',monospace;font-variant-numeric:tabular-nums}
  .sec-lbl{font-size:10.5px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--deep-2);margin:20px 0 8px;display:flex;align-items:center;gap:8px}
  .sec-lbl::after{content:'';flex:1;height:1px;background:var(--line)}
  .sec-lbl:first-child{margin-top:0}
  .info-banner{display:flex;gap:10px;align-items:flex-start;padding:12px 16px;border-radius:10px;background:var(--blue-tint);border:1px solid rgba(29,111,184,.22);color:#155E93;font-size:12.5px;font-weight:600;line-height:1.55;margin-bottom:18px}
  .info-banner i{margin-top:2px}
  .info-banner.warn{background:var(--warn-tint);border-color:rgba(217,119,6,.3);color:var(--warn)}

  /* JE editor — no horizontal scroll at jwide width */
  .je-grid{display:grid;grid-template-columns:1.6fr .8fr .8fr 1.2fr 34px;gap:10px;align-items:center}
  .je-grid > *{min-width:0}
  .je-grid.head{font-size:9.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--ink-muted);padding-bottom:6px}
  .je-row{padding:7px 0;border-bottom:1px solid var(--line)}
  .je-row .form-control{padding:8px 10px;font-size:12px}
  .je-row .num{text-align:right}
  .je-remove{width:28px;height:28px;border-radius:6px;border:none;background:var(--red-tint);color:var(--red-2);cursor:pointer;transition:.15s}
  .je-remove:hover{background:rgba(220,38,38,.18)}
  .je-remove:disabled{opacity:.3;cursor:not-allowed}
  .je-balance{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:14px;padding:11px 14px;border-radius:10px;font-size:12.5px;font-weight:800;flex-wrap:wrap}
  .je-balance.ok{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .je-balance.bad{background:var(--red-tint);color:var(--red-2);border:1px solid rgba(220,38,38,.25)}
  .sugg-flag{display:inline-flex;align-items:center;gap:5px;font-size:9px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;background:var(--green-tint);color:var(--green);padding:2px 8px;border-radius:8px;border:1px solid rgba(21,128,61,.25)}

  /* modals */
  .modal-overlay{position:fixed;inset:0;background:rgba(10,46,50,.55);backdrop-filter:blur(5px);z-index:330;display:none;align-items:flex-start;justify-content:center;padding:48px 20px;overflow-y:auto}
  .modal-overlay.open{display:flex;animation:fadeIn .2s ease both}
  .modal{background:#fff;border-radius:16px;width:100%;max-width:520px;box-shadow:0 24px 70px -18px rgba(10,46,50,.5);animation:popIn .3s cubic-bezier(.2,.9,.3,1.2) both;overflow:hidden}
  .modal.wide{max-width:1000px}
  .modal.xwide{max-width:1320px}
  .modal.jwide{max-width:980px}
  @media(max-width:1360px){.modal.xwide{max-width:96vw}}
  @media(max-width:1020px){.modal.jwide{max-width:96vw}}
  .modal-header{display:flex;align-items:center;gap:14px;padding:18px 24px;background:radial-gradient(120% 150% at 88% -30%, rgba(127,209,192,.28), transparent 55%),linear-gradient(135deg,var(--deep-2),var(--deep-3));position:relative}
  .modal-header::after{content:'';position:absolute;left:0;right:0;bottom:0;height:1px;background:linear-gradient(90deg,rgba(127,209,192,.4),transparent 60%)}
  .modal-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;box-shadow:var(--teal-btn-shadow);flex-shrink:0}
  .modal-title{font-size:16px;font-weight:800;color:#fff}
  .modal-subtitle{font-size:12px;color:rgba(255,255,255,.62);margin-top:2px}
  .modal-close{margin-left:auto;width:32px;height:32px;border-radius:8px;border:none;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;font-size:13px;transition:.18s;flex-shrink:0}
  .modal-close:hover{background:rgba(255,255,255,.22);transform:rotate(90deg)}
  .modal-body{padding:22px;max-height:calc(100vh - 260px);overflow-y:auto}
  .modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:15px 22px;border-top:1px solid var(--line);background:linear-gradient(180deg,var(--mist-a),var(--mist-b));flex-wrap:wrap}
  .modal-footer .left{margin-right:auto}

  /* toasts */
  .toast-wrap{position:fixed;bottom:24px;right:24px;z-index:400;display:flex;flex-direction:column;gap:10px}
  .toast{display:flex;align-items:flex-start;gap:10px;min-width:300px;max-width:420px;padding:13px 16px;border-radius:12px;background:linear-gradient(135deg,var(--deep-2),var(--deep-3));color:#fff;box-shadow:var(--cta-btn-shadow);animation:toastIn .3s cubic-bezier(.2,.9,.3,1.2) both}
  .toast.ok i{color:var(--mint)}.toast.warn i{color:#FBBF24}.toast.err i{color:#FCA5A5}
  .toast .t-title{font-size:12.5px;font-weight:800}
  .toast .t-sub{font-size:11px;color:rgba(255,255,255,.65);margin-top:3px;line-height:1.5}

  /* tooltips */
  [data-tip]{position:relative}
  [data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--deep-2);color:#fff;font-size:11px;font-weight:500;padding:4px 9px;border-radius:6px;white-space:nowrap;pointer-events:none;z-index:900;animation:fadeIn .15s ease both;box-shadow:var(--cta-btn-shadow)}
  [data-tip].tip-below:hover::after{bottom:auto;top:calc(100% + 8px)}
  [data-tip].tip-wide:hover::after{white-space:normal;width:300px;text-align:left;line-height:1.55;padding:8px 11px;border-radius:9px;font-weight:600}
  [data-tip].tip-start:hover::after{left:0;transform:none}
  ::-webkit-scrollbar{width:5px;height:5px}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
  ::-webkit-scrollbar-thumb:hover{background:var(--sec)}
</style>
</head>
<body>

<nav class="topnav">
  <div class="nav-inner">
    <div class="topnav-brand"><div class="topnav-logo">L</div><span class="topnav-app">CamelotBooks</span><span class="topnav-sep">|</span><span class="topnav-org"><strong>Acme Corporation</strong> &nbsp;·&nbsp; Headquarters</span></div>
    <div class="topnav-center"><a class="topnav-pill" href="#"><i class="fa-solid fa-list-check"></i> My Tasks <span class="badge-count">3</span></a><a class="topnav-pill" href="#"><i class="fa-solid fa-star"></i> Favourites <span class="badge-count">16</span></a><div class="topnav-search"><i class="fa-solid fa-magnifying-glass"></i> Search… <kbd>Ctrl K</kbd></div></div>
    <div class="topnav-right"><button class="topnav-icon-btn" data-tip="Notifications"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button><button class="topnav-icon-btn" data-tip="Help"><i class="fa-solid fa-circle-question"></i></button><div class="topnav-user"><div class="topnav-avatar">ES</div><div><div class="topnav-user-name">Elvis Seyama</div><div class="topnav-user-role">SYSTEM_ADMIN</div></div></div></div>
  </div>
</nav>
<nav class="modulenav">
  <div class="nav-inner">
    <a class="module-item" href="#"><i class="fa-solid fa-cart-shopping"></i> Sales <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-truck-field"></i> Purchasing <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-cubes"></i> Inventory <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item active" href="#"><i class="fa-solid fa-building-columns"></i> Banking <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-book-open"></i> Accounting <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-building"></i> Assets <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-users"></i> Payroll <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-chart-bar"></i> Reports <i class="fa-solid fa-chevron-down chevron"></i></a>
    <a class="module-item" href="#"><i class="fa-solid fa-gear"></i> System <i class="fa-solid fa-chevron-down chevron"></i></a>
  </div>
</nav>

<main class="main">
  <div class="breadcrumb anim-1"><a>Banking</a><i class="fa-solid fa-chevron-right sep"></i><span class="current">Reconciliation</span></div>

  <div class="page-header anim-1">
    <div>
      <h1 class="page-title">Bank Reconciliation — Control Centre</h1>
      <div class="page-subtitle">Outstanding items, reconciliation periods, statements, adjustments, reports and audit — every item carries a required action.</div>
    </div>
    <div class="page-actions">
      <button class="btn btn-ghost" onclick="openMatcher('All open periods')"><i class="fa-solid fa-link"></i> Go to Matcher</button>
      <button class="btn btn-ghost" onclick="render()"><i class="fa-solid fa-rotate"></i> Refresh</button>
    </div>
  </div>

  <div class="ptabs anim-2" id="ptabs">
    <button class="ptab" data-tab="recs" onclick="setTab('recs')">Reconciliations <span class="n" id="t-recs">0</span></button>
    <button class="ptab" data-tab="stmts" onclick="setTab('stmts')">Bank Statements <span class="n" id="t-stmts">0</span></button>
    <button class="ptab" data-tab="adjs" onclick="setTab('adjs')">Adjustments <span class="n" id="t-adjs">0</span></button>
    <button class="ptab on" data-tab="outst" onclick="setTab('outst')">Outstanding Items <span class="n" id="t-outst">0</span></button>
    <button class="ptab" data-tab="reps" onclick="setTab('reps')">Reports</button>
    <button class="ptab" data-tab="audit" onclick="setTab('audit')">Audit Trail <span class="n" id="t-audit">0</span></button>
  </div>

  <!-- ═══ PANE: RECONCILIATIONS ═══ -->
  <div class="idx-pane" id="pane-recs">
    <div class="card glass">
      <div class="card-head">
        <div class="card-head-icon teal"><i class="fa-solid fa-scale-balanced"></i></div>
        <div><div class="card-title">Reconciliation Register</div><div class="card-sub">All periods — continue into the matcher from any row</div></div>
        <div class="card-head-right"><button class="btn btn-ghost btn-sm" onclick="location.href='bank-reconciliation.html'"><i class="fa-solid fa-plus"></i> New Reconciliation</button></div>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead><tr><th>Bank Account</th><th>Statement</th><th>Period End</th><th class="td-right">Opening (K)</th><th class="td-right">Closing (K)</th><th class="td-right">Book (K)</th><th class="td-right">Difference</th><th>Status</th><th style="text-align:right">Action</th></tr></thead>
          <tbody id="recs-rows"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ═══ PANE: BANK STATEMENTS ═══ -->
  <div class="idx-pane" id="pane-stmts">
    <div class="card glass">
      <div class="card-head">
        <div class="card-head-icon blue"><i class="fa-solid fa-file-invoice"></i></div>
        <div><div class="card-title">Bank Statements</div><div class="card-sub">Every file parsed or imported into a reconciliation period</div></div>
        <div class="card-head-right"><button class="btn btn-ghost btn-sm" onclick="location.href='bank-reconciliation.html'"><i class="fa-solid fa-file-import"></i> Import Statement</button></div>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead><tr><th>File</th><th>Period</th><th class="td-right">Lines</th><th>Imported</th><th>By</th><th>Status</th></tr></thead>
          <tbody id="stmts-rows"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ═══ PANE: ADJUSTMENTS ═══ -->
  <div class="idx-pane" id="pane-adjs">
    <div class="card glass">
      <div class="card-head">
        <div class="card-head-icon purple"><i class="fa-solid fa-sliders"></i></div>
        <div><div class="card-title">Adjustments</div><div class="card-sub">Book & statement side corrections — posted to the ledger on finalize</div></div>
        <div class="card-head-right"><button class="btn btn-ghost btn-sm" onclick="openAdjustmentModal()"><i class="fa-solid fa-plus"></i> New Adjustment</button></div>
      </div>
      <div class="table-scroll">
        <table class="data-table">
          <thead><tr><th>Period</th><th>Type</th><th>Side</th><th class="td-right">Amount (K)</th><th>Account</th><th>Description</th></tr></thead>
          <tbody id="adjs-rows"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ═══ PANE: OUTSTANDING ITEMS ═══ -->
  <div class="idx-pane on" id="pane-outst">
    <div class="card glass anim-3">
      <div class="card-head">
        <div class="card-head-icon tip-below tip-wide tip-start" data-tip="Ageing policy: deposits in transit > 3d investigate · cheques > 90d follow up · > 180d void stale · unrecorded bank lines > 30d record"><i class="fa-solid fa-circle-question"></i></div>
        <div><div class="card-title">Outstanding Items Register</div></div>
        <div class="card-head-right">
          <span class="chip-count" id="head-count">0 open items</span>
          <button class="btn btn-ghost btn-sm fs-btn tip-below" data-tip="Expand to full width" onclick="toggleFs(this)"><i class="fa-solid fa-expand"></i> Expand</button>
        </div>
      </div>
      <div class="card-body">
        <div class="tab-toolbar">
          <div class="toolbar-left">
            <div class="seg" id="filter-seg">
              <button class="seg-btn active" data-f="all">All <span class="n" id="n-all">0</span></button>
              <button class="seg-btn" data-f="bank_only">Bank-only <span class="n" id="n-bank">0</span></button>
              <button class="seg-btn" data-f="book_only">Book-only <span class="n" id="n-book">0</span></button>
              <button class="seg-btn" data-f="urgent"><i class="fa-solid fa-triangle-exclamation" style="color:var(--red)"></i> Urgent <span class="n" id="n-urgent">0</span></button>
            </div>
            <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input id="search" placeholder="Reference, description or period…"></div>
          </div>
          <span class="chip-count" id="visible-count">0 item(s)</span>
        </div>
        <div class="match-bar" id="bulk-bar">
          <span class="txt"><span id="bulk-num">0</span> item(s) selected</span>
          <div class="right">
            <button class="btn btn-ghost btn-sm" onclick="bulkCarry()"><i class="fa-solid fa-forward"></i> Carry Forward Selected</button>
            <button class="btn btn-ghost btn-sm" onclick="bulkInvestigate()"><i class="fa-solid fa-user-plus"></i> Assign Investigation</button>
            <button class="btn btn-ghost btn-sm" onclick="clearSel()"><i class="fa-solid fa-xmark"></i> Clear</button>
          </div>
        </div>
        <div class="table-wrap">
          <div class="table-scroll">
            <table class="data-table">
              <thead><tr>
                <th style="width:34px"><input type="checkbox" class="row-check" id="check-all"></th>
                <th>Ageing</th><th>Kind</th><th>Period</th><th>Date</th><th>Reference</th><th>Description</th>
                <th class="td-right">Amount (K)</th><th>Required Action</th><th style="text-align:right">Actions</th>
              </tr></thead>
              <tbody id="rows"></tbody>
            </table>
            <div class="mempty" id="empty" style="display:none"><i class="fa-solid fa-circle-check"></i>Nothing outstanding — every line is matched, recorded or explained.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ PANE: REPORTS ═══ -->
  <div class="idx-pane" id="pane-reps">
    <div class="rep-grid">
      <div class="rep-card"><div class="rep-icon"><i class="fa-solid fa-scale-balanced"></i></div><div class="rep-name">Reconciliation Summary</div><div class="rep-desc">Period-by-period book vs bank position with differences and status.</div><button class="btn btn-primary btn-sm" onclick="runReport('summary')"><i class="fa-solid fa-play"></i> Run</button></div>
      <div class="rep-card"><div class="rep-icon" style="background:var(--warn-tint);color:var(--warn)"><i class="fa-solid fa-hourglass-half"></i></div><div class="rep-name">Unmatched Items</div><div class="rep-desc">All bank-only and book-only lines carried across open periods.</div><button class="btn btn-primary btn-sm" onclick="runReport('unmatched')"><i class="fa-solid fa-play"></i> Run</button></div>
      <div class="rep-card"><div class="rep-icon" style="background:var(--purple-tint);color:var(--purple)"><i class="fa-solid fa-sliders"></i></div><div class="rep-name">Adjustment Ledger</div><div class="rep-desc">Posted and pending reconciliation adjustments by account and side.</div><button class="btn btn-primary btn-sm" onclick="runReport('adjustments')"><i class="fa-solid fa-play"></i> Run</button></div>
      <div class="rep-card"><div class="rep-icon" style="background:var(--blue-tint);color:var(--blue)"><i class="fa-solid fa-clock-rotate-left"></i></div><div class="rep-name">Audit Trail</div><div class="rep-desc">Who imported, matched, adjusted and finalized — with timestamps.</div><button class="btn btn-primary btn-sm" onclick="runReport('audit')"><i class="fa-solid fa-play"></i> Run</button></div>
    </div>
  </div>

  <!-- ═══ PANE: AUDIT TRAIL ═══ -->
  <div class="idx-pane" id="pane-audit">
    <div class="card glass">
      <div class="card-head">
        <div class="card-head-icon green"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div><div class="card-title">Audit Trail</div><div class="card-sub">Immutable event log — appended by every mutating action in this module</div></div>
        <div class="card-head-right"><span class="chip-count" id="audit-count">0 events</span></div>
      </div>
      <div class="card-body" id="audit-body"></div>
    </div>
  </div>
</main>

<!-- ═══ MODAL: TRANSACTION MATCHER (extra wide) ═══ -->
<div class="modal-overlay" id="modal-matcher">
  <div class="modal xwide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-link"></i></div>
      <div style="flex:1"><div class="modal-title">Transaction Matcher</div><div class="modal-subtitle" id="mm-sub">All open periods</div></div>
      <button class="modal-close" onclick="closeModal('modal-matcher')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="match-bar" id="mm-bar">
        <span class="txt" id="mm-bar-txt">Select one book and one bank line</span>
        <div class="right">
          <button class="btn btn-primary btn-sm" id="mm-match" disabled onclick="matchPair()"><i class="fa-solid fa-link"></i> Match Selected</button>
          <button class="btn btn-ghost btn-sm" onclick="mmClear()">Clear</button>
        </div>
      </div>
      <div class="match-grid">
        <div class="mpanel">
          <div class="mpanel-head"><span class="mpanel-title">Book Transactions</span><span class="mpanel-sub" id="mm-book-count">0 open</span>
            <label class="mpanel-search"><i class="fa-solid fa-magnifying-glass"></i><input id="mm-search-book" placeholder="Search book…" oninput="renderMatcher()"></label></div>
          <div class="mpanel-body" id="mm-book"></div>
        </div>
        <div class="mpanel">
          <div class="mpanel-head"><span class="mpanel-title">Statement Lines</span><span class="mpanel-sub" id="mm-bank-count">0 open</span>
            <label class="mpanel-search"><i class="fa-solid fa-magnifying-glass"></i><input id="mm-search-bank" placeholder="Search bank…" oninput="renderMatcher()"></label></div>
          <div class="mpanel-body" id="mm-bank"></div>
        </div>
      </div>
      <div class="info-banner" style="margin:16px 0 0"><i class="fa-solid fa-circle-info"></i><span>Matching is enabled only when the two selected amounts agree within K 0.01 — otherwise record an adjustment first.</span></div>
    </div>
    <div class="modal-footer">
      <span class="chip-count left" id="mm-matched-count">0 matched this session</span>
      <button class="btn btn-ghost" onclick="closeModal('modal-matcher')">Close</button>
    </div>
  </div>
</div>

<!-- ═══ MODAL: RECORD IN BOOKS — QUICK JOURNAL (wide) ═══ -->
<div class="modal-overlay" id="modal-journal">
  <div class="modal jwide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-book-medical"></i></div>
      <div style="flex:1"><div class="modal-title">Record in Books — Quick Journal</div><div class="modal-subtitle" id="j-sub">—</div></div>
      <button class="modal-close" onclick="closeModal('modal-journal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="src-recap"><i class="fa-solid fa-file-invoice lead"></i><div><div class="t" id="j-recap-t">—</div><div class="s" id="j-recap-s">—</div></div><span class="amt" id="j-recap-amt">0.00</span></div>
      <div class="info-banner" style="margin-bottom:16px"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Lines below were <b>auto-suggested</b> from the bank line type. Adjust accounts before posting.</span></div>
      <div class="form-grid" style="margin-bottom:6px">
        <div class="form-field"><label>Journal Date <span class="req">*</span></label><input type="date" class="form-control" id="j-date"></div>
        <div class="form-field"><label>Source</label><input class="form-control" value="Bank Rec — Outstanding Item" disabled></div>
        <div class="form-field full"><label>Memo <span class="req">*</span></label><input class="form-control" id="j-memo" placeholder="e.g. Bank charges per August statement"></div>
      </div>
      <div class="sec-lbl">Journal Lines <span class="sugg-flag"><i class="fa-solid fa-wand-magic-sparkles"></i> suggested</span>
        <button class="btn btn-ghost btn-xs" style="margin-left:auto" onclick="addJeLine()"><i class="fa-solid fa-plus"></i> Add Line</button></div>
      <div class="je-grid head"><span>Account</span><span class="td-right">Debit (K)</span><span class="td-right">Credit (K)</span><span>Memo</span><span></span></div>
      <div id="je-lines"></div>
      <div class="je-balance bad" id="je-balance"><span>UNBALANCED</span><span class="mono" id="je-totals"></span></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-journal')">Cancel</button>
      <button class="btn btn-deep" id="j-save" disabled onclick="saveJournal()"><i class="fa-solid fa-check"></i> Post Journal &amp; Clear Item</button>
    </div>
  </div>
</div>

<!-- ═══ MODAL: REPORT PREVIEW ═══ -->
<div class="modal-overlay" id="modal-report">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-chart-column"></i></div>
      <div style="flex:1"><div class="modal-title" id="rp-title">Report</div><div class="modal-subtitle" id="rp-sub">Preview</div></div>
      <button class="modal-close" onclick="closeModal('modal-report')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body"><div class="table-scroll"><table class="data-table" id="rp-table"></table></div></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-report')">Close</button>
      <button class="btn btn-deep" onclick="exportReport()"><i class="fa-solid fa-download"></i> Export CSV</button>
    </div>
  </div>
</div>

<!-- ═══ MODAL: NEW ADJUSTMENT ═══ -->
<div class="modal-overlay" id="modal-adjustment">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon" style="background:linear-gradient(180deg,#8B5CF6,var(--purple))"><i class="fa-solid fa-sliders"></i></div>
      <div style="flex:1"><div class="modal-title">New Adjustment</div><div class="modal-subtitle">Book or statement side correction</div></div>
      <button class="modal-close" onclick="closeModal('modal-adjustment')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field full"><label>Period <span class="req">*</span></label><select class="form-control" id="na-period"></select></div>
        <div class="form-field"><label>Type</label><select class="form-control" id="na-type"><option>Bank Fees</option><option>Interest Income</option><option>NSF Cheque</option><option>Deposit in Transit</option><option>Outstanding Cheque</option><option>Error Correction</option></select></div>
        <div class="form-field"><label>Side</label><select class="form-control" id="na-side"><option value="book">Book</option><option value="statement">Statement</option></select></div>
        <div class="form-field"><label>Sign</label><select class="form-control" id="na-sign"><option value="-1">Subtract (−)</option><option value="1">Add (+)</option></select></div>
        <div class="form-field"><label>Amount (K) <span class="req">*</span></label><input class="form-control mono" id="na-amt" type="number" step="0.01" placeholder="0.00"></div>
        <div class="form-field full"><label>Account</label><select class="form-control" id="na-acct"><option value="">—</option><option>6100 — Bank Charges</option><option>4100 — Interest Income</option><option>1100 — Accounts Receivable</option><option>2100 — Accounts Payable</option></select></div>
        <div class="form-field full"><label>Description</label><input class="form-control" id="na-desc" placeholder="Optional note"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modal-adjustment')">Cancel</button>
      <button class="btn btn-deep" onclick="saveAdjustment()"><i class="fa-solid fa-check"></i> Save Adjustment</button>
    </div>
  </div>
</div>

<!-- ═══ SLIDE-OVERS: CARRY / INVESTIGATE / VOID / BANK ERROR ═══ -->
<div class="slideover-overlay" id="ov-carry" onclick="closePane()"></div>
<aside class="slideover narrow" id="so-carry">
  <div class="slideover-head">
    <div class="so-icon"><i class="fa-solid fa-forward"></i></div>
    <div style="flex:1"><div class="slideover-title">Carry Forward</div><div class="slideover-sub" id="c-sub">—</div></div>
    <button class="so-close" onclick="closePane()"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="slideover-body">
    <div class="info-banner"><i class="fa-solid fa-circle-info"></i><span>Timing differences roll into the next open period automatically. Add a reason for the audit trail.</span></div>
    <div class="form-field" style="margin-bottom:14px"><label>Target Period</label><select class="form-control" id="c-target"><option value="">Next open period (auto)</option></select></div>
    <div class="form-field"><label>Reason / Note</label><textarea class="form-control" id="c-note" placeholder="e.g. Cheque presented to bank 29 Aug, clears next cycle…"></textarea></div>
  </div>
  <div class="slideover-foot">
    <button class="btn btn-ghost" onclick="closePane()">Cancel</button>
    <button class="btn btn-deep" onclick="saveCarry()"><i class="fa-solid fa-forward"></i> Carry Forward</button>
  </div>
</aside>

<div class="slideover-overlay" id="ov-investigate" onclick="closePane()"></div>
<aside class="slideover narrow" id="so-investigate">
  <div class="slideover-head amber">
    <div class="so-icon"><i class="fa-solid fa-user-magnifying-glass"></i></div>
    <div style="flex:1"><div class="slideover-title" id="i-title">Investigate / Assign</div><div class="slideover-sub" id="i-sub">—</div></div>
    <button class="so-close" onclick="closePane()"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="slideover-body">
    <div class="info-banner warn"><i class="fa-solid fa-bell"></i><span>The assignee receives a task notification with the due date and your instruction.</span></div>
    <div class="form-field" style="margin-bottom:14px"><label>Assign To</label><select class="form-control" id="i-user"></select></div>
    <div class="form-field" style="margin-bottom:14px"><label>Due Date</label><input type="date" class="form-control" id="i-due"></div>
    <div class="form-field"><label>Instruction / Note</label><textarea class="form-control" id="i-note" placeholder="e.g. Confirm with branch why deposit of K … has not cleared…"></textarea></div>
  </div>
  <div class="slideover-foot">
    <button class="btn btn-ghost" onclick="closePane()">Cancel</button>
    <button class="btn btn-deep" onclick="saveInvestigate()"><i class="fa-solid fa-paper-plane"></i> Assign Investigation</button>
  </div>
</aside>

<div class="slideover-overlay" id="ov-void" onclick="closePane()"></div>
<aside class="slideover narrow" id="so-void">
  <div class="slideover-head danger">
    <div class="so-icon"><i class="fa-solid fa-ban"></i></div>
    <div style="flex:1"><div class="slideover-title">Void / Reverse Stale Item</div><div class="slideover-sub" id="v-sub">—</div></div>
    <button class="so-close" onclick="closePane()"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="slideover-body">
    <div class="info-banner warn"><i class="fa-solid fa-triangle-exclamation"></i><span>A <b>reversing journal entry</b> will be posted automatically and the original book transaction cancelled. Intended for stale-dated cheques (&gt; 180 days).</span></div>
    <div class="src-recap"><i class="fa-solid fa-money-check lead"></i><div><div class="t" id="v-recap-t">—</div><div class="s" id="v-recap-s">—</div></div><span class="amt neg" id="v-recap-amt">0.00</span></div>
    <div class="form-field"><label>Void Reason <span class="req">*</span></label><textarea class="form-control" id="v-reason" placeholder="e.g. Cheque 10454 never presented; supplier re-issued invoice…"></textarea></div>
  </div>
  <div class="slideover-foot">
    <button class="btn btn-ghost" onclick="closePane()">Cancel</button>
    <button class="btn btn-danger" id="v-save" disabled onclick="saveVoid()"><i class="fa-solid fa-ban"></i> Void &amp; Post Reversal</button>
  </div>
</aside>

<div class="slideover-overlay" id="ov-bank_error" onclick="closePane()"></div>
<aside class="slideover narrow" id="so-bank_error">
  <div class="slideover-head danger">
    <div class="so-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div style="flex:1"><div class="slideover-title">Mark as Bank Error</div><div class="slideover-sub" id="e-sub">—</div></div>
    <button class="so-close" onclick="closePane()"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="slideover-body">
    <div class="info-banner warn"><i class="fa-solid fa-circle-info"></i><span>The line is excluded from ERP recording and queued into the <b>Bank Error Dispute Report</b> your finance manager sends to the bank.</span></div>
    <div class="form-field"><label>Dispute Note <span class="req">*</span></label><textarea class="form-control" id="e-note" placeholder="e.g. Duplicate charge on 15/08 — bank to reverse…"></textarea></div>
  </div>
  <div class="slideover-foot">
    <button class="btn btn-ghost" onclick="closePane()">Cancel</button>
    <button class="btn btn-deep" id="e-save" disabled onclick="saveBankError()"><i class="fa-solid fa-flag"></i> Flag as Bank Error</button>
  </div>
</aside>

<div class="toast-wrap" id="toasts"></div>

<script>
/* ═════════ SEED DATA ═════════ */
const ACCOUNTS = [
  {id:1000,label:'1000 — Cash and Cash Equivalents'},
  {id:1100,label:'1100 — Accounts Receivable'},
  {id:2100,label:'2100 — Accounts Payable'},
  {id:4000,label:'4000 — Sales Revenue'},
  {id:4100,label:'4100 — Interest Income'},
  {id:6100,label:'6100 — Bank Charges'}];
const USERS = [{id:1,name:'Elvis Seyama'},{id:2,name:'Mary Phiri'},{id:3,name:'Peter Mwangonde'},{id:4,name:'Grace Banda'}];
const PERIODS = [{id:11,label:'1000 · stmt-nov · Nov 2026'},{id:12,label:'1000 · stmt-dec · Dec 2026 (next open)'}];
const RECS = [
  {id:'r1', acct:'1000 — Cash and Cash Equivalents', stmt:'asdasdas', end:'2026-11-02', open:622222.00, close:555551.00, book:4000.00, status:'draft'},
  {id:'r2', acct:'1000 — Cash and Cash Equivalents', stmt:'were', end:'2026-08-13', open:900000.00, close:6540000.00, book:4000.00, status:'in-progress'},
  {id:'r3', acct:'1000 — Cash and Cash Equivalents', stmt:'15542', end:'2026-07-31', open:1500000.00, close:8965420.00, book:4000.00, status:'draft'}];
const IMPORTS = [
  {file:'nov_stmt_draft.csv', period:'1000 · asdasdas', lines:9, at:new Date('2026-11-02T08:05:00'), by:'Mary Phiri', status:'Parsed'},
  {file:'sample_bank_statement_jul2026.xlsx', period:'1000 · were', lines:21, at:new Date('2026-08-13T10:19:00'), by:'Elvis Seyama', status:'Imported'},
  {file:'stanbic_aug2026.xlsx', period:'1000 · were', lines:18, at:new Date('2026-08-13T09:29:00'), by:'Elvis Seyama', status:'Imported'}];
const ADJUSTMENTS = [
  {id:'a1', period:'1000 · were', type:'Bank Fees', side:'book', sign:-1, amount:1250, account:'6100 — Bank Charges', desc:'Aug maintenance fee'},
  {id:'a2', period:'1000 · were', type:'Interest Income', side:'book', sign:1, amount:3750, account:'4100 — Interest Income', desc:'Savings sweep interest'}];
const AUDIT = [
  {at:new Date('2026-11-03T09:12:00'), rec:'1000 · asdasdas', msg:'Reconciliation period created (draft)'},
  {at:new Date('2026-11-02T08:05:00'), rec:'1000 · asdasdas', msg:'Statement parsed — nov_stmt_draft.csv (9 lines)'},
  {at:new Date('2026-08-13T10:24:00'), rec:'1000 · were', msg:'Adjustment added: Interest Income +3,750.00 (book)'},
  {at:new Date('2026-08-13T10:22:00'), rec:'1000 · were', msg:'Adjustment added: Bank Fees −1,250.00 (book)'},
  {at:new Date('2026-08-13T10:19:00'), rec:'1000 · were', msg:'Statement imported — 21 lines'},
  {at:new Date('2026-07-31T16:40:00'), rec:'1000 · 15542', msg:'Reconciliation period created (draft)'}];
const MATCHED_SESSION = [];

const TODAY = new Date(); TODAY.setHours(0,0,0,0);
const d = s => new Date(s+'T00:00:00');
const state = {
  tab:'outst', filter:'all', search:'', selected:[], menu:null, pane:null, active:null, bulkMode:false,
  mm:{book:null, bank:null},
  items:[
    {id:1, type:'book_only', rec:'1000 · were · Aug 2026', date:d('2026-03-15'), ref:'CHQ-10412', desc:'Cheque payment — Rent Q1 (never presented)', amount:-15000},
    {id:2, type:'book_only', rec:'1000 · were · Aug 2026', date:d('2026-07-01'), ref:'CHQ-10438', desc:'Cheque payment — Supplier ABC (follow up)', amount:-22000},
    {id:3, type:'book_only', rec:'1000 · stmt-nov · Nov 2026', date:d('2026-10-29'), ref:'DEP-1029-071', desc:'Deposit in transit — Branch collections', amount:22500},
    {id:4, type:'book_only', rec:'1000 · stmt-nov · Nov 2026', date:d('2026-11-01'), ref:'CHQ-10461', desc:'Cheque payment — Utilities MW (normal timing)', amount:-8900},
    {id:5, type:'bank_only', rec:'1000 · were · Aug 2026', date:d('2026-09-15'), ref:'CHG-0915-002', desc:'Bank Charges — account maintenance', amount:-1250},
    {id:6, type:'bank_only', rec:'1000 · were · Aug 2026', date:d('2026-09-30'), ref:'INT-0930-001', desc:'Interest Received — savings sweep', amount:3750},
    {id:7, type:'bank_only', rec:'1000 · stmt-nov · Nov 2026', date:d('2026-08-15'), ref:'CHG-0815-009', desc:'Duplicate charge — dispute with bank', amount:-2600},
    {id:8, type:'bank_only', rec:'1000 · stmt-nov · Nov 2026', date:d('2026-10-25'), ref:'DEP-1025-004', desc:'Customer direct deposit — Metro Ltd (unrecorded)', amount:45000},
    {id:9, type:'bank_only', rec:'1000 · were · Aug 2026', date:d('2026-07-01'), ref:'CHQ-10438', desc:'Cheque Payment — CHQ 10438 cleared', amount:-22000},
    {id:10, type:'bank_only', rec:'1000 · stmt-nov · Nov 2026', date:d('2026-10-30'), ref:'DEP-1029-071', desc:'Deposit — Branch collections cleared', amount:22500},
  ]
};

/* ═════════ HELPERS ═════════ */
const $ = id => document.getElementById(id);
const fmt = n => (Number(n)||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
const dstr = dt => (dt instanceof Date && !isNaN(dt)) ? dt.toLocaleDateString('en-US',{month:'short',day:'2-digit',year:'numeric'}) : '—';
const dtime = dt => dt.toLocaleString('en-US',{month:'short',day:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
const esc = s => String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const ageDays = it => Math.max(0, Math.floor((TODAY - it.date)/86400000));
function logAudit(rec,msg){ AUDIT.unshift({at:new Date(), rec, msg}); }
function statusPill(st){
  if(st==='reconciled') return '<span class="pill pill-done"><span class="pdot"></span> Reconciled</span>';
  if(st==='ready') return '<span class="pill pill-review"><span class="pdot"></span> Ready for Review</span>';
  if(st==='in-progress') return '<span class="pill pill-progress"><span class="pdot"></span> In Progress</span>';
  return '<span class="pill pill-draft"><span class="pdot"></span> Draft</span>';
}
function agingBadge(it){
  const a = ageDays(it);
  if(it.type==='book_only'){
    if(it.amount < 0){
      if(a>180) return {level:'red',   label:'Stale · '+a+'d', hint:'Void immediately — stale-dated cheque'};
      if(a>90)  return {level:'orange',label:'Aged · '+a+'d',  hint:'Follow up with payee'};
      return null;
    }
    if(a>3) return {level:'amber', label:'In transit · '+a+'d', hint:'Investigate deposit with bank'};
    return null;
  }
  if(a>30) return {level:'amber', label:'Unrecorded · '+a+'d', hint:'Record in books'};
  return null;
}
function requiredAction(it){
  const a = ageDays(it);
  if(it.type==='bank_only') return 'Record in books & match';
  if(it.amount<0 && a>180)  return 'Void / reverse stale cheque';
  if(it.amount<0 && a>90)   return 'Follow up with payee';
  if(it.amount>0 && a>3)    return 'Investigate deposit with bank';
  return 'Carry forward (timing difference)';
}
function availableActions(it){
  if(it.type==='bank_only') return ['journal','match','bank_error','note'];
  const acts=['carry','investigate','note'];
  if(it.amount<0) acts.splice(2,0,'void');
  return acts;
}
function urgencyClass(it){
  const b=agingBadge(it);
  if(b && b.level==='red') return 'urg-red';
  if(b && b.level==='orange') return 'urg-orange';
  return '';
}

/* ═════════ TABS ═════════ */
function setTab(t){
  state.tab=t;
  document.querySelectorAll('.ptab').forEach(b=>b.classList.toggle('on',b.dataset.tab===t));
  document.querySelectorAll('.idx-pane').forEach(p=>p.classList.remove('on'));
  $('pane-'+t).classList.add('on');
}

/* ═════════ RENDER ═════════ */
function visible(){
  let list = state.items;
  if(state.filter==='bank_only'||state.filter==='book_only') list=list.filter(i=>i.type===state.filter);
  if(state.filter==='urgent') list=list.filter(i=>{const b=agingBadge(i);return b&&['red','orange','amber'].includes(b.level);});
  if(state.search){const q=state.search.toLowerCase();list=list.filter(i=>(i.desc+' '+i.ref+' '+i.rec).toLowerCase().includes(q));}
  return list;
}
function render(){
  $('t-recs').textContent=RECS.length;
  $('t-stmts').textContent=IMPORTS.length;
  $('t-adjs').textContent=ADJUSTMENTS.length;
  $('t-outst').textContent=state.items.length;
  $('t-audit').textContent=AUDIT.length;
  renderRecs(); renderStmts(); renderAdjs(); renderAudit(); renderOutstanding();
}
function renderRecs(){
  $('recs-rows').innerHTML = RECS.map(r=>{
    const diff=r.close-r.book;
    return `<tr>
      <td style="font-size:12.5px;color:var(--ink-soft);max-width:200px">${r.acct}</td>
      <td style="font-weight:700;color:var(--deep-2)">${esc(r.stmt)}</td>
      <td>${dstr(d(r.end))}</td>
      <td class="td-right td-mono td-strong">${fmt(r.open)}</td>
      <td class="td-right td-mono td-strong">${fmt(r.close)}</td>
      <td class="td-right td-mono td-strong">${fmt(r.book)}</td>
      <td class="td-right td-mono td-orange">${fmt(diff)}</td>
      <td>${statusPill(r.status)}</td>
      <td style="text-align:right"><button class="cont-link" onclick="openMatcher('${esc(r.stmt)} · ${dstr(d(r.end))}')">Continue →</button></td>
    </tr>`;}).join('');
}
function renderStmts(){
  $('stmts-rows').innerHTML = IMPORTS.length ? IMPORTS.map(i=>`<tr>
      <td style="font-weight:700;color:var(--deep-2)"><i class="fa-solid fa-file-excel" style="color:var(--sec);margin-right:8px"></i>${esc(i.file)}</td>
      <td style="font-size:12px;color:var(--ink-soft)">${esc(i.period)}</td>
      <td class="td-right td-mono td-strong">${i.lines}</td>
      <td style="font-size:12px">${dtime(i.at)}</td>
      <td style="font-size:12px">${esc(i.by)}</td>
      <td><span class="pill pill-done"><span class="pdot"></span> ${i.status}</span></td>
    </tr>`).join('') : `<tr><td colspan="6"><div class="mempty"><i class="fa-solid fa-file-import"></i>No statements imported yet.</div></td></tr>`;
}
function renderAdjs(){
  $('adjs-rows').innerHTML = ADJUSTMENTS.length ? ADJUSTMENTS.map(a=>`<tr>
      <td style="font-size:12px;color:var(--ink-soft)">${esc(a.period)}</td>
      <td style="font-weight:700">${esc(a.type)}</td>
      <td>${a.side==='book'?'Book':'Statement'} ${a.sign>0?'(+)':'(−)'}</td>
      <td class="td-right td-mono td-strong">${fmt(a.amount)}</td>
      <td class="td-mono">${esc(a.account||'—')}</td>
      <td style="font-size:12px">${esc(a.desc||'—')}</td>
    </tr>`).join('') : `<tr><td colspan="6"><div class="mempty"><i class="fa-solid fa-sliders"></i>No adjustments recorded.</div></td></tr>`;
}
function renderAudit(){
  $('audit-count').textContent = AUDIT.length+' events';
  $('audit-body').innerHTML = AUDIT.map(a=>`<div class="audit-item"><span class="audit-dot"></span><div class="audit-txt"><b>${esc(a.rec)}</b> — ${esc(a.msg)}<div class="audit-time">${dtime(a.at)} · Elvis Seyama</div></div></div>`).join('');
}
function renderOutstanding(){
  const list=visible();
  $('n-all').textContent=state.items.length;
  $('n-bank').textContent=state.items.filter(i=>i.type==='bank_only').length;
  $('n-book').textContent=state.items.filter(i=>i.type==='book_only').length;
  $('n-urgent').textContent=state.items.filter(i=>{const b=agingBadge(i);return b&&['red','orange'].includes(b.level);}).length;
  $('head-count').textContent=state.items.length+' open items';
  $('visible-count').textContent=list.length+' item(s)';
  $('empty').style.display=list.length?'none':'block';
  $('rows').innerHTML=list.map(it=>{
    const b=agingBadge(it), acts=availableActions(it), open=state.menu===it.id;
    return `<tr class="${urgencyClass(it)}">
      <td><input type="checkbox" class="row-check" ${state.selected.includes(it.id)?'checked':''} onchange="toggleOne(${it.id},this.checked)"></td>
      <td>${b?`<span class="age-badge age-${b.level}" data-tip="${esc(b.hint)}"><i class="fa-solid fa-clock"></i> ${b.label}</span>`:`<span class="age-badge age-ok">${ageDays(it)}d</span>`}</td>
      <td><span class="pill ${it.type==='bank_only'?'pill-bank':'pill-book'}"><span class="pdot"></span> ${it.type==='bank_only'?'Bank-only':'Book-only'}</span></td>
      <td style="font-size:11.5px;color:var(--ink-soft)">${esc(it.rec)}</td>
      <td style="font-size:12px">${dstr(it.date)}</td>
      <td class="td-mono">${esc(it.ref)||'—'}</td>
      <td class="desc-cell" title="${esc(it.desc)}">${esc(it.desc)}</td>
      <td class="td-right td-mono ${it.amount<0?'td-red':'td-strong'}">${fmt(it.amount)}</td>
      <td class="req-cell">${requiredAction(it)}</td>
      <td style="text-align:right"><div class="row-menu-wrap ${open?'open':''}">
        <button class="row-icon-btn" onclick="event.stopPropagation();toggleMenu(${it.id})"><i class="fa-solid fa-ellipsis-vertical"></i></button>
        <div class="row-menu ${open?'open':''}">
          ${acts.includes('journal')?`<button onclick="openJournal(${it.id})"><i class="fa-solid fa-book-medical"></i> Record in Books (Quick JE)</button>`:''}
          ${acts.includes('match')?`<button onclick="closeMenuState();openMatcher('${esc(it.rec)}', ${it.id})"><i class="fa-solid fa-link"></i> Manual Match in Workspace</button>`:''}
          ${acts.includes('carry')?`<button onclick="openCarry(${it.id})"><i class="fa-solid fa-forward"></i> Carry Forward…</button>`:''}
          ${acts.includes('investigate')?`<button onclick="openInvestigate(${it.id})"><i class="fa-solid fa-user-magnifying-glass"></i> Investigate / Assign…</button>`:''}
          ${acts.includes('void')?`<button class="danger" onclick="openVoid(${it.id})"><i class="fa-solid fa-ban"></i> Void / Reverse (Stale)</button>`:''}
          ${acts.includes('bank_error')?`<button class="danger" onclick="openBankError(${it.id})"><i class="fa-solid fa-triangle-exclamation"></i> Mark as Bank Error</button>`:''}
          <hr><button onclick="openInvestigate(${it.id},true)"><i class="fa-solid fa-note-sticky"></i> Add Investigation Note</button>
        </div></div></td>
    </tr>`;
  }).join('');
  syncBulk();
  syncMenuOverflow();
}
function toggleMenu(id){ state.menu = state.menu===id?null:id; renderOutstanding(); }
function closeMenuState(){ if(state.menu!==null){ state.menu=null; } }
document.addEventListener('click',e=>{ if(!e.target.closest('.row-menu-wrap') && state.menu!==null){ state.menu=null; renderOutstanding(); } });

function toggleOne(id,on){ state.selected = on?[...new Set([...state.selected,id])]:state.selected.filter(x=>x!==id); renderOutstanding(); }
function clearSel(){ state.selected=[]; renderOutstanding(); }
$('check-all').addEventListener('change',e=>{ state.selected = e.target.checked ? visible().map(i=>i.id) : []; renderOutstanding(); });
function syncBulk(){
  $('bulk-num').textContent=state.selected.length;
  $('bulk-bar').classList.toggle('show',state.selected.length>0);
  const all=visible(); $('check-all').checked = all.length>0 && all.every(i=>state.selected.includes(i.id));
}
document.querySelectorAll('#filter-seg .seg-btn').forEach(b=>b.addEventListener('click',()=>{
  document.querySelectorAll('#filter-seg .seg-btn').forEach(x=>x.classList.remove('active'));
  b.classList.add('active'); state.filter=b.dataset.f; renderOutstanding();
}));
$('search').addEventListener('input',e=>{state.search=e.target.value;renderOutstanding();});

/* ═════════ MATCHER MODAL ═════════ */
function openMatcher(sub, presetId){
  $('mm-sub').textContent = sub || 'All open periods';
  state.mm.book=null; state.mm.bank=null;
  if(presetId){
    const it=state.items.find(i=>i.id===presetId);
    if(it) state.mm[it.type==='bank_only'?'bank':'book']=it.id;
  }
  openModal('modal-matcher');
  renderMatcher();
}
function mmPools(){
  const qb=($('mm-search-book').value||'').toLowerCase(), qk=($('mm-search-bank').value||'').toLowerCase();
  return {
    book: state.items.filter(i=>i.type==='book_only' && (i.desc+' '+i.ref).toLowerCase().includes(qb)),
    bank: state.items.filter(i=>i.type==='bank_only' && (i.desc+' '+i.ref).toLowerCase().includes(qk))
  };
}
function renderMatcher(){
  const p=mmPools();
  $('mm-book-count').textContent=p.book.length+' open';
  $('mm-bank-count').textContent=p.bank.length+' open';
  const row=(i,side)=>`<div class="mrow ${state.mm[side]===i.id?'sel':''}" onclick="mmPick('${side}',${i.id})">
      <span class="d">${dstr(i.date)}</span>
      <span class="desc"><span class="t">${esc(i.desc)}</span><span class="r">${esc(i.ref||'—')} · ${esc(i.rec)}</span></span>
      <span class="amt ${i.amount<0?'neg':''}">${fmt(i.amount)}</span></div>`;
  $('mm-book').innerHTML = p.book.length? p.book.map(i=>row(i,'book')).join('') : `<div class="mempty"><i class="fa-solid fa-inbox"></i>No open book-only lines.</div>`;
  $('mm-bank').innerHTML = p.bank.length? p.bank.map(i=>row(i,'bank')).join('') : `<div class="mempty"><i class="fa-solid fa-inbox"></i>No open bank-only lines.</div>`;
  const b=state.items.find(i=>i.id===state.mm.book), k=state.items.find(i=>i.id===state.mm.bank);
  const bar=$('mm-bar'); bar.classList.toggle('show', !!(b||k));
  const btn=$('mm-match');
  if(b&&k){
    const diff=Math.abs(b.amount-k.amount);
    $('mm-bar-txt').innerHTML = `Book <span>${fmt(b.amount)}</span> ↔ Bank <span>${fmt(k.amount)}</span> · Δ <span>${fmt(diff)}</span>`;
    btn.disabled = diff>0.01;
  } else {
    $('mm-bar-txt').innerHTML = `Selected: <span>${b?'book':''}</span>${b&&k?' + ':''}<span>${k?'bank':''}</span> — pick one from each side`;
    btn.disabled = true;
  }
  $('mm-matched-count').textContent = MATCHED_SESSION.length+' matched this session';
}
function mmPick(side,id){ state.mm[side] = state.mm[side]===id?null:id; renderMatcher(); }
function mmClear(){ state.mm.book=null; state.mm.bank=null; renderMatcher(); }
function matchPair(){
  const b=state.items.find(i=>i.id===state.mm.book), k=state.items.find(i=>i.id===state.mm.bank);
  if(!b||!k) return;
  state.items = state.items.filter(i=>i.id!==b.id && i.id!==k.id);
  MATCHED_SESSION.push({b,k,at:new Date()});
  logAudit(k.rec, 'Manual match '+b.ref+' ↔ '+k.ref+' ('+fmt(b.amount)+')');
  mmClear();
  toast('Matched', b.ref+' ↔ '+k.ref+' cleared from outstanding.','ok');
  render(); renderMatcher();
}

/* ═════════ REPORTS ═════════ */
let lastReport=null;
function runReport(kind){
  let head=[], rows=[], title='', sub='';
  if(kind==='summary'){
    title='Reconciliation Summary'; sub='Book vs bank position per period';
    head=['Account','Statement','Period End','Opening','Closing','Book','Difference','Status'];
    rows=RECS.map(r=>[r.acct,r.stmt,r.end,fmt(r.open),fmt(r.close),fmt(r.book),fmt(r.close-r.book),r.status]);
  } else if(kind==='unmatched'){
    title='Unmatched Items'; sub='Bank-only and book-only lines across open periods';
    head=['Period','Kind','Date','Reference','Description','Amount'];
    rows=state.items.map(i=>[i.rec, i.type==='bank_only'?'Bank-only':'Book-only', dstr(i.date), i.ref||'—', i.desc, fmt(i.amount)]);
  } else if(kind==='adjustments'){
    title='Adjustment Ledger'; sub='All recorded reconciliation adjustments';
    head=['Period','Type','Side','Sign','Amount','Account','Description'];
    rows=ADJUSTMENTS.map(a=>[a.period,a.type,a.side,a.sign>0?'+':'−',fmt(a.amount),a.account||'—',a.desc||'—']);
  } else {
    title='Audit Trail'; sub='Module event log';
    head=['Timestamp','Period','Event'];
    rows=AUDIT.map(a=>[dtime(a.at),a.rec,a.msg]);
  }
  lastReport={head,rows,title};
  $('rp-title').textContent=title; $('rp-sub').textContent=sub;
  $('rp-table').innerHTML = `<thead><tr>${head.map(h=>`<th>${h}</th>`).join('')}</tr></thead><tbody>`+
    (rows.length? rows.map(r=>`<tr>${r.map(c=>`<td>${esc(c)}</td>`).join('')}</tr>`).join('') : `<tr><td colspan="${head.length}"><div class="mempty"><i class="fa-solid fa-inbox"></i>No data for this report yet.</div></td></tr>`)+`</tbody>`;
  openModal('modal-report');
}
function exportReport(){
  if(!lastReport) return;
  const csv=[lastReport.head.join(','),...lastReport.rows.map(r=>r.map(f=>`"${String(f??'').replace(/"/g,'""')}"`).join(','))].join('\n');
  const blob=new Blob([csv],{type:'text/csv'}); const url=URL.createObjectURL(blob);
  const a=document.createElement('a'); a.href=url; a.download=lastReport.title.toLowerCase().replace(/\s+/g,'-')+'.csv'; a.click(); URL.revokeObjectURL(url);
  toast('Report exported', lastReport.title+' downloaded as CSV.','ok');
}

/* ═════════ ADJUSTMENT MODAL ═════════ */
function openAdjustmentModal(){
  $('na-period').innerHTML = RECS.map(r=>`<option>${r.acct.split(' — ')[0]} · ${r.stmt}</option>`).join('');
  openModal('modal-adjustment');
}
function saveAdjustment(){
  const amt=parseFloat($('na-amt').value);
  if(!amt||amt<=0){ toast('Invalid amount','Enter an amount greater than zero.','err'); return; }
  const rec={id:'a'+(ADJUSTMENTS.length+1), period:$('na-period').value, type:$('na-type').value, side:$('na-side').value, sign:parseInt($('na-sign').value), amount:amt, account:$('na-acct').value, desc:$('na-desc').value};
  ADJUSTMENTS.unshift(rec);
  logAudit(rec.period,'Adjustment added: '+rec.type+' '+(rec.sign>0?'+':'−')+fmt(amt)+' ('+rec.side+')');
  $('na-amt').value=''; $('na-desc').value='';
  closeModal('modal-adjustment');
  toast('Adjustment saved', rec.type+' recorded on '+rec.side+' side.','ok');
  render();
}

/* ═════════ QUICK JOURNAL MODAL ═════════ */
const item = id => state.items.find(i=>i.id===id);
let jeLines=[];
function suggestLines(it){
  const amt=Math.abs(it.amount);
  if(it.type==='bank_only'){
    if(it.amount<0) return [
      {account_id:6100, debit:amt.toFixed(2), credit:'', memo:it.desc},
      {account_id:1000, debit:'', credit:amt.toFixed(2), memo:'Per bank statement'}];
    const isInt=/interest/i.test(it.desc);
    return [
      {account_id:1000, debit:amt.toFixed(2), credit:'', memo:'Per bank statement'},
      {account_id:isInt?4100:1100, debit:'', credit:amt.toFixed(2), memo:isInt?'Interest received':'Customer receipt (AR)'}];
  }
  return [
    {account_id:1000, debit:amt.toFixed(2), credit:'', memo:'Reversal — stale item'},
    {account_id:2100, debit:'', credit:amt.toFixed(2), memo:'Reverse original: '+it.desc}];
}
function openJournal(id){
  const it=item(id); state.active=it; state.menu=null;
  $('j-sub').textContent=it.desc;
  $('j-recap-t').textContent=(it.ref||'—')+' · '+dstr(it.date);
  $('j-recap-s').textContent=it.rec;
  const a=$('j-recap-amt'); a.textContent=fmt(it.amount); a.classList.toggle('neg',it.amount<0);
  $('j-date').value=TODAY.toISOString().slice(0,10);
  $('j-memo').value=`${it.desc} (${it.ref||'no ref'}) — per bank rec`;
  jeLines=suggestLines(it);
  renderJe();
  openModal('modal-journal');
  renderOutstanding();
}
function addJeLine(){ jeLines.push({account_id:'',debit:'',credit:'',memo:''}); renderJe(); }
function removeJeLine(i){ if(jeLines.length>2){ jeLines.splice(i,1); renderJe(); } }
function renderJe(){
  $('je-lines').innerHTML=jeLines.map((l,i)=>`<div class="je-grid je-row">
    <select class="form-control" onchange="jeLines[${i}].account_id=this.value;checkJe()">
      <option value="">— account —</option>
      ${ACCOUNTS.map(a=>`<option value="${a.id}" ${String(l.account_id)===String(a.id)?'selected':''}>${a.label}</option>`).join('')}
    </select>
    <input class="form-control mono num" type="number" step="0.01" placeholder="0.00" value="${l.debit}" oninput="jeLines[${i}].debit=this.value;jeLines[${i}].credit='';document.querySelectorAll('#je-lines .je-row')[${i}].children[2].value='';checkJe()">
    <input class="form-control mono num" type="number" step="0.01" placeholder="0.00" value="${l.credit}" oninput="jeLines[${i}].credit=this.value;jeLines[${i}].debit='';document.querySelectorAll('#je-lines .je-row')[${i}].children[1].value='';checkJe()">
    <input class="form-control" value="${esc(l.memo)}" oninput="jeLines[${i}].memo=this.value">
    <button class="je-remove" ${jeLines.length<=2?'disabled':''} onclick="removeJeLine(${i})"><i class="fa-solid fa-trash-can"></i></button>
  </div>`).join('');
  checkJe();
}
const drTotal=()=>jeLines.reduce((s,l)=>s+(parseFloat(l.debit)||0),0);
const crTotal=()=>jeLines.reduce((s,l)=>s+(parseFloat(l.credit)||0),0);
function checkJe(){
  const dr=drTotal(), cr=crTotal();
  const bal=Math.abs(dr-cr)<=0.01 && dr>0;
  const acc=jeLines.every(l=>l.account_id);
  const ok=bal&&acc&&$('j-memo').value.trim()&&$('j-date').value;
  const box=$('je-balance');
  box.className='je-balance '+(bal?'ok':'bad');
  box.children[0].textContent=bal?'Balanced ✓':'UNBALANCED';
  $('je-totals').textContent=`Dr ${fmt(dr)} · Cr ${fmt(cr)} · Δ ${fmt(Math.abs(dr-cr))}`;
  $('j-save').disabled=!ok;
}
$('j-memo').addEventListener('input',checkJe); $('j-date').addEventListener('input',checkJe);
function saveJournal(){
  const it=state.active;
  logAudit(it.rec,'Outstanding item recorded in books via Quick JE ('+it.ref+')');
  removeItem(it.id);
  toast('Journal posted','JE recorded for '+it.ref+' — item cleared from outstanding.','ok');
  closeModal('modal-journal');
}

/* ═════════ SLIDE-OVERS ═════════ */
function openPane(name){ state.pane=name; document.body.classList.add('locked');
  document.querySelectorAll('.slideover-overlay').forEach(o=>o.classList.remove('open'));
  document.querySelectorAll('.slideover').forEach(o=>o.classList.remove('open'));
  $('ov-'+name).classList.add('open'); $('so-'+name).classList.add('open'); }
function closePane(){ state.pane=null; state.menu=null; document.body.classList.remove('locked');
  document.querySelectorAll('.slideover-overlay').forEach(o=>o.classList.remove('open'));
  document.querySelectorAll('.slideover').forEach(o=>o.classList.remove('open')); renderOutstanding(); }
function openCarry(id){
  const it=item(id); state.active=it; state.menu=null;
  $('c-sub').textContent=it.ref+' · '+it.desc;
  $('c-target').innerHTML='<option value="">Next open period (auto)</option>'+PERIODS.map(p=>`<option value="${p.id}">${p.label}</option>`).join('');
  $('c-note').value='';
  openPane('carry'); renderOutstanding();
}
function saveCarry(){
  const ids = state.bulkMode ? [...state.selected] : [state.active.id];
  const rec = state.active? state.active.rec : 'bulk';
  ids.forEach(removeItem);
  logAudit(rec,'Carried forward '+ids.length+' item(s) to next open period');
  state.bulkMode=false;
  toast('Carried forward', ids.length+' item(s) rolled to the next open period.','ok');
  closePane();
}
function bulkCarry(){ state.bulkMode=true; openCarry(state.selected[0]); $('c-sub').textContent=state.selected.length+' selected items'; }
function openInvestigate(id,noteOnly){
  const it=item(id); state.active=it; state.menu=null; state.bulkMode=false;
  $('i-title').textContent = noteOnly?'Add Investigation Note':'Investigate / Assign';
  $('i-sub').textContent=it.ref+' · '+it.desc;
  $('i-user').innerHTML=USERS.map(u=>`<option value="${u.id}">${u.name}</option>`).join('');
  $('i-due').value=''; $('i-note').value='';
  openPane('investigate'); renderOutstanding();
}
function saveInvestigate(){
  const ids = state.bulkMode ? [...state.selected] : [state.active.id];
  const rec = state.active? state.active.rec : 'bulk';
  ids.forEach(removeItem);
  logAudit(rec,'Investigation assigned on '+ids.length+' item(s)');
  state.bulkMode=false;
  toast('Investigation assigned', ids.length+' item(s) queued with due date & note.','ok');
  closePane();
}
function bulkInvestigate(){ state.bulkMode=true; openInvestigate(state.selected[0]); $('i-title').textContent='Assign Investigation (bulk)'; $('i-sub').textContent=state.selected.length+' selected items'; }
function openVoid(id){
  const it=item(id); state.active=it; state.menu=null;
  $('v-sub').textContent=it.ref+' · '+ageDays(it)+' days old';
  $('v-recap-t').textContent=it.desc;
  $('v-recap-s').textContent=it.rec+' · '+dstr(it.date);
  $('v-recap-amt').textContent=fmt(it.amount);
  $('v-reason').value=''; $('v-save').disabled=true;
  openPane('void'); renderOutstanding();
}
$('v-reason').addEventListener('input',e=>$('v-save').disabled=!e.target.value.trim());
function saveVoid(){
  const it=state.active;
  logAudit(it.rec,'Stale item voided & reversed: '+it.ref);
  removeItem(it.id);
  toast('Item voided','Reversing journal posted for '+it.ref+'.','ok');
  closePane();
}
function openBankError(id){
  const it=item(id); state.active=it; state.menu=null;
  $('e-sub').textContent=it.ref+' · '+it.desc;
  $('e-note').value=''; $('e-save').disabled=true;
  openPane('bank_error'); renderOutstanding();
}
$('e-note').addEventListener('input',e=>$('e-save').disabled=!e.target.value.trim());
function saveBankError(){
  const it=state.active;
  logAudit(it.rec,'Bank error disputed: '+it.ref);
  removeItem(it.id);
  toast('Flagged as bank error', it.ref+' queued for the dispute report.','ok');
  closePane();
}

/* ═════════ MODALS / FULLSCREEN / MISC ═════════ */
function openModal(id){ $(id).classList.add('open'); document.body.classList.add('modal-open'); }
function closeModal(id){ $(id).classList.remove('open'); document.body.classList.remove('modal-open'); }
document.querySelectorAll('.modal-overlay').forEach(ov=>ov.addEventListener('mousedown',e=>{if(e.target===ov){ov.classList.remove('open');document.body.classList.remove('modal-open');}}));
function setFs(card, on){
  if(!card) return;
  const isOn = card.classList.contains('fs');
  if(on && !isOn){
    const anchor = document.createComment('fs-anchor');
    card.parentNode.insertBefore(anchor, card);
    card._fsAnchor = anchor;
    document.body.appendChild(card);
    card.classList.add('fs');
  } else if(!on && isOn){
    card.classList.remove('fs');
    const anchor = card._fsAnchor;
    if(anchor && anchor.parentNode){ anchor.parentNode.insertBefore(card, anchor); anchor.remove(); }
    card._fsAnchor = null;
  }
  const b = card.querySelector('.fs-btn');
  if(b){
    b.innerHTML = on ? '<i class="fa-solid fa-compress"></i> Collapse' : '<i class="fa-solid fa-expand"></i> Expand';
    b.setAttribute('data-tip', on ? 'Collapse to page width' : 'Expand to full width');
  }
  document.body.classList.toggle('locked', on);
}
function toggleFs(btn){
  const card = btn.closest('.card');
  setFs(card, !card.classList.contains('fs'));
}
document.addEventListener('keydown', e=>{
  if(e.key==='Escape'){
    document.querySelectorAll('.modal-overlay.open').forEach(o=>o.classList.remove('open'));
    document.querySelectorAll('.card.fs').forEach(c=>setFs(c,false));
    if(!state.pane) document.body.classList.remove('modal-open');
    if(!state.pane && !document.querySelector('.modal-overlay.open')) document.body.classList.remove('locked');
  }
});
function syncMenuOverflow(){
  document.querySelectorAll('.table-wrap.menu-open,.card.menu-open').forEach(w=>w.classList.remove('menu-open'));
  const open = document.querySelector('.row-menu.open');
  if(open){
    const wrap = open.closest('.table-wrap'); if(wrap) wrap.classList.add('menu-open');
    const card = open.closest('.card');       if(card) card.classList.add('menu-open');
  }
}
function removeItem(id){ state.items=state.items.filter(i=>i.id!==id); state.selected=state.selected.filter(x=>x!==id); }
function toast(title,sub,type){
  const t=document.createElement('div'); t.className='toast '+(type||'ok');
  const icon=type==='err'?'fa-circle-exclamation':type==='warn'?'fa-triangle-exclamation':'fa-circle-check';
  t.innerHTML=`<i class="fa-solid ${icon}"></i><div><div class="t-title">${title}</div><div class="t-sub">${sub||''}</div></div>`;
  $('toasts').appendChild(t);
  setTimeout(()=>{t.style.transition='opacity .4s, transform .4s';t.style.opacity='0';t.style.transform='translateY(8px)';setTimeout(()=>t.remove(),400);},3400);
}
document.querySelectorAll('.module-item').forEach(el=>el.addEventListener('click',e=>{if(el.getAttribute('href')==='#'){e.preventDefault();document.querySelectorAll('.module-item').forEach(i=>i.classList.remove('active'));el.classList.add('active');}}));

render();
</script>
</body>
</html>
```

---

## FINAL ENFORCEMENT STATEMENT FOR OPENCODE

This is not a suggestion. This is a strict implementation requirement.

Implement the bank reconciliation module so that opening the register, the workspace, the import wizard, or the Outstanding Items control centre reproduces **every element, interaction, validation, animation, and workflow** present in the two mockups above.

If any tab, KPI tile, filter, stepper state, sheet chip, mapping row, preview column, statistic chip, matcher panel, suggestion card, adjustment field, ageing badge, required-action rule, row-menu item, modal, slide-over, bulk operation, report preview, audit event, or finalize/lock behavior is missing or altered — **the implementation is incomplete**.

If the Quick-Add Journal loses its suggestion engine, its balanced-only gate, or its no-horizontal-scroll line grid — **the implementation is incomplete**.

If the Transaction Matcher modal is not extra-wide, or the fullscreen Expand/Collapse stops reparenting to `<body>`, or row menus paint beneath rows, or closed slide-overs leak shadows, or wide tooltips clip against card edges — **the implementation is incomplete**.

If any currency renders as USD/$ instead of MWK "K" — **the implementation is incomplete**.

If the faint centred bottom hairline, glass surfaces, gradient headers, sheen highlights, or spring animations are replaced with generic UI — **the implementation is incomplete**.

Do not stop until every page, partial, component, route, controller, model, migration, seeder, policy, service, form request, validation rule, notification, job, frontend behavior, and test required by this module is fully delivered and integrated into the existing system.
</s>