# IMPLEMENTATION PROMPT — "Records & Clearance Desk" (Laravel) — v4

## 0. MISSION & GUARDRAILS

You are building a **simple, document-centric, workflow-oriented** records system for a Sacco
(loan + membership applications). This is **NOT** a full document management system. The core
loop is:

> Capture a record → attach/upload documents → classify the application → track status →
> route for CRB clearance → search/report → maintain an audit trail.

Hard rules:
1. **`applications` is the central table.** Documents, CRB clearances, status history and notes
   all attach to it. Loan- and membership-specific fields live in 1-to-1 child tables.
2. Keep it boring and standard: Laravel conventions, Form Requests, Policies, Eloquent
   relationships, no exotic architecture. No API needed — server-rendered Blade only.
   Do not scaffold API routes, API resources, or Sanctum tokens; this system is Blade-only.
3. The UI must replicate the provided **mockup.html** (markup structure, CSS, badges, tables,
   tabs, modals). Port its CSS to `resources/css/app.css` and its JS patterns to Alpine.js.
4. Every important action is audit-logged. Every file download/view is authorized. Documents
   live on a **private disk** and are streamed through authorized routes only.
5. **Branch scoping is enforced by default, and is determined by the user's branch, not
   their role.** Every user (including `admin`/`viewer`) belongs to exactly one branch. Only
   users whose branch is flagged as the **head office** see records across all branches
   (their own plus every other branch); everyone else sees only their own branch's records,
   unless `settings.branch_scoping_enabled = 0`. Role still governs *what actions* a user may
   take (§8) — head-office status only governs *which branches' records they can see*.
   See §2.1, §4, and §8.
6. Ask a clarifying question only when something is genuinely ambiguous; otherwise decide
   sensibly and note the decision.

## 1. TECH STACK

| Layer | Choice |
|---|---|
| PHP | ≥ 8.2 |
| Framework | Laravel 11/12 |
| DB | MySQL / MariaDB |
| Auth | Laravel Breeze (Blade stack) — auth scaffolding only |
| JS | Alpine.js (tabs, dropdowns, modals, bulk-select bar, toasts) |
| CSS | The mockup's custom CSS, compiled through Vite (`resources/css/app.css`) |
| Exports | Native streamed CSV (`fputcsv`). PDF = print-friendly Blade view (browser print). `barryvdh/laravel-dompdf` optional later |
| Optional pkgs (later only) | `spatie/laravel-activitylog`, `maatwebsite/excel`, `laravel/scout` |

Setup commands:
```bash
laravel new sacco-records            # or composer create-project
composer require laravel/breeze --dev
php artisan breeze:install blade --no-interaction
# create MySQL DB, set .env (DB_*; FILESYSTEM_DISK local)
php artisan make:model ... etc. per schema below
```

## 2. DATABASE SCHEMA

Migration order and exact columns. Use `$table->foreignId(...)->constrained()` and cascade
where noted. All tables get `id` + timestamps unless stated.

### 2.1 `branches`
code (string 10, unique), name, region, officer_in_charge (nullable),
**is_head_office (bool default false — exactly one branch should have this set; drives
cross-branch visibility, see §4)**, timestamps.
Add a DB-level safeguard (a small model-level check in `Branch::saving()`, since MySQL can't
easily do a single-row-true unique constraint) that prevents saving a second branch with
`is_head_office = true` — reject with a validation error naming the existing head office
branch instead.

### 2.2 `users` (modify Breeze migration)
name, email, password, **role** (string 20, default `'records_officer'`; values:
`admin`, `records_officer`, `credit_officer`, `viewer`), **branch_id** (FK branches,
restrictOnDelete, **required for every user regardless of role** — an admin or viewer sees
all branches only because their `branch_id` points at the head-office branch, not because of
their role), is_active (bool default true), last_login_at (nullable), timestamps.

### 2.3 `members`
membership_number (string 12, unique, format `MEM-000000`), first_name, last_name,
national_id (string 20, unique, format `345678/10/1`), phone (string 20, nullable),
email (nullable), employer (nullable), employment_type (nullable),
monthly_income (decimal 12,2 nullable), branch_id (nullable FK), joined_at (date nullable),
status (string 15 default `active`: active/inactive/suspended), timestamps.
Indexes: national_id, phone, (last_name, first_name).

### 2.4 `loan_products`
code (unique), name, requires_collateral (bool default false), requires_crb (bool default true),
max_amount (decimal 15,2 nullable), default_term_months (int default 12), is_active (bool),
sort_order (int default 0), timestamps.

### 2.5 `sequences`  *(number generator)*
type (string 20 unique — `loan`, `membership`, `member`), last_number (unsignedBigInteger default 0).

