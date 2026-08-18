# MASTER IMPLEMENTATION PROMPT — KUWALA SACCO ADMIN SUITE (PART 2)
# Consolidated: Loan Types redesign → Users/Companies (membership + payroll assignment)
# → Thresholds → Authorizer Tiers → Email Settings
# Prerequisite: Part-1 consolidation (public calculator, product-driven forms, form
# builder, loan access control) is implemented. This prompt extends it.

============================================================
## 0. GLOBAL CONSTRAINTS & DESIGN SYSTEM
============================================================
- Preserve ALL existing business logic (calculations, workflows, approvals, access
  filtering). This prompt adds/extends ADMIN configuration + presentation only.
- Reuse design tokens: navy #16202C/#1E2A3A, amber #F6A21E/#FFC466, amber-ink
  #9A5B00, bg #F4F6F9, panel #fff, line rgba(22,32,44,.10); fonts Inter + Space
  Grotesk; radii 12–16px; soft shadows; min font 12px; money tabular-nums.
- Reusable admin components (build once, use everywhere):
  • Page header card (title + subtitle + primary CTA)
  • Stat chips row; navy sticky table headers; row hover; status pills (Active/Inactive)
  • Icon-only row actions with tooltip+aria-label: Edit(pencil), Deactivate(ban/red),
    Activate(check/green), Assign-PO(person+), Save(disk)
  • Toggle switches (amber) for booleans; segmented "class cards" with amber check-boxes
  • Sectioned form cards (uppercase micro-label + hairline), sticky Save/Cancel bar
  • Modals with navy header; searchable multi-select lists; toasts.
- Security: all admin routes behind auth + permission gates; email password stored
  encrypted; test-email throttled; server-side validation everywhere.

============================================================
## 1. DATA MODEL CHANGES (migrations)
============================================================
users: + member_classes JSON nullable  // ["ordinary","staff"]; keep is_member synced
       (is_member = count(member_classes)>0). Migrate existing member_class → array.
company_payroll_officer: id, company_id FK cascade, user_id FK cascade,
       assigned_by FK users nullable, timestamps, unique(company_id,user_id).
system_settings (existing key/value) — add keys:
  mail_transport, mail_host, mail_port, mail_username, mail_password (ENCRYPTED),
  mail_encryption, mail_from_address, mail_from_name,
  mail_notification_prefs JSON  // {account_created,loan_submitted,payroll_assessment,
                                //  approval_stage,final_decision,delegation,account_status}
(loan_types.application_form_id, loan_type_access, authorizer_tiers,
 approval_thresholds already exist from Part 1.)

============================================================
## 2. SERVICES
============================================================
- LoanAccessService (Part 1) — UPDATE: a loan type is visible iff
  intersection(user.member_classes, type.access_classes) non-empty
  OR user.id ∈ type.access_users OR type.access_classes empty;
  AND (type.access_companies empty OR contains user.company_id).
