RECURRING JOURNALS CENTRE — FULL IMPLEMENTATION SPEC "AS DESIGNED IN THE MOCKUP".
Build the financial automation engine: journal templates, schedules, automatic
generation, approval, posting, audit. ALL VALUES INLINE; no mockup dependency.

DO NOT SKIP ANY PAGE: implement ALL TEN pages as separate, menu-reachable routes —
Dashboard, Recurring Journal List, Create Recurring Journal, Journal Templates,
Scheduled Journals, Generated Journals, Approval Queue, Journal History, Reports,
Settings (§4). A combined-card mockup screen maps to separate routes; no page may be
omitted, merged away, or left as a stub. Dashboard is mandatory.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY
as implemented and applies to every recurring-journals page per the registry in §16:
resting slim icon rail with teal Expand; drawer with pin (true toggle, remembered per
page) + X at top-right; Favorites "Pin rails to right side bar" global toggle unchanged.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: journal posting/reversal handlers, GL
account mappings, period-locking, approval workflow engine, notification handlers,
export/print handlers, search/filter/pagination params, auth/permissions and all routes
remain EXACTLY as-is. Generated journals post ONLY through the existing journal posting
handler; reversals ONLY through the existing reversal handler. This spec adds the
automation UI + scheduler orchestration on top, without altering those handlers.

==================== 0 · DISCOVERY ====================
0.1 Inventory existing journal module routes/handlers: journal create/post/reverse,
period locks, approval workflow, numbering, notification channels.
0.2 List CURRENT controls + handlers per page (drives §19 audit).
0.3 Locate: chart of accounts, departments/cost centres/projects, fixed-assets
depreciation source, payroll source, loan schedules, subscription/prepayment registers
(integration points for templates), currency rates.
0.4 Locate user-preference storage (rail prefs live there) + header Favorites menu.
0.5 Confirm scheduler mechanism (cron/queue) availability for §15.

==================== 1 · TOKENS / DIMENSIONS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red:#dc2626; --red-2:#b91c1c; --green:#15803d; --amber:#d97706;
--amber-2:#b45309; --steel:#46708C. html,body{overflow-x:clip}. Text rem per app rule.
prefers-reduced-motion respected.