### 2.6 `applications`  ← CENTRAL TABLE
- application_number (string 12, unique)
- application_type (string 12: `loan` | `membership`)  — PHP enum, NOT a lookup table
- member_id (nullable FK members, nullOnDelete)  — null for membership applicants until approved
- applicant_name (string)  — denormalized for display/search
- national_id (string 20 nullable), phone (string 20 nullable) — snapshot for search
- branch_id (FK branches, restrictOnDelete)  — **drives branch-scoped visibility, see §4/§8**
- application_date (date)
- status (string 25 default `draft`) — values: `draft, submitted, under_review,
  awaiting_documents, awaiting_crb, crb_cleared, crb_flagged, approved, rejected, archived`
- crb_status (string 25 nullable) — values: `not_submitted, awaiting_submission, submitted,
  awaiting_response, cleared, flagged, failed, requires_review` (null for membership)
- docs_completion (tinyInteger default 0) — denormalized checklist % (maintained by service)
- assigned_user_id (nullable FK users, nullOnDelete)
- created_by (FK users, restrictOnDelete)
- archived_at (nullable timestamp)
- timestamps, softDeletes
- Indexes: status, crb_status, application_type, application_date, branch_id, member_id,
  docs_completion; fulltext not required (use LIKE).

### 2.7 `loan_applications` (1-1 child)
application_id (FK applications, cascadeOnDelete, **unique**), loan_product_id (FK loan_products),
requested_amount (decimal 15,2), term_months (int nullable), interest_rate_pm
(decimal 5,2 nullable, per-month %), purpose (text nullable), repayment_channel (string nullable),
timestamps.

### 2.8 `membership_applications` (1-1 child)
application_id (FK applications, cascadeOnDelete, **unique**), applicant_type (string 15:
`individual|joint|corporate`), kyc_notes (text nullable), approved_membership_number
(string nullable — set when approved & member created), timestamps.

### 2.9 `document_types`
name, slug (unique), category (string 20: `form, identification, financial, employment,
collateral, kyc, clearance, other`), applies_to (json — array of `["loan","membership"]`),
is_required (bool — drives checklist), **allows_multiple (bool default false — true for types
like Collateral/KYC Documents where more than one file may be attached)**,
accepted_formats (string default `pdf,jpg,jpeg,png`), max_size_mb (int default 10),
is_active (bool default true), sort_order (int), timestamps.

### 2.10 `documents`
document_type_id (FK document_types), original_filename, stored_path, mime_type,
file_size (unsignedInteger), version (smallInteger default 1), replaces_document_id
(nullable self-FK), uploaded_by (FK users), description (nullable), status (string 15 default
`under_review`: under_review/verified/rejected/superseded), timestamps, softDeletes.

### 2.11 `application_documents` (pivot — active doc(s) of each type per application)
application_id (FK applications, cascadeOnDelete), document_id (FK documents, cascadeOnDelete),
document_type_id (FK document_types) — denormalized for constraint,
timestamps.
**Constraint changed from v1:** no longer a blanket `unique(application_id, document_type_id)`.
Instead:
- For a `document_type` with `allows_multiple = false`: application code enforces (in
  `DocumentService::attach`, inside a transaction) that at most one **active** row exists for
  that `(application_id, document_type_id)` pair — replacing via the existing replace flow,
  not the DB constraint.
- For `allows_multiple = true`: any number of active rows may exist for that pair; each is a
  distinct attachment (e.g. multiple collateral photos, multiple KYC documents).
- Add a partial/application-level unique guard instead at the DB level:
  `unique(application_id, document_id)` (a given document row is only ever pivoted once).

### 2.12 `crb_clearances` (one row per application, updated in place)
application_id (FK applications, cascadeOnDelete, **unique**), status (string 25 default
`awaiting_submission`), reference_number (nullable), submitted_at (nullable datetime),
response_at (nullable datetime), result (text nullable), remarks (text nullable),
attempts (tinyInteger default 0), processed_by (nullable FK users, nullOnDelete), timestamps.

### 2.13 `application_status_history`
application_id (FK applications, cascadeOnDelete), from_status (string 25 nullable),
to_status (string 25), changed_by (FK users), reason (nullable), timestamps.
Index: application_id, created_at.

### 2.14 `notes`
application_id (FK applications, cascadeOnDelete), user_id (FK users), body (text), timestamps.

### 2.15 `audit_logs`
user_id (nullable FK users, nullOnDelete), action (string 50 — dotted verb, e.g.
`document.uploaded`, `status.changed`, `crb.submitted`), application_id (nullable FK,
nullOnDelete — denormalized for fast per-record filtering), auditable_type + auditable_id
(nullable morphs), description (text), metadata (json nullable), ip_address (string 45 nullable),
created_at timestamp (no updated_at). Indexes: application_id, action, created_at.

### 2.16 `settings`
key (string unique), value (text nullable). Seed defaults (see §10). Includes new key
`branch_scoping_enabled` (default `1`).

## 3. ENUMS (PHP 8.1+ backed enums in `app/Enums`)

