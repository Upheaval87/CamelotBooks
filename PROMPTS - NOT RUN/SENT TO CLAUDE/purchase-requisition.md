PURCHASE REQUISITION MODULE — FULL REDESIGN IMPLEMENTATION SPEC (LIST / CREATE / EDIT /
DETAIL / REPORTS). Rebuild the requisition module in the established system language.
ALL VALUES INLINE; no mockup dependency. The system-wide pinnable rails feature
(rails.html) stays EXACTLY as implemented — each requisition page renders its rail per
the registry in §7; global pin applies.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: approval workflow handlers (submit /
approve / reject), convert-to-PO handler, budget-check logic, delete rules/locks,
export/print handlers, search/filter/sort/pagination params, auth/permissions and all
routes remain EXACTLY as-is. Every pre-existing button keeps its handler; this spec
re-styles/re-arranges UI only. Fix the current mislabeled list heading ("Create
Requisition") to "Purchase Requisitions".

==================== 0 · DISCOVERY ====================
0.1 Inventory requisition routes/pages: index/create/edit/show + any reports pages.
0.2 List CURRENT controls + handlers per page (drives §10 audit): list (status filter,
Export, Create, row view/edit/delete); create (Save Draft, Save, Submit for Approval,
Add Line, duplicate/delete line); edit (Cancel, Delete, Save Changes, Submit); show
(Edit, Print/PDF, Convert to PO, Approve, Reject, comment); any lock rules when status
≥ Approved.
0.3 Locate status values + transitions (Draft, Pending, Approved, Rejected, Converted,
+Void if present), priority field, approval records (who/when/step), PO link field.
0.4 Locate user-preference storage + header Favorites (rails) — reference only.

==================== 1 · TOKENS / DIMENSIONS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red:#dc2626; --red-2:#b91c1c; --green:#15803d; --amber:#d97706;
--amber-2:#b45309. html,body{overflow-x:clip}. Text rem per app rule.
prefers-reduced-motion respected.

==================== 2 · STATUS BADGES / PRIORITY CHIPS ====================
Badges (pill + dot, existing component): Draft rgba(17,69,75,.07)/.2/#11454b;
Pending rgba(217,119,6,.10)/.35/#b45309 (dot #d97706); Approved gradient 180deg
#ecfdf3,#dcf5e7 + rgba(22,163,74,.28) + #15803d (dot #22c55e); Rejected
rgba(185,28,28,.08)/.3/#b91c1c (dot #dc2626); Converted rgba(18,143,142,.10)/.35/#128F8E.
Priority chips: Normal = rgba(17,69,75,.06)/.16/#5f7476; Urgent = amber tint (same as
Pending badge colors), .625rem 800, radius 999.