==================== 2 · STATUS BADGES + CHIPS ====================
2.1 Schedule badges (pill + dot): Active = mint gradient (#ecfdf3→#dcf5e7,
rgba(22,163,74,.28), #15803d, dot #22c55e); Paused rgba(217,119,6,.10)/.35/#b45309
(dot #d97706); Expired/Reversed gray rgba(138,165,167,.15)/.5/#5f7476; Scheduled =
mint; Pending Approval amber; Draft rgba(17,69,75,.07)/.2/#11454b; Posted mint.
2.2 Type chips (.tchip): Standard gray; Accrual amber tint; Depreciation steel tint
rgba(70,112,140,.10)/.4/#46708C; Prepayment teal tint rgba(18,143,142,.10)/.35/#128F8E;
Adjustment steel.
2.3 Mode chips: Auto-post green tint; Approval first gray; Draft only gray.
2.4 Balance chip .okchip.ok "✓ Balanced — debits equal credits" green; failure variant
red. Amounts: debits/credits right tabular; totals bold; tfoot border-top 1.5px #17565d.

==================== 4 · PAGE INVENTORY — BUILD ALL (no skips) ====================
rj.dashboard        "Recurring Journals" dashboard (§5).
rj.index            "Recurring Journal List" (§6).
rj.create           "Create Recurring Journal" (§7) + rj.edit reuses it pre-filled.
rj.templates        "Journal Templates" (§8).
rj.scheduled        "Scheduled Journals" (§9).
rj.generated        "Generated Journals" (§10).
rj.approvals        "Approval Queue" (§11).
rj.history          "Journal History / Audit Trail" (§12).
rj.reports          "Recurring Journal Reports" (§13).
rj.settings         "Recurring Journals Settings" (§14).
Module menu (sidebar/nav) lists all ten in this order.

==================== 5 · DASHBOARD (rj.dashboard) — MANDATORY ====================
5.1 NON-sticky page head: h1 "Recurring Journals" + sub "Automate rent, salaries,
depreciation, interest, subscriptions and accruals — with full control and audit.";
right: [⚙ Settings ghost][📊 View Reports ghost][▶ Run Scheduled Journals secondary]
[➕ Create Recurring Journal CTA].
5.2 KPI ROW 1 (4): hero Total Recurring Journals (teal gradient; sub "{active} active ·
{paused} paused · {expired} expired") · Active Schedules (sub "next run in {n} days") ·
Pending Journals (amber; "Approval queue →") · Failed Generations (red; "View
failure →").
5.3 KPI ROW 2 (4): Generated This Month (sub "{posted} posted · {pending} pending") ·
Total Amount Posted (sub "FY{yyyy} to date") · Upcoming Runs (7d) (sub "{value}
scheduled") · Auto-post Enabled ("{n} of {m} active schedules").
5.4 UPCOMING JOURNAL RUNS card: table Next Run / Journal (700) / Type chip / Frequency /
Amount (bold) / Mode chip / [Run Now secondary-xs]; "Scheduled journals →" link.

==================== 6 · LIST (rj.index) ====================
6.1 Page head: h1 "Recurring Journals" + sub; right [⇩ Export ghost][➕ New Recurring
Journal CTA].
6.2 STATUS BOXES (4): All(t-ink) / Active(t-mint) / Paused(t-amber) / Expired(t-red);
live counts; active teal ring; click sets EXISTING filter param.
6.3 CONTROLS: search by name + Status select (All/Active/Paused/Expired) + Frequency
select (Daily/Weekly/Monthly/Quarterly/Yearly).
6.4 TABLE: Journal Name (700) / Reference (mono RJ-№) / Type chip / Frequency ("Monthly
· day 1") / Next Run / Last Generated / Amount (bold) / Status badge / actions:
[▶ run][👁 view] + ⋯ menu [✎ Edit · ⧉ Duplicate ·  Pause / ▶ Resume · 🕘 View History ·
🗑 Delete (confirm; blocked when generated journals exist — surface message)] ; Expired
rows show [Renew]. Pagination existing.

==================== 7 · CREATE / EDIT (rj.create) ====================
7.1 Sticky head: h1 + sub "Define the entry once — the engine generates, approves and
posts on schedule."; right Cancel ghost + seg [Save Draft ghost | Activate Schedule ⚡
CTA].
7.2 SECTION "Basic Information": g4: Journal Name* (sp2) / Reference (auto, disabled) /
Journal Type select (Standard/Accrual/Depreciation/Prepayment/Adjustment) /
Description (sp2) / Start Date / End Date / Currency + [🗂 Use Template] (prefills from
§8 template).
7.3 SECTION "Journal Lines": table Account select / Description input / Debit input /
Credit input / Department select / Cost Centre select / delete; tfoot Totals; header
[⧉ Copy Previous Lines][＋ Add Line]; below: .okchip.ok "✓ Balanced…" (live) +
[Validate Balance] + tax chip; multi-debit/multi-credit; balancing enforced before
Activate; tax calculation per item tax settings; multi-currency via existing rates.
7.4 SECTION "Scheduling": g4: Frequency select (Daily/Weekly/Monthly/Quarterly/
Semi-annually/Annually/Custom) / Day of Month (or Day of Week) / Occurrences / Next
Execution (auto, disabled) / Generation Mode select (Auto-post / Draft only / Approval
first) / Email Notification select; buttons [👁 Preview Schedule] (renders next-run
chips: dates + "+n more") + [⚗ Test Run] (generates a non-persisted draft, shows result).
7.5 EDIT mode: same screen pre-filled; schedule changes audited (§12).

==================== 8 · TEMPLATES (rj.templates) ====================
8.1 Page head: h1 "Journal Templates" + right [＋ New Template CTA].
8.2 TEMPLATE CARDS (grid 3→2→1): title + description line (DR account · CR account ·
amount · frequency · integration note e.g. "linked to Fixed Assets / Payroll / Loans");
foot buttons [Use Template secondary][✎ Edit][ Duplicate][🗑 Delete (danger-o,
confirm)]; [Share] where permissions allow. Examples seeded from existing data: Monthly
Rent, Depreciation — Vehicles, Loan Interest, Insurance Premium Amortisation, Software
Subscriptions, Salaries Accrual.
8.3 "Use Template" → rj.create prefilled (lines + schedule defaults).

==================== 9 · SCHEDULED (rj.scheduled) ====================
9.1 Page head: h1 "Scheduled Journals" + sub; chip "{n} runs next 30 days".
9.2 TABLE: Next Run / Journal (700) / Frequency / Amount (bold) / Mode chip / Status
(Scheduled mint / Paused amber) / actions: [Run Now secondary-xs] + ⋯ [ Skip Run ·
📅 Reschedule · ⏸ Pause · ✎ Edit Schedule]; Paused rows [Resume].
9.3 Skip Run marks occurrence skipped (audited); Reschedule opens date picker; Pause/
Resume toggle status; Run Now triggers immediate generation (§15).

==================== 10 · GENERATED (rj.generated) ====================
10.1 Page head: h1 "Generated Journals" + sub "Everything the automation created —
review, approve, post, reverse, print."; right [⇩ Export ghost].
10.2 Header chips: Draft / Pending / Posted / Reversed counts.
10.3 TABLE: Journal № (mono RJV-№) / Date / Reference · Source (RJ-№) / Amount (bold) /
Status badge / actions per status: Pending → [Approve][Reject]; Draft → [Post][✎ Edit
(if allowed by settings)]; Posted → [👁][🖨] + ⋯ [ Reverse · 🕘 Audit]; Reversed → [👁].
10.4 Approve/Post/Reverse call EXISTING handlers; "Edit before posting" only when
settings allow and status Draft.

==================== 11 · APPROVAL QUEUE (rj.approvals) ====================
11.1 Page head: h1 "Approval Queue" + sub; chip "comments tracked".
11.2 QUEUE CARD per pending journal: mono RJV-№ + description + amount + "submitted by
{engine/user}" + right [Approve secondary][Reject danger-o][Request Changes ghost];
comment input below (mandatory on Reject / Request Changes; stored in approval history).
11.3 Approval history list per item (who/when/comment) — tracked immutably.

==================== 12 · HISTORY / AUDIT (rj.history) ====================
12.1 Page head: h1 "Journal History / Audit Trail" + right [⇩ Export History][🕘 View
Audit Log].
12.2 IMMUTABLE audit rows (.arow): timestamp mono / actor (Engine or user) / action
text with bold refs — created, modified, generated, auto-posted, failed (+reason +
retry), reversed (+reason), approved/rejected (+comment), schedule changes.
12.3 Filters: journal ref search + event type + date range. Export Excel/PDF.

==================== 13 · REPORTS (rj.reports) ====================
Report cards (grid 3→2→1): Recurring Journal Summary · Scheduled Journal Report ·
Generated Journal Report · Journal Posting History · Failed Journal Runs · Expired /
Upcoming Control. Each: title, description, PDF + Excel chips, Open →. Existing report
pages if present; else MINIMAL pages using the system report pattern; buttons
[Generate][Print][Export PDF][Export Excel].

==================== 14 · SETTINGS (rj.settings) ====================
g3 read/edit card: Journal Numbering (RJV-{yyyy}-{seq:6}) / Approval Workflow
(thresholds e.g. "> 1M → Finance Manager") / Auto-posting Rules (e.g. "auto-post unless
type = Accrual") / Notifications (email 1 day before run; failure alerts; channels) /
Period Locking ("block generation into locked periods") / Default Accounts (suspense
for unmatched; per-type defaults). Existing settings handlers; create minimal page only
if absent.

==================== 15 · AUTOMATION ENGINE RULES ====================
15.1 Scheduler runs daily; for each Active schedule due: generate journal (DR/CR lines
from template with department/cost-centre), reference RJ-№, number RJV-{yyyy}-{seq:6}.
15.2 Generation Mode: Auto-post → post via existing handler; Draft only → save Draft;
Approval first → route to rj.approvals.
15.3 PERIOD LOCK: generation into a locked period = Failed run (reason "period locked"),
no posting; retried next unlock or manual Run Now.
15.4 FAILURES: logged with reason + retry count; dashboard Failed Generations counts
them; [Run Now] retries manually.
15.5 OCCURRENCES/EXPIRY: decrement occurrences per run; when 0 or End Date passed →
status Expired (Renew action re-activates with new dates).
15.6 NOTIFICATIONS: email per schedule setting (before/after posting) + failure alerts
via existing notification handlers.
15.7 TEST RUN: produces a non-persisted preview journal (marked "test") — never posts.
15.8 Multi-company/multi-currency: per-schedule company + currency; conversions via
existing rate tables.

==================== 16 · RAILS REGISTRY (per page; rails feature unchanged) =======
rj.dashboard → Quick Nav [Recurring Journals List, Run Scheduled, Generated Journals,
Reports].
rj.index → Views [All Journals(active), Active, Paused, Expired] + Reports
[Scheduled Journal Report, Failed Runs].
rj.create/edit → Quick Nav [Journal Templates, Recurring Journals List, Settings].
rj.templates → Quick Nav [New Template, Recurring Journals List, Create Journal].
rj.scheduled → Views [Scheduled(active), Paused] + Quick Nav [Run Now, List].
rj.generated → Views [All Generated(active), Draft, Pending Approval, Posted,
Reversed] + Quick Nav [Approval Queue, List].
rj.approvals → Quick Nav [Generated Journals, List].
rj.history → Quick Nav [Generated Journals, Scheduled, List].
rj.reports → Quick Nav [List, Scheduled, History].
rj.settings → Quick Nav [List, Templates].

==================== 17 · ACCESSIBILITY / RESPONSIVE ====================
17.1 aria: status boxes aria-pressed; ⋯ menus aria-haspopup; focus rings #94a3b8;
tables th scope; balanced/failure chips aria-live.
17.2 ≤1100px statgrid 2-col; ≤1000px KPI 2-col + mcards/repcards 2-col; ≤768px slim
rail hidden, g4 → 1fr 1fr, tables horizontal-scroll inside cards; no horizontal PAGE
scrollbar at 1280/1024/768.

==================== 18 · CONSTRAINTS ====================
No changes to rails feature or other modules; no posting/reversal/approval/period-lock
handler changes; no new packages; ONE shared component/CSS per pattern; no hardcoded
sample data (live ledger only); audit trail immutable; DO NOT SKIP ANY PAGE (§4).

==================== 19 · VERIFY (EVERY PAGE — ALL TEN) ====================
19.1 ROUTE CHECK: all ten routes exist, render, and are reachable from the module menu;
dashboard present with KPIs + upcoming runs.
19.2 ACTION AUDIT: every button (dashboard four actions + Run Now, list
run/view/edit/duplicate/pause/resume/history/delete/renew/export, create Save Draft/
Activate/Use Template/Add Line/Copy Previous/Validate/Preview/Test Run, templates
use/edit/duplicate/delete, scheduled run/skip/reschedule/pause/resume/edit, generated
approve/reject/post/edit/view/print/reverse/audit, approvals approve/reject/request-
changes + comments, history export, reports opens, settings edits) triggers the SAME
handler/route as pre-implementation where it existed (spot-click each).
19.3 ENGINE: schedule generates on due date per mode; period-lock produces Failed run;
occurrences/expiry flip to Expired; test run never persists; reversals via existing
handler; approvals require comment on reject.
19.4 MATH: lines balance enforced; tfoot totals = sums; KPI counts = table rows.
19.5 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as
rails.html on these and all other pages; pages render §16 registries.
19.6 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; page-route table (all ten, confirmed built); action-mapping
table (old control → new location → handler confirmed same); status/chip table; rail
registry per page; engine event log samples; confirmation rails + all existing
functionality unchanged and NO PAGE SKIPPED.