- `ApplicationType: string` { Loan='loan', Membership='membership' } → `label()`.
- `ApplicationStatus: string` — all 10 values; methods: `label()`, `badgeClass()`
  (mockup classes `b-draft`…`b-arch`), and static `transitions(): array`:
  ```
  draft            → [submitted]
  submitted        → [under_review, awaiting_documents, awaiting_crb, rejected]
  under_review     → [awaiting_documents, awaiting_crb, approved, rejected]
  awaiting_documents → [submitted, under_review, rejected]
  awaiting_crb     → [crb_cleared, crb_flagged, under_review, rejected]
  crb_cleared      → [approved, rejected, under_review]
  crb_flagged      → [under_review, rejected]
  approved         → [archived]
  rejected         → [archived]
  ```
  Method `canTransitionTo(self $to): bool`. `archived` reachable from any status by admin only.
- `CrbStatus: string` — 8 values above; `label()`, `badgeClass()` (`c-none`…`c-rev`).
- `DocumentFileStatus: string` { UnderReview, Verified, Rejected, Superseded }.
- `UserRole: string` { Admin, RecordsOfficer, CreditOfficer, Viewer } with `label()`.
- `ApplicantType: string` { Individual, Joint, Corporate }.

Cast on models: `status => ApplicationStatus::class`, etc.

## 4. MODELS & RELATIONSHIPS (`app/Models`)

- **Application**: belongsTo member, branch, assignedUser (`assigned_user_id`), creator
  (`created_by`); hasOne loanDetail (`LoanApplication`), membershipDetail, crbClearance;
  hasMany statusHistory, notes; belongsToMany documents via `application_documents`
  (withPivot document_type_id); hasOneThrough-style helper for active docs.
  Scopes: `scopeOfType`, `scopeSearch($q)` (see §9), `scopeCrbQueue` (crb_status in
  awaiting set), `scopeMissingDocs` (`docs_completion < 100` and status not in
  [approved, rejected, archived]).
  **`scopeVisibleTo(Builder $query, User $user)`** — if
  `Setting::get('branch_scoping_enabled', true)` and `$user->branch->is_head_office` is
  false: constrain `where('branch_id', $user->branch_id)`. If the user's branch **is** the
  head office, or scoping is disabled: no constraint (all branches visible). This check is
  purely about the user's own `branch_id → is_head_office` flag — it does not look at
  `$user->role` at all, so an admin or viewer sitting at a regular branch is just as
  branch-scoped as a records_officer there, and a records_officer who happens to be posted
  at head office sees every branch. Apply this scope in **every** controller method that
  lists or resolves an `Application` — index, show, search, dashboard counts, reports — via
  route-model-binding-safe checks (404, not 403, for out-of-scope records the user has no
  business knowing exist) or an explicit query constraint before `findOrFail`.
  Accessors: `days_waiting_crb` (submitted_at → now, only when status awaiting),
  `is_overdue_crb` (> setting crb_sla_days), `type_badge`.
- **User**: hasMany assignedApplications; belongsTo branch; helper `initials()`, `isAdmin()`,
  **`isHeadOffice(): bool`** (delegates to `$this->branch->is_head_office`) — this, not role,
  is what any branch-visibility check should call.
- **Member**: hasMany applications; accessor `full_name`.
- **Document**: belongsTo type, uploader, replaces (`self`), replacedBy (inverse);
  belongsToMany applications via pivot. Accessor `extension`.
  **`scopeVisibleTo(Builder $query, User $user)`** — documents have no `branch_id` of their
  own (they belong to whichever application they're attached to), so this scope constrains
  via the pivot/relationship: `whereHas('applications', fn($q) => $q->visibleTo($user))`.
  `DocumentRepositoryController@index` and `MissingDocumentsController@index` **must** apply
  this scope before any of their own `type`/`branch`/`q` filters — otherwise those two
  listing pages would leak the existence and metadata of every branch's documents even
  though opening any individual out-of-branch file is still blocked by `DocumentPolicy`
  (§8). As with §9's search filter, if a non-head-office user passes an explicit `branch`
  query param for another branch, ignore it rather than honoring it.
- **ApplicationDocument** pivot model with `$attributes` access.
- **CrbClearance**: belongsTo application, processor.
- **ApplicationStatusHistory**, **Note**, **AuditLog**: simple belongsTo.
- **Setting**: static `get($key, $default)` with per-request cache.

## 5. SERVICES (`app/Services`)

### 5.1 `NumberingService`
`nextNumber(string $type): string` — wraps `DB::transaction` + `lockForUpdate` on the
`sequences` row; formats: loan `LA-` + 6-digit zero-pad, membership `MA-`, member `MEM-`.
Format prefixes read from settings (`loan_number_format`) but padding constant for MVP.

### 5.2 `DocumentChecklistService`
- `requiredTypesFor(Application $app): Collection<DocumentType>` — where `is_active`,
  `applies_to` contains app type, `is_required`. Extra rule: collateral type only required if
  loan product `requires_collateral`.
- `evaluate(Application $app): array{items: Collection, percent: int}` — each item:
  type + state (`received | missing | requested`) from `application_documents`. For a type
  with `allows_multiple = true`, "received" means **≥1** active document of that type is
  attached (not exactly one); count of attached files is shown alongside the state for those
  types. CRB Report type shown as informational state `system` (never counts toward %).
- Persists `applications.docs_completion` after every upload/replace/delete (call from
  `DocumentService` and from an artisan command `app:checklist:refresh` for repair).

### 5.3 `DocumentService`
`attach(Application, UploadedFile, DocumentType, ?description): Document` — inside a
`DB::transaction`, verify the file's **actual** content type via `finfo`/magic-byte detection
(not just the client-reported MIME) against `document_type.accepted_formats`; store on disk
`private` under `documents/{application_id}/`, create `documents` row (version = existing
max version for that type+app + 1, or 1). Pivot behavior depends on
`document_type.allows_multiple`:
  - `false`: if an active pivot already exists for this `(application, type)`, reject with a
    validation error directing the user to "Replace" instead — do not silently create a
    second active row.
  - `true`: always insert a new pivot row alongside any existing ones.
`replace(Document $old, UploadedFile, ?description): Document` — old doc → `superseded`,
new row `replaces_document_id` = old id, pivot swapped (old pivot row removed, new one
inserted) — applies to both single- and multi-doc types (replaces one specific attachment,
not the whole set).
`delete(Document)` — soft delete, detach pivot. Every call fires `AuditService` and
`DocumentChecklistService::evaluate()` to refresh `docs_completion`.

### 5.4 `ApplicationWorkflowService`
`create(array $data): Application` — generate number, snapshot applicant_name/national_id/phone
(from selected member or typed-in applicant), create child row (loan/membership), write status
history `null → draft|submitted`, audit `application.created`.
`advance(Application, ApplicationStatus $to, ?reason): void` — wrapped in `DB::transaction`
with `lockForUpdate()` on the application row to prevent two concurrent requests both passing
validation before either writes; validate transition (`ApplicationStatus::canTransitionTo`)
against the **freshly locked** row's current status, update, write
`application_status_history`, audit.
`assign(Application, User)` — updates `assigned_user_id`, audit `application.assigned`, and
writes a stub notification (`Log::info` for MVP, matching the pattern used for reminders in
§15) so the assignee is told to check the record — replace with real mail/SMS later without
changing the call site.
`approveMembership(Application)` — on approved: create `members` row (next MEM number),
link member_id, set approved_membership_number.

### 5.5 `CrbService`
`submit(Application, User)` — create/update crb_clearance (status `submitted`, attempts++,
submitted_at now, reference_number = `CRB/{Y}/{rand5}`), set application crb_status
`submitted` → then immediately `awaiting_response` (mock gateway), audit `crb.submitted`.
`recordOutcome(Application, CrbStatus $outcome, ?result, ?remarks, User)` — response_at now,
set clearance + application crb_status, auto-advance application status to `crb_cleared` /
`crb_flagged` when currently `awaiting_crb`, audit. `resubmit()` for failed.

### 5.6 `AuditService`
`log(string $action, ?Application $app, string $description, array $meta = [], ?Model $subject)`
— captures auth user + IP. Called explicitly from services/controllers **and** wired via
observers as a safety net (guard against double-logging with action keys).

### 5.7 `SearchService`
`run(array $filters): LengthAwarePaginator` — applies `Application::visibleTo($user)` (§4)
before any of the filters in §9, so branch-scoped users never see out-of-branch results in
search either.

### 5.8 `ReportService` + `app/Reports`
Abstract `Report` class: `key(), title(), description(), query(), columns()` (column =
header + closure). Every report's base `query()` applies `Application::visibleTo($user)`
before report-specific filters, so a non-head-office user of any role running "Pending
Applications" only sees their own branch's rows; a head-office user sees all. One class each:
`LoanRegister, MembershipRegister, PendingApplications, AwaitingCrb, CrbTurnaround,
MissingDocuments, ByBranch, ByProduct, ByStatus, DocumentsUploaded, DocumentActivity`.
Registry returns all for `/reports` cards. CSV export: `streamedResponse` writing header +
rows via `fputcsv`.

## 6. OBSERVERS & EVENTS

Register in `AppServiceProvider::boot`:
- `DocumentObserver` (created → audit `document.uploaded`; deleted → `document.deleted`).
- `ApplicationObserver` (updated: if `status` dirty → history row + audit `status.changed`;
  if `assigned_user_id` dirty → audit).
- `NoteObserver` (created → audit `note.added`).
- `CrbClearanceObserver` (created/updated → audit only when not already logged by service —
  use `$model->wasChanged` + a flag).

## 7. ROUTES (`routes/web.php`)

Auth (Breeze) + everything below inside `auth` middleware. File-serving routes additionally
get `throttle:document-access` (see §17).

```
GET  /dashboard                       DashboardController@index

# Applications
GET  /applications                    ApplicationController@index        (tab: all|loan|membership via ?tab=)
GET  /applications/loans              → @index tab=loan          name applications.loans
GET  /applications/membership         → @index tab=membership    name applications.membership
GET  /applications/create             @create   (?type=loan|membership)
POST /applications                    @store
GET  /applications/{application}      @show                        (detail page w/ tabs)
GET  /applications/{application}/print      @print   (single-application summary sheet, print-friendly)
PATCH /applications/{application}/status    StatusController@update
PATCH /applications/{application}/assign    AssignController@update
POST /applications/{application}/notes      NoteController@store

# Documents
POST   /applications/{application}/documents        DocumentController@store
GET    /documents/{document}/view                   @view      (inline stream, throttled)
GET    /documents/{document}/download                @download  (attachment stream, throttled)
POST   /documents/{document}/replace                @replace   (new version)
DELETE /documents/{document}                        @destroy
GET    /documents                                   DocumentRepositoryController@index  (filters: type, branch, q — branch-scoped, see §4/§8)
GET    /documents/missing                           MissingDocumentsController@index                 (branch-scoped, see §4/§8)

# CRB
GET   /clearance                      CrbQueueController@index    (?status= filter, tab queue|history)
POST  /applications/{application}/crb/submit        CrbController@submit
PATCH /applications/{application}/crb               CrbController@outcome   (cleared|flagged|failed|requires_review)
POST  /clearance/batch-submit         CrbController@batchSubmit

# Search & reports & audit
GET /search                           SearchController@index
GET /reports                          ReportController@index
GET /reports/{report}                 ReportController@show       (?format=csv|print)

# Admin (middleware role:admin)
resource /admin/users                 UserController
resource /admin/branches              BranchController
resource /admin/document-types        DocumentTypeController
GET|PATCH /admin/settings             SettingController@edit@update
GET /audit                            AuditLogController@index    (?filter=docs|status|crb|notes|sys)
```

Route names consistently for sidebar active states (`request()->routeIs()`). Every route
that resolves an `Application` by ID must apply `visibleTo($user)` before `findOrFail` —
prefer `Application::visibleTo(auth()->user())->findOrFail($id)` over route-model binding
where scoping matters, so an out-of-branch ID 404s instead of leaking existence via a 403.

## 8. AUTHORIZATION (Policies)

Two independent axes govern access: **role** decides *which actions* are permitted at all;
the user's own `branch->is_head_office` flag (§4) decides *which branches' records* those
actions can be performed against. A capability marked ✔ below is still subject to branch
visibility — e.g. a `credit_officer` who can "Approve / reject" only does so for applications
their `visibleTo` scope actually returns.

Role matrix (implement in Policies + a `role:` middleware alias for admin section):

| Capability | admin | records_officer | credit_officer | viewer |
|---|---|---|---|---|
| View records (within visible branches) | ✔ | ✔ | ✔ | ✔ |
| Create/edit applications | ✔ | ✔ | ✔ | ✘ |
| Upload/replace documents | ✔ | ✔ | ✔ | ✘ |
| Delete documents | ✔ | ✔ | ✘ | ✘ |
| Advance status (non-final) | ✔ | ✔ | ✔ | ✘ |
| Approve / reject application | ✔ | ✘ | ✔ | ✘ |
| Submit CRB | ✔ | ✔ | ✔ | ✘ |
| Record CRB outcome | ✔ | ✘ | ✔ | ✘ |
| Admin section / audit log | ✔ | ✘ (audit read ✔) | ✘ | ✘ |

**Which branches** a user's "View"/"Create"/"Upload" etc. actually reach is separate and
purely a function of `isHeadOffice()` (§4): a head-office user of *any* role reaches every
branch; a non-head-office user of *any* role — including admin — reaches only their own
branch, **except** the Admin section/audit log and Admin CRUD (users, branches,
document-types, settings), which remain global for the `admin` role regardless of branch,
since reference data isn't branch-owned.

`ApplicationPolicy`, `DocumentPolicy`, `CrbClearancePolicy`. Every policy method that takes an
`Application` (or a `Document`/`CrbClearance` via its parent application) must first check
`$user->isHeadOffice() || $application->branch_id === $user->branch_id` — a non-head-office
user is denied on an out-of-branch record even for capabilities their role would otherwise
grant (e.g. a `credit_officer` cannot record a CRB outcome on another branch's application
just because outcome-recording is generally allowed to their role). Enforce in controllers
via `$this->authorize()` / `Gate`. Viewer role: every mutating route denied, everywhere.

## 9. SEARCH

`SearchController@index` reads GET params (all optional): `q, application_number,
membership_number, applicant_name, national_id, phone, type, loan_product_id, branch_id,
status, crb_status, date_from, date_to`.

Query: `Application::visibleTo(auth()->user())->with(['member','branch','loanDetail.product'])`
- free `q`: LIKE across application_number, applicant_name, members.membership_number,
  members.national_id, applications.national_id, phone (use `where(fn) OR` group).
- exact-ish filters: `where` / `whereHas('member', …)` / `whereHas('loanDetail.product', …)`.
- If the requesting user is not head-office and passes a `branch_id` filter for a branch
  other than their own, ignore it (don't let a filter param bypass `visibleTo`).
- date range on `application_date`. Paginate 20, `->withQueryString()`.
Display: result table like mockup + "matched on" hints + result count/time.

## 10. SEEDERS

`DatabaseSeeder` calls, in order:
1. **BranchSeeder** — 5 branches from mockup (LUS-01 Lusaka Main … CHP-01 Chipata), with
   **LUS-01 Lusaka Main flagged `is_head_office = true`** and the other 4 `false`.
2. **UserSeeder** — Grace Zimba (admin, assigned to LUS-01 head office → sees all branches),
   Mary Banda (records_officer, LUS-01 head office → sees all branches, to demonstrate that
   head-office visibility isn't role-gated), Peter Phiri (records_officer, assigned to a
   non-head-office branch → sees only that branch), Thoko Musonda (credit_officer, a
   different non-head-office branch), Chipo Levy (viewer, non-head-office branch,
   suspended). Password `password`. This spread deliberately covers: head-office admin,
   head-office non-admin, and branch-only users of two different roles.
3. **DocumentTypeSeeder** — 10 types exactly as mockup admin screen (Loan Application Form,
   Membership Form, National ID, Payslip, Bank Statement, Employment Confirmation,
   **Collateral (allows_multiple=true)**, **KYC Documents (allows_multiple=true)**,
   CRB Report (category clearance, is_required=false/system), Other Supporting
   (allows_multiple=true)).
4. **LoanProductSeeder** — Salary Advance, Personal Loan, Business Loan, School Fees Loan,
   Asset Finance (requires_collateral), Emergency Loan.
5. **SettingSeeder** — org_name, loan_number_format `LA-{000000}`, membership_number_format,
   crb_sla_days `2`, reminders_enabled `1`, escalation_enabled `1`, retention_years `7`,
   default_branch_id `1`, **branch_scoping_enabled `1`**.
6. **DemoDataSeeder** (env-gated, skip in production) — pin a fixed `Faker` seed (e.g.
   `fake()->seed(2024)`) so re-running the seeder produces the same demo dataset every time;
   ~15 members, ~25 applications covering EVERY status + CRB status **and spread across at
   least 3 different branches** (to exercise scoping), documents rows pointing at fixture
   PDFs in `database/seeders/fixtures/` (ship 2–3 small sample PDFs), matching pivots
   (including at least one multi-file Collateral/KYC example), crb_clearances, status
   history, notes, and audit_logs consistent with the mockup dataset (John Banda LA-000125,
   Jane Phiri LA-000126, etc.).

## 11. FRONTEND / BLADE STRUCTURE

- `layouts/app.blade.php` — shell from mockup: sidebar partial, topbar (global search,
  live clock JS, New Application button), `<main>` yield, toast partial (reads
  `session('success'|'warning'|'error')`), sprite `<svg>` symbols block.
- `partials/sidebar.blade.php` — nav groups exactly as mockup; counts from cached queries
  scoped via `Application::visibleTo(auth()->user())` (LoanApplications::count(), awaiting
  CRB count, missing docs count) so a branch-scoped officer's sidebar counts match what
  they can actually see; active via `routeIs()`.
- Blade components (`resources/views/components`):
  `x-status-badge :status`, `x-crb-badge :status`, `x-doc-bar :percent`, `x-avatar :user`,
  `x-page-header :title :sub :kick`, `x-card`, `x-stat-tile`, `x-modal :id`, `x-toast`,
  `x-empty`, `x-pagination` (custom view matching mockup pager).
- Pages (mirror mockup pages 1:1):
  `dashboard.blade.php`, `applications/index.blade.php` (chips filter via query param),
  `applications/create.blade.php` (modal-like full page or reuse modal),
  `applications/show.blade.php` (**heart**: header strip, statstrip, tabs
  Overview | Applicant | Documents | CRB Clearance | History | Notes),
  `applications/print.blade.php` (minimal-CSS, browser-print summary sheet: applicant
  details, status, document checklist, CRB result — for physical file jackets),
  `clearance/index.blade.php` (queue + history tabs, bulk bar, SLA coloring),
  `documents/index.blade.php`, `documents/missing.blade.php`, `search/index.blade.php`,
  `reports/index.blade.php` (+ `reports/print.blade.php` minimal styles for print-PDF),
  `audit/index.blade.php`, `admin/users|branches|document-types|settings`.
- Document upload UI must reflect `document_type.allows_multiple`: single-type slots show
  one file card with a "Replace" action once filled; multi-type slots show a list of
  attached files with "Add another" plus per-file "Replace"/"Delete".
- Alpine usage: tabs (`x-data="{tab:'ov'}"`), status dropdown, modals (capture + document
  viewer), bulk-select bar (checkbox change → count), toasts auto-dismiss.
- **Document viewer modal**: iframe pointing at `/documents/{id}/view` for PDFs, `<img>` for
  images; zoom = CSS transform on wrapper; page counter only needed for PDF.js later —
  MVP keeps iframe + download/print/fullscreen buttons.
- Status filter chips on registers: server-side via query string (`?status=awaiting_crb`),
  keep chip counts from grouped counts (also scoped via `visibleTo`).

## 12. FILE HANDLING DETAILS

- `.env`: no public exposure; disk `private` → `storage/app/private`.
- Upload validation (FormRequest): `file` required; **verify actual file content type via
  `finfo_file`/magic-byte sniffing**, not just the client-supplied MIME header, and confirm
  the sniffed type is consistent with `document_type.accepted_formats` (reject mismatches,
  e.g. a `.pdf`-named file whose magic bytes say `application/x-php`); max
  `document_type.max_size_mb * 1024`; `document_type_id` must exist, be active, and apply
  to the application's type. For `allows_multiple = false` types, reject duplicates with a
  clear error pointing at "Replace" instead; for `allows_multiple = true` types, duplicates
  are allowed (each is a separate attachment).
- `DocumentController@view`: `$this->authorize('view',$doc)` (which itself checks branch
  visibility via the parent application, §8);
  `return response()->file($doc->stored_path, ['Content-Disposition' => 'inline'])`.
- `@download`: same with attachment + original_filename.
- Never expose `stored_path` in any response/JSON.

## 13. DASHBOARD QUERY SPEC

All counts below are computed against `Application::visibleTo(auth()->user())`, so a
non-head-office user's dashboard reflects only their own branch, regardless of role; a
head-office user's dashboard reflects totals across all branches.

- Loans total `visibleTo($user)->where('application_type','loan')->count()`; membership same.
- Awaiting CRB: `whereIn('crb_status', ['submitted','awaiting_response'])` (+ overdue count
  via submitted_at < now − sla_days).
- Cleared this month / flagged totals.
- Missing docs: `docs_completion < 100` & active statuses count.
- By-status bars: `select status, count(*) group by status` (active statuses).
- Queue preview: top 5 awaiting by `submitted_at asc`.
- Recently captured: latest 8 with type badges.
- Activity feed: latest 8 audit_logs, filtered to applications visible to the user, with
  user + `->diffForHumans()`.

## 14. VALIDATION (Form Requests)

`StoreApplicationRequest` — common: applicant resolution (existing member_id OR typed
applicant_name + national_id), branch_id exists (and for non-head-office users, must equal
`auth()->user()->branch_id` — enforced as a validation rule, not just left to the UI; a
head-office user may pick any branch),
application_date date, assigned_user_id exists; if type=loan: loan_product_id exists,
requested_amount numeric min 0, term_months int; if type=membership: applicant_type in enum.
`StoreDocumentRequest` (includes MIME-sniff rule per §12, and the allows_multiple-aware
duplicate check), `ReplaceDocumentRequest`, `UpdateStatusRequest` (status in enum + custom
rule `ValidStatusTransition` checking §3 map + role for approved/rejected), `CrbOutcomeRequest`
(outcome in cleared|flagged|failed|requires_review, result/remarks nullable),
`StoreNoteRequest` (body max 2000).

## 15. SCHEDULED COMMANDS (Phase 6+)

- `app:reminders:send` — daily 08:00: applications in awaiting_documents older than X →
  audit `reminder.sent` (email/SMS stub via Log for MVP).
- `app:crb:escalate` — awaiting_response past SLA → audit + dashboard flag.
- `app:checklist:refresh` — recompute docs_completion for all active applications.
- `app:retention:sweep` — daily: applications in `archived` status older than
  `settings.retention_years` past `archived_at` → audit `retention.flagged` (list-only for
  MVP; do not auto-delete). Gives a concrete home for the `retention_years` setting that v1
  defined but never used. Actual purge/anonymization left as a manual admin action for now.
Register in `routes/console.php` via `Schedule::command(...)`.

## 16. TESTING (Pest preferred)

Feature tests minimum:
1. Loan application capture → number issued `LA-000001`, child row created, history row exists.
2. Membership approval → member created + MEM number linked.
3. Document upload → pivot created, checklist % recomputed (3/5 → 60%).
4. Replace document → version 2, old superseded, pivot swapped.
5. Invalid status transition rejected (draft → approved ⇒ 403/422).
6. Viewer role cannot upload/advance (403).
7. CRB submit → awaiting_response; outcome cleared → app status crb_cleared.
8. Download blocked for unauthorized user (403) and for deleted doc (404).
9. Search by national_id and by applicant name returns right rows.
10. CSV export streams correct headers + row count.
11. Audit log entry created for each: upload, replace, delete, status change, crb submit/outcome.
12. **Branch scoping**: a user (of any role — test at least `records_officer` and `admin`)
    posted at a non-head-office branch cannot see, open, or act on another branch's
    application via index, show, search, or direct document view/download URL (404, not
    leaking existence via 403). A user posted at the head-office branch — including a
    non-admin `records_officer` — sees and can act across every branch. This confirms
    visibility follows `branch->is_head_office`, not role.
13. **Document repository scoping**: a non-head-office user hitting `/documents` or
    `/documents/missing` — including with an explicit `?branch=<other>` query param — only
    ever sees documents belonging to applications in their own branch; a head-office user
    sees the full repository.
14. **Multi-document types**: uploading a second file to a `allows_multiple=true` type (e.g.
    Collateral) succeeds and both remain active; uploading a second file to an
    `allows_multiple=false` type is rejected with a "use Replace" error.
15. **Concurrent status advance**: two simultaneous `advance()` calls on the same application
    result in exactly one successful transition and one rejected (no double-transition, no
    lost history row).
16. **MIME spoofing**: a file renamed with a `.pdf` extension but non-PDF magic bytes is
    rejected by `StoreDocumentRequest`.

## 17. SECURITY CHECKLIST

Private disk + authorized streaming only · file mime/size validation via **actual content
sniffing, not just client-reported MIME** · role middleware on admin routes · policies on
every document action, each enforcing branch visibility · CSRF everywhere (Blade forms) ·
login rate-limiting (Breeze default) · **`throttle:document-access` middleware (e.g. 60/min
per user) on `/documents/{id}/view` and `/documents/{id}/download`** to slow down
enumeration attempts against otherwise-authorized routes · audit log not deletable from UI ·
no mass-assignment holes (`$fillable` only) · `->withQueryString()` not leaking sensitive
params · branch scoping applied consistently across index/show/search/reports/dashboard **and the
document repository listing (`/documents`, `/documents/missing`)**, not just the main
applications list · virus/malware scanning explicitly out of scope for this
MVP (noted here as a conscious decision, not an oversight — revisit before production use
with high-risk uploads, e.g. via ClamAV).

## 18. DELIVERY PHASES (build in this order; demo-able after each)

**Phase 0 — Foundation**: install, Breeze auth, DB schema ALL tables (§2) + migrations,
enums, models with relationships (including `scopeVisibleTo`), layout shell (sidebar/topbar
from mockup), settings table, seeders 1–5. ✅ Login → empty dashboard shell renders.

**Phase 1 — Admin data**: users/branches/document-types CRUD + settings screen (including
`branch_scoping_enabled` toggle). ✅ Admin can manage reference data.

**Phase 2 — Application capture & tracking**: NumberingService, create/show/index for both
types (branch-scoped for non-head-office users of any role), status advance (with locking) +
history, assign officer (with notification stub), notes, single-application print view.
✅ Full record lifecycle minus docs, with branch scoping demonstrable.

**Phase 3 — Documents**: upload/replace/delete (with MIME sniffing and
allows_multiple-aware pivot logic), versions, checklist service + completion %, viewer
(inline stream, throttled), repository + missing-docs pages. ✅ John Banda shows 60% with
missing rows as in mockup; a Collateral slot accepts multiple files.

**Phase 4 — CRB**: queue page (filters, days waiting, SLA colors, bulk submit), submit +
outcome flow, history tab, auto status linkage. ✅ Queue matches mockup table.

**Phase 5 — Search & Dashboard**: SearchService + advanced search page, dashboard widgets
with real queries, all scoped via `visibleTo`. ✅ Every dashboard tile populated from DB and
correctly branch-scoped.

**Phase 6 — Reports & audit**: 11 reports (cards + CSV + print view, scoped via
`visibleTo`), audit trail page with filters, observers fully wired, retention sweep command.
✅ Reports export, audit shows real entries, retention sweep flags stale archives.

**Phase 7 — Polish & hardening**: policies review (branch-scoping edge cases), tests green
(including branch-scoping, multi-doc, concurrency, MIME-spoofing tests from §16), scheduled
commands, empty states, pagination, flash toasts, seed demo dataset fidelity pass.

## 19. WORKING AGREEMENT

- When I say **"Build Phase N"**, deliver every file needed for that phase with full code:
  migrations, models, enums, services, requests, policies, controllers, Blade views,
  route additions, seeder changes — each under a clear `path/to/file.php` heading.
- Follow existing conventions; reuse the mockup's CSS classes verbatim.
- After each phase, list: what was built, how to verify it (click-path), and any decisions made.
- No TODO placeholders — working code only. Prefer simple over clever.

Start by confirming you understand the schema (§2), the status/transition model (§3), the
head-office-based branch-scoping rule — visibility follows `branch->is_head_office`, not
role (§0.5 / §2.1 / §4 / §8) — and the multi-document-per-type rule (§2.11 / §5.2 / §5.3),
then wait for my "Build Phase 0".