- PayrollAssignmentService: assign/remove(company,user,by); companiesFor(user);
  officersFor(company). Payroll assessment scoping (Part 1) now reads the PIVOT
  (officer may assess any company they're assigned to); seed pivot from existing
  payroll officers' company_id.
- MailSettingsService: get()/save(array) (encrypt mail_password with
  Crypt::encryptString; decrypt on read); apply() → set config() at boot:
  config(['mail.default'=>transport, 'mail.mailers.smtp.host'=>…, port, username,
  password(decrypted), encryption, 'mail.from.address'=>…, 'mail.from.name'=>…]);
  register apply() in AppServiceProvider::boot.
- NotificationRouter (Part 1) — honour mail_notification_prefs booleans per event.

============================================================
## 3. ROUTES & CONTROLLERS (admin, gated)
============================================================
admin/loan-types            index/create/store/edit/update  (LoanTypeController)
admin/loan-types/{id}/access  update                        (updateAccess)
admin/users                 index/create/store/edit/update  (UserController; member_classes)
admin/companies             index/create/store/edit/update  (CompanyController)
admin/companies/{id}/payroll-officers  update               (assignPayrollOfficers)
admin/thresholds            index/create/store/edit/update  (ThresholdController)
admin/tiers                 index/update                    (AuthorizerTierController)
admin/email-settings        index/update/test               (EmailSettingsController;
                            test → Mail::to(x)->send(new TestMail), throttle:5,1)

============================================================
## 4. FEATURE A — LOAN TYPES (list / add-edit / access modal)
============================================================
LIST: header + "Add Loan Type"; stat chips (Products, Active, Staff-only, Forms
attached); search + status filter; navy table: Loan Type, Code, Application Form
(inline select → update application_form_id), Access (class chips + Manage), Rate
(% p.m. tabular), Method, Status pill, icon actions Edit/Deactivate-Activate.
ADD/EDIT: sectioned card — Product Identity (name, code, description); Pricing &
Interest (rate %, interest period, method select w/ "System default", application
form select + hint, insurance % + period, processing fee %); Limits (max months,
min/max MK); Requirements & Status (toggles guarantor/collateral/active); Access
(button → modal). Sticky Save/Cancel.
ACCESS MODAL ("Loan Product Access"): navy header w/ product name; member-class
segmented cards (Ordinary / Staff) with hint "leave both unchecked = allow all";
companies cards (hint "leave all unchecked = all"); searchable individual-users
multi-select (hint "leave empty = class/company only"); Save Access/Cancel.

============================================================
## 5. FEATURE B — USERS (membership: ordinary / staff / both)
============================================================
LIST: stat chips (Users, Members, Staff-class, Active); search + role + status
filters; navy table: User (avatar+name), Username(mono), Email, Role pill, Company,
Membership chips (Ordinary=amber / Staff=blue / "Not member"), Status pill, icon
actions Edit/Deactivate-Activate.
REGISTER/EDIT: sections — Personal (first/other/last, DOB, marital, employment
type); Contact & Identity (email, national ID, telephone); Role & Assignment
(role select, company select *required, branch select); MEMBERSHIP / LOAN-ACCESS
CLASS: two class cards (Ordinary member / Staff member) — check one = single class,
BOTH = ordinary+staff, none = cannot apply; live hint reflects state; Identity
Documents upload cards (passport, ID front/back, contract). Sticky bar
(Create User / Save Changes). On save: set member_classes + sync is_member.
Header sub: emailed secure link + auto unique usernames.

============================================================
## 6. FEATURE C — COMPANIES (assign payroll officers)
============================================================
LIST: stat chips (Companies, Active, Payroll officers); navy table: Company, Code,
Contact, Users, Payroll Officers (name chips + Manage/Assign), Status pill, icon
actions Assign-PO / Edit / Deactivate-Activate.
ADD/EDIT: sections — Identity (name*, code); Contact (email, phone, address);
PAYROLL OFFICERS: searchable checklist of users with role Payroll Officer (checked
= assigned to this company) + hint; sticky Save/Cancel.
ASSIGN MODAL (from list): same checklist; Save writes pivot (assigned_by=auth user).

============================================================
## 7. FEATURE D — APPROVAL THRESHOLDS
============================================================
LIST: header sub "director threshold NOT hardcoded — reads from flagged threshold";
stat chips (Thresholds, Director level = MK value of flagged row, Active); navy
table: Name, Min, Max (∞ if null), Approvers, Director Auth (DIRECTOR LEVEL pill +
amber left-edge on flagged row), Directors (mgmt out), Branch Filter, Status pill,
icon actions.
ADD/EDIT: note "exactly one threshold flagged as requiring director authorization";
sections — Definition (name, min MK, max MK blank=∞); Approval Chain (approver
count, director count if manager absent, director branch filter); Flags (toggles
Requires Director Authorization, Active). Enforce single director-flag on save
(unflag others). Sticky bar.

============================================================
## 8. FEATURE E — AUTHORIZER TIERS
============================================================
Header sub explains Primary/Secondary/Auxiliary; stat chips (Primary/Secondary/
Auxiliary counts); navy table: User (avatar+name), Role pill, three amber
check-tiles (Primary/Secondary/Auxiliary) toggle in place, per-row Save icon.
Persist to authorizer_tiers (user may hold multiple tiers).

============================================================
## 9. FEATURE F — EMAIL SETTINGS (NEW)
============================================================
Header + live status chip (Not tested / Test sent / OK).
Sections:
1 Mail Transport: transport select (SMTP/Mailgun/SES/Sendmail/Log), encryption
  (None/TLS/SSL), host, port, username, password (show/hide eye).
2 Sender Identity: from address, from name.
3 Notification Preferences: toggle switches per event (account_created+password
  link, loan_submitted→officers, payroll_assessment→officers, approval_stage→
  authorizers, final_decision→applicant, delegation alerts, account_status).
4 Send Test Email: recipient input + "Send test email" button (throttled) →
  updates status chip + toast.
Sticky Save/Cancel. Save → MailSettingsService::save (encrypt password) + toast.

============================================================
## 10. SEED / COMPAT
============================================================
- users.member_classes: ordinary members → ["ordinary"]; staff → ["staff"];
  both where applicable; non-members → [].
- company_payroll_officer: seed from each payroll officer's current company_id.
- system_settings mail_*: sensible defaults (transport SMTP, from no-reply@kuwala.mw,
  "Kuwala SACCO Ltd", prefs all true).

============================================================
## 11. SECURITY
============================================================
- All routes admin-gated; email settings view/update restricted to administrator.
- mail_password encrypted at rest, masked in UI, decrypted only in apply()/send.
- Test email throttled (5/min); no secret echoed in responses/toasts.
- Server-side validation for every form; access changes audited (audit_trail).

============================================================
## 12. QA & DEFINITION OF DONE
============================================================
- Loan Types: inline form select persists; access modal saves classes/companies/
  users; member calculator/application reflect access (ordinary never sees staff-only).
- Users: membership cards save ["ordinary"]/["staff"]/both/none; loan access follows.
- Companies: payroll officers assignable via form + modal; payroll assessment
  scoped by pivot; users count + PO chips render.
- Thresholds: exactly one director-flag enforced; director threshold read from it.
- Tiers: multi-tier per user persists; counts update.
- Email: settings save (password encrypted); apply() affects outgoing mail at boot;
  test email delivers; notification prefs suppress disabled events.
- All pages use the shared admin components; responsive + a11y passes;
  `php artisan test` green; no unrelated logic changed.