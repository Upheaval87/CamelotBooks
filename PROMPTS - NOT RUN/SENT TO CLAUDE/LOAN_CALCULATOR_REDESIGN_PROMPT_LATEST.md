# MASTER IMPLEMENTATION PROMPT — KUWALA SACCO LOAN SYSTEM
# Consolidated features F1→F6 (public calculator → loan access control)

Implement ALL of the following in the existing Laravel loan-application system, IN
THIS ORDER. Each feature builds on the previous ones. Preserve ALL existing
calculation logic, formulas, routes' existing behaviour, field names and outputs
unless a feature explicitly adds/changes something below.

============================================================
## GLOBAL CONSTRAINTS
============================================================
- DO NOT alter interest/fee formulas, rounding, or any existing financial logic.
- DO NOT break existing workflows (officer→payroll→3 authorizers), documents,
  disbursement, reports, auth/2FA, or permissions.
- All NEW server-side filtering (access control) must be enforced on the server;
  never trust the client.
- Security: no open redirects (build targets from route()+validated params only);
  validate every dynamic form field server-side; rate-limit public endpoints.
- Reuse existing theme/design tokens (navy #16202C, amber #F6A21E, Ivory surfaces).

============================================================
## F1 — PUBLIC CALCULATOR + GUEST "APPLY" HANDOFF
============================================================
1. LOGIN PAGE: add a link/button "Try the Loan Calculator" → route('loans.calculator').
2. Make the calculator PUBLIC (guest + authed): move/add its route outside auth
   middleware. Keep its PDF endpoint public but throttled.
3. "APPLY FOR THIS LOAN" gating: enabled ONLY when all Loan Information fields are
   valid (product selected, amount>0, period within 1..product.max, start present).
   Otherwise disabled + hint "Complete all Loan Information fields to apply."
4. Single navigation path: Apply → GET /loans/apply-from-calculator?{loan_type_id,
   amount, period, start} (handoff controller):
   - validate params (exists loan_types, numeric>0, integer>=1, date nullable);
   - build $target = route('member.apply').'?'.http_build_query($validated);
   - if auth()->check() → redirect()->to($target);
   - else redirect()->guest('login')  // stores intended (this URL+query)
   Ensure the FINAL post-login redirect (after password AND member 2FA) uses
   redirect()->intended(...) so guests return to the handoff and are then bounced
   to the auto-populated application form.
5. Application form keeps its existing query-param auto-population
   (loan_type_id, amount, period, start) + "Pre-filled from Loan Calculator" banner.

============================================================
## F2 — PRODUCT-DRIVEN APPLICATION FORM (NO TABS)
============================================================
1. REMOVE the Standard/Staff/Pompo tab switcher from the member application page.
2. The form format is determined by the SELECTED LOAN PRODUCT:
   - loan_types gains application_form_id (FK → application_forms).
   - Selecting a product (from calculator pre-fill or the form's product dropdown)
     loads that product's attached application-form definition and renders it.
3. Member product dropdown lists ONLY loan types the signed-in user may access
   (see F6). Guest calculator shows all active products.

============================================================
## F3 — LOAN TYPES CONFIG: "APPLICATION FORM" DROPDOWN
============================================================
On the admin Loan Types page add an "Application Form" select per row listing all
application_forms; saving sets loan_types.application_form_id. Keep existing
rate/method/insurance/processing/max-period fields unchanged.

============================================================
## F4 — FORM BUILDER (ADMIN) — design application forms
============================================================
New admin page "Form Builder": list / create / edit / delete application forms.
- Each form = ordered SECTIONS; each section = ordered FIELDS; section titles editable.
- Field designer: label, key(auto), input type
  [text,number,currency,date,select,radio,textarea,file,auto], required, width
  (half/full), options (select/radio), min, max, pattern, placeholder, help,
  and CONDITION (see F5).
- REORDER by drag-and-drop: sections and fields (persist sort_order via a JSON
  reorder endpoint). Use native HTML5 DnD or SortableJS.
- Preview button renders the member form with the current definition.
- SEED the three knowledge-base forms on first run (see §SEED).

============================================================
## F5 — CONDITIONAL FIELDS / SECTIONS
============================================================
- Field.condition and Section.condition = {key, op('='|'!='), val}.
- Renderer shows a field/section only when its condition passes (live on change).
- SEED example: a "Disbursement Details" section with disb_method select
  [Bank Account, Mobile Money]; bank fields (acc_name, acc_no, bank_name, branch)
  condition disb_method='Bank Account'; mobile fields (mob_inst select
  [Airtel Money,TNM Mpamba,MTN Mobile Money], mob_name, mob_no) condition
  disb_method='Mobile Money' — i.e. selecting Mobile Money REPLACES bank inputs.

============================================================
## F6 — LOAN-TYPE ACCESS CONTROL (member class / company / user)
============================================================
1. users.member_class = 'ordinary' | 'staff' (seed: existing members ordinary;
   staff users staff).
2. loan_type_access rows: (loan_type_id, member_class) — which classes may see a
   loan type. Plus loan_types.access_companies (JSON, empty=all) and
   loan_types.access_users (JSON, empty=none) for company/user overrides.
3. LoanAccessService::visibleLoanTypes($user): a loan type is visible iff
   user.member_class ∈ its access classes (OR user in access_users) AND
   (access_companies empty OR contains user.company).
4. Apply this filter to: member calculator product list AND member application
   product dropdown. Ordinary members must NEVER see staff-only loans (e.g.
   Staff Material Loan → classes ['staff'] only). Admin sees/edits all.

============================================================
## DATABASE (migrations)
============================================================
users: + member_class string(20) default 'ordinary'.
loan_types: + application_form_id FK nullable; + access_companies JSON nullable;
            + access_users JSON nullable.
application_forms: id, name, slug unique, description null, is_active, timestamps.
form_sections: id, application_form_id FK cascade, title, sort_order, condition JSON
               nullable, timestamps.
form_fields: id, form_section_id FK cascade, key, label, type, required bool,
             width default 'half', options JSON null, min/max decimal null,
             pattern null, placeholder null, help null, condition JSON null,
             sort_order, timestamps, unique(form_section_id,key).
loan_type_access: id, loan_type_id FK cascade, member_class, timestamps.

============================================================
## MODELS / SERVICES / CONTROLLERS / ROUTES
============================================================
Models: ApplicationForm(hasMany sections), FormSection(hasMany fields, belongsTo
form), FormField, LoanTypeAccess; LoanType belongsTo ApplicationForm, hasMany
LoanTypeAccess.
Services:
- LoanAccessService::visibleLoanTypes(User)
- FormDefinitionService::forLoanType(LoanType) → form+sections+fields ordered
- ConditionEvaluator::ok(?array $cond, $getValue)
- DynamicFormValidator::rules(FormDefinition) → Laravel rules
  (required; numeric/min/max; regex:pattern; in:options for select/radio)
Controllers/Routes:
- Public: GET /loans/calculator (show), GET /loans/calculator/pdf (throttle),
  GET /loans/apply-from-calculator (applyHandoff).
- Member: GET/POST member.apply (renders dynamic form; POST validates via
  DynamicFormValidator and stores answers as JSON on the application).
- Admin: LoanTypeController@updateForm + @updateAccess;
  FormBuilderController: forms CRUD, sections CRUD+reorder, fields CRUD+reorder
  (reorder endpoints accept ordered id arrays and persist sort_order).

============================================================
## SEED DATA (three knowledge-base forms)
============================================================
STANDARD LOAN APPLICATION FORM sections/fields:
1 Personal Details: first_name*, other_name, last_name*, dob(date)*, telephone*,
 email*, next_of_kin*, marital_status(select Single/Married/Divorced/Widowed),
 residential_address(full,req), rented_owner(select Owner/Rented), contact_address,
 home_village, traditional_authority, district, place_of_birth, nok_address(full,req)
2 Employment Record: employer(full,req), emp_no, years(number), employer_address(full),
 job_position, gross_salary(currency), net_salary(currency,req)
3 Loan Information: amount(currency,req), amount_words(auto), loan_type(text,req),
 period(number,req), date_required(date), source(textarea), purpose(textarea,req,full),
 credit(radio No/Yes), credit_when(text,cond credit=Yes), credit_amount(currency,cond credit=Yes)
4 Securities Offered: sec1_desc(full), sec1_value(currency), sec1_loc, sec2_desc(full),
 sec2_value(currency), sec2_loc
5 Guarantors: g_name*, g_nid, g_tel, g_rel, gc1_desc(full), gc1_value(currency),
 gc1_loc, g_id(file,req,full)
6 Spouse Details (cond marital_status=Married): spouse_name, spouse_maiden,
 spouse_address(full), spouse_tel
7 Disbursement Details: disb_method(select,req) + bank/mobile conditional fields (F5)
POMPO-POMPO ADVANCE FORM = STANDARD minus the loan_type field.
STAFF LOAN APPLICATION FORM:
1 Application: name*, branch, id_no, type_of_loan*, amount(currency)*,
 amount_words(auto), repayment_period(text)*, reason(textarea,req,full)
2 Disbursement Details (as F5)
PRODUCT MAPPING + ACCESS: Standard/School Fees/Material/Development → standard form,
classes [ordinary,staff]; Pompo-Pompo Advance → pompo form, [ordinary,staff];
Staff Material Loan → staff form, [staff] only.

============================================================
## UI SPECS (reuse design tokens)
============================================================
- Member application: NO tabs; product dropdown (filtered by F6); dynamic sections
  rendered from definition; conditional show/hide live; auto amount_words; summary
  rail (product/amount/period/monthly/total + "Form format: {name}"); pre-fill banner.
- Admin Loan Types: table + "Application Form" select + "Access" button opening a
  modal (checkboxes Ordinary/Staff; company checkboxes; users text) → save F6.
- Admin Form Builder: left form list (+New); editor with editable form name,
  section cards (editable title, drag handle, add field, remove), field rows
  (drag handle, type/req/if badges, edit ✎, remove ×), Add Section; field modal
  with all designer inputs incl. condition (field select, operator =/!=, value).

============================================================
## QA & DEFINITION OF DONE
============================================================
- Guest: login link → public calculator; Apply(valid)→login(+2FA)→auto-populated
  application; authed Apply → auto-populated directly; invalid → Apply disabled.
- No tabs; product selection swaps form format (Pompo→Advance form, Staff→Staff form).
- Builder: create/edit/delete forms+sections+fields; drag reorder persists;
  conditions work (Mobile Money replaces bank fields); 3 forms seeded.
- Access: ordinary member never sees staff-only loans anywhere; staff does.
- Calculations unchanged (regression: Standard 100,000/36 → MK 15,098.59 monthly).
- Server-side validation of dynamic fields; no open redirect; route:list shows only
  the new routes; tests green; responsive + a11y passes.