==================== 3 · LIST (requisitions.index) ====================
3.1 NON-sticky page head: h1 "Purchase Requisitions" + sub "Raise, approve and track
internal purchase requests."; right: [⇩ Export ghost-sm][＋ Create Requisition CTA].
3.2 CLICKABLE STATUS BOXES (.fbox row of 5): Total(t-ink) / Pending(t-amber) /
Approved(t-mint) / Rejected(t-red) / Converted(t-teal); live counts; active = teal ring;
click sets the EXISTING status filter param. (fbox: flex gap 10px; radius 14px; padding
10px 12px; bg rgba(255,255,255,.85); tile 2rem radius .625rem white icon; label
.5625rem 800 uppercase faint; value .9375rem 800 ink.)
3.3 CONTROLS: search (requisition #, requester; EXISTING param) + sort select
[Newest / Total high→low / Needed by soonest].
3.4 TABLE (mist thead): Requisition № (mono) 13 / Date 11 / Requested By 16 /
Department 12 / Needed By 11 / Total (K) num 11 / Priority 9 / Status 10 / Actions 8
(view/edit/delete icon buttons → existing handlers). Row hover tint; pagination row
"Showing X of Y requisitions" + Prev/Next (existing pagination).

==================== 4 · CREATE (requisitions.create) ====================
4.1 Sticky head: h1 + sub "Request goods or services for internal approval."; right:
Cancel ghost + seg [Save Draft ghost | Save secondary | Submit for Approval ⤴ CTA]
(existing handlers).
4.2 SUMMARY BAR (live) above sections: grid 1fr 1fr 1fr 1.25fr; cells Subtotal (n =
line count) / Est. Tax / Budget Check ("—" until submit; "Within budget"/"Over budget"
from EXISTING check) / hero Grand Total (teal gradient; live). ≤900px: 2 cols, hero spans.
4.3 SECTION "Requisition Details" (g4): Requested By (default current user) / Department
select / Cost Centre combo / Needed By date / Priority select / Suggested Supplier
(optional combo, supplier search) sp2 / Reference.
4.4 SECTION "Line Items": columns Code 10 / Item (inline search) 20 / Description 24 /
Qty num 7 / Unit select (pcs/kg/box/hrs) 8 / Est. Unit Price num 12 / Amount (K) 11 /
Actions 8 (duplicate + delete). Add Line ghost-sm. TOTALS BLOCK ALWAYS below table:
right box 18.75rem rows Subtotal / Est. Tax / Grand Total (final row border-top 1.5px
#17565d 800); values 0.00 when zero — NO zero-hiding.
4.5 SECTION "Notes & Attachments": 2-col Justification/Notes + Attachments (dashed
textareas or existing upload widget — keep existing handler).

==================== 5 · EDIT (requisitions.edit) ====================
5.1 Sticky head: h1 "Edit Requisition" + mono chip {REQ-№} + status badge + sub
"{requester} · {date} · needed by {date}"; right: Cancel ghost + Delete danger-o +
seg [Save Changes secondary | Submit for Approval ⤴ CTA]. If existing rules lock editing
when status ≥ Approved, KEEP the lock (read-only notice; no change).
5.2 Same sections as create, PRE-FILLED; sumbar reflects saved values (hero amber when
Pending, teal otherwise); totals block always visible.

==================== 6 · DETAIL (requisitions.show) — HEADER STANDARD ==============
6.1 STICKY HEAD (actions only): LEFT back icon-btn (34px chevron; SAME route as old
Back) + breadcrumb Purchasing › Requisitions › {REQ-№} (here = mono). RIGHT cluster:
[✎ Edit][ Print / PDF][⚙ Convert to PO] | hairline | [✓ Approve secondary]
[Reject danger-o]. Approve/Reject visible ONLY when status Pending (existing rules);
Convert hidden when already Converted. NO micro-labels; NO title/sub here.
6.2 PROFILE CARD (identity only, NO buttons): tile 3.5rem teal-tint doc icon; name row:
"Requisition" + mono chip + status badge + priority chip; meta chips: {requester},
{department} · CC {cost-centre}, Needed by {date}, Suggested · {supplier}.
6.3 SUMMARY BAR: Subtotal / Est. Tax / Budget Check / hero (amber "Awaiting Approval"
+ value when Pending; teal Grand Total otherwise).
6.4 APPROVAL WORKFLOW CARD: vertical steps with connectors from EXISTING approval
records: Submitted (done teal check + timestamp) → Manager Approval (current: white +
amber ring + pulse; "Waiting for {approver}") → Finance Review (todo) → Convert to
Purchase Order (todo). Footer (only when current user is an approver AND Pending):
comment input + [✓ Approve][Reject] → EXISTING handlers.
6.5 LINE ITEMS CARD: read-only table Code 10 / Item 24 / Description 27 / Qty 7 /
Unit 8 / Est. Unit Price 12 / Amount 12 + totals block §4.4.

==================== 7 · RAILS REGISTRY (per page; rails feature unchanged) ========
requisitions.index → Views [All Requisitions(active), Pending, Approved, Rejected,
Converted] + Reports [Requisition Register, Approval Queue].
requisitions.create → Quick Nav [Requisitions List, Suppliers, Day Book].
requisitions.edit → Quick Nav [View Requisition, Convert to PO, Print / PDF,
Back to Requisitions].
requisitions.show → Quick Nav [Approve, Convert to PO, Print / PDF,
Back to Requisitions].
requisitions.reports → Quick Nav [Requisitions List, Pending Approvals, Day Book].

==================== 8 · REPORTS (requisitions.reports) ============================
8.1 Page head: h1 "Requisition Reports" + sub; right [⇩ Export All ghost].
8.2 FILTER BAR: period seg2 [This Month|This Quarter|This Year|Custom] + Department
select + Status select; filters apply to all reports + exports; state in URL params.
8.3 REPORT CARDS (grid 3 cols; ≤1000 2; ≤700 1): Requisition Register · Approval
Queue / Pending · Status Summary · Requisition Aging · Conversion to PO · Spend by
Department / Cost Centre. Each: icon tile 38px teal-tint; title .875rem 800; description
.71875rem muted; foot: PDF + CSV chips + "Open →".
8.4 Each card opens its report page (existing if present; else create MINIMAL report
page using the system report pattern: filter bar + mist/dark-agnostic table + totals
tfoot + exports). Register table: Requisition № / Date / Requested By / Department /
Total (K) / Status / PO # (mono; em-dash when none) / View; TOTAL tfoot row.
8.5 Report computations from the requisition registry ONLY: aging brackets 0–7/8–14/15+;
conversion = approved with/without PO + variance; spend grouped by department/cost
centre vs budget where budget data exists.

==================== 9 · ACCESSIBILITY / RESPONSIVE ====================
9.1 aria: status boxes aria-pressed; breadcrumb nav; cluster labels kept; steps as
ordered list semantics; focus rings #94a3b8.
9.2 ≤1100px statgrid 3-col; ≤768px: slim rail hidden, statgrid 2-col, g4 → 1fr 1fr,
sp2 spans; tables horizontal-scroll inside cards; no horizontal PAGE scrollbar at
1280/1024/768.

==================== 10 · CONSTRAINTS ====================
No changes to rails feature (slim/drawer/pins/global pin) or other modules; no
approval/conversion/budget/delete handler changes; no new packages; ONE shared
component/CSS per pattern; totals block always present; sumbar never replaces it;
no hardcoded sample data in reports (live registry only).

==================== 11 · VERIFY (EVERY PAGE) ====================
11.1 ACTION AUDIT: Export/Create/row actions/Save Draft/Save/Submit/Cancel/Delete/
Save Changes/Print/Convert/Approve/Reject/comment all trigger the SAME handlers/routes
as pre-implementation (spot-click each); lock rules preserved.
11.2 LIST: status boxes set existing filter; search/sort/pagination identical; counts
live; badges/priority chips per §2; heading reads "Purchase Requisitions".
11.3 CREATE/EDIT: live sumbar + always-visible totals with all rows; unit select +
inline item search work; edit pre-filled; hero amber/teal by status.
11.4 DETAIL: header shows breadcrumb + cluster only; profile identity-only; workflow
states match existing approval records; Approve/Reject visibility per status.
11.5 REPORTS: all six render from live data; filters + exports carry params; register
totals = sum of rows; missing report pages created per §8.4.
11.6 RAILS REGRESSION: slim/full/pins/global pin behave exactly as rails.html on these
and all other pages.
11.7 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; action-mapping table (old control → new location → handler
confirmed same); status/badge table; rail registry per page; report pages created;
confirmation rails + all existing functionality unchanged.