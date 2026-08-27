VENDOR CENTRE DASHBOARD — CLEAN REDESIGN — FULL IMPLEMENTATION SPEC (SELF-CONTAINED)
(SELF-CONTAINED — complete reference mockup HTML in APPENDIX A.)
SCOPE: ONE clean, uncluttered Vendor Centre dashboard: slim toolbar, stat strip, payables
aging, upcoming payments, tabbed Top Vendors, pending transactions, needs-attention
alerts, vendor balances and documents expiring soon. Dashboard is a READ-ONLY consumer;
every action drills into EXISTING pages/handlers.
SYSTEM CONSTRAINTS: currency from system setting (never hard-coded); system live-search
results overlay above all content (z-index ≥ 9999); page renders as mocked.
HARD GUARD — existing vendor master, AP subledger, approval engine, payment/PO/invoice
handlers and routes remain EXACTLY as-is; dashboard adds no writes except user preference
(last-used Top Vendors tab).

==================== 0 · DISCOVERY ====================
0.1 Locate vendor master table + statuses, AP subledger (invoices/payments/credit notes),
approval-engine queues (PO/GRN/invoice/payment), vendor documents table, calendar page.
0.2 List CURRENT vendor dashboard controls/handlers (drives §12 audit).

==================== 1 · DATA SOURCES / DERIVED METRICS ====================
vendors(id, code, name, category, status ENUM[active,inactive,suspended,pending,
restricted], credit_limit, tax_info_complete BOOL).
Balances per vendor from AP subledger: opening + purchases − payments − returns = closing.
Payables aging: bucket unpaid invoices by days-overdue (Current/1–30/31–60/61–90/91–120/
120+); sum amounts + distinct vendor counts.
Upcoming payments: unpaid invoices due ≤ 30 days ordered by due date (severity dots:
today red / tomorrow orange / ≤7d amber / later green).
Pending transactions: approval-engine counts by stage/status (PO awaiting approval &
partially received; GRN awaiting invoice/verification; invoices awaiting approval &
unpaid/partially paid; payments pending authorization).
Documents: vendor_id/doc_type/expiry; "expiring soon" = expiry ≤ 60 days (red ≤ 30).
Alerts (Needs Attention): overdue vendor count; invoices due ≤ 7d; awaiting authorization;
documents expiring soon; over-credit-limit; incomplete tax info.

==================== 2 · PAGE STRUCTURE (match APPENDIX A) ====================
2.1 HEADER: h1 "Vendors" + sub "Accounts payable overview"; vendor search; single-line
toolbar: [＋ New Vendor CTA][Purchase Order][Invoice][Payment][Credit Note][More ⌄].
NO sub-labels on toolbar buttons. More ⌄ = Import Vendors / Export Vendors / Vendor
Statement / Aging Report.
2.2 STAT STRIP (one card, hairline dividers, 5 stats): Vendors (n + active) · Total
Payables · Due This Week · Overdue (red) · Purchases YTD (+% vs LY). Each stat clickable
→ filtered list.
2.3 ROW: Payables Aging card (6 proportional bars + amounts; link "Aging report →") |
Upcoming Payments card (4 severity-dot rows; link "Calendar →").
2.4 Top Vendors card (full width): tabs [By Purchase Value | By Outstanding | By
Transaction Count] re-sort table live; columns Vendor/Purchases/Outstanding/Transactions/
Last Purchase; outstanding ≥ 10M shown red; link "All vendors →".
2.5 ROW: Pending Vendor Transactions card (Stage/Status/Count; link "Authorizations →") |
Needs Attention card (4 dot rows with counts; link "Alerts →").
2.6 ROW: Vendor Balances card (Opening/Purchases/Payments/Returns/Closing; link "All
balances →") | Documents Expiring Soon card (Vendor/Document/Expiry, red ≤30d; link
"Compliance →").

==================== 3 · BEHAVIOUR ====================
3.1 Toolbar buttons route to existing create pages; More menu closes on outside click.
3.2 Top Vendors tab choice persisted per user; default By Purchase Value.
3.3 Stat/card links open existing filtered lists (overdue, due-this-week, aging, calendar,
vendors, authorizations, balances, compliance).
3.4 Numbers formatted with system currency symbol + tabular-nums; negatives in
parentheses.

==================== 4 · PERMISSIONS ====================
View dashboard: all finance roles. Create actions + imports gated by authorization engine
per feature. Vendors with restricted status hidden from non-admin roles.

==================== 5 · A11Y / RESPONSIVE ====================
Toolbar wraps; stat strip 5→2 cols ≤900px; grids 2→1 ≤1000px; tables horizontal-scroll;
tabs keyboard-operable (role=tablist); focus rings #94a3b8; text-size matrix 90–125 no
clipping; no horizontal PAGE scrollbar at 1280/1024/768; no console errors.

==================== 6 · CONSTRAINTS (PIXEL PARITY) ====================
Replicate APPENDIX A exactly: clean white cards, hairline dividers, single accent palette
(teal; red overdue; amber due-soon), NO badge/chip clutter, generous whitespace, single-
line toolbar, slim stat strip. System currency; search overlay top-most.

==================== 7 · VERIFY ====================
7.1 All metrics reconcile to subledger (aging sums = total payables; balances closing =
opening+purchases−payments−returns; due-this-week = invoices due ≤7d).
7.2 Tabs re-sort correctly + persist; More menu works; every link/button reaches existing
route (spot-click each). 7.3 Alerts counts match underlying queries. 7.4 Screens match
APPENDIX A; responsive + a11y pass; no console errors; no handler changes.
REPORT: metric→query map; link/route table; persistence proof; parity confirmation;
NO SECTION SKIPPED.

==================== APPENDIX A — EMBEDDED REFERENCE MOCKUP (HTML) ====================
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vendors — clean dashboard (full)</title>
<style>
  :root{--deep-2:#0c3539;--sec:#128F8E;--ink:#0B2A2D;--sub:#41585c;--muted:#5f7476;--faint:#8aa5a7;--border:#dceaea;--line:#e2ecec;--hair:#EEF3F1;--bg:#eef4f4;--green:#15803d;--red:#b91c1c;--amber:#b45309;
    --shadow:0 1px 2px rgba(10,42,46,.04),0 10px 26px -14px rgba(10,42,46,.14);}
  *{box-sizing:border-box;margin:0;padding:0}html,body{overflow-x:clip}
  body{font-family:Inter,"Segoe UI",system-ui,sans-serif;background:var(--bg);color:#374151;font-size:14px;-webkit-font-smoothing:antialiased}
  .wrap{max-width:1200px;margin:0 auto;padding:28px 28px 60px}
  .head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
  .head h1{font-size:22px;font-weight:850;color:var(--ink)}
  .head .sub{font-size:12px;color:var(--muted);margin-top:3px}
  .head .grow{flex:1}
  .search{height:40px;width:260px;border-radius:11px;border:1px solid var(--border);padding:0 14px 0 36px;font-family:inherit;font-size:12.5px;color:var(--ink);background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='%235f7476' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cline x1='21' y1='21' x2='16' y2='16'/%3E%3C/svg%3E") no-repeat 12px center}
  .search:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.12)}
  .btn{display:inline-flex;align-items:center;gap:7px;height:40px;padding:0 16px;border-radius:11px;border:1px solid transparent;cursor:pointer;font-family:inherit;font-weight:700;font-size:12.5px;white-space:nowrap}
  .btn-cta{color:#fff;background:var(--deep-2)}
  .btn-ghost{color:var(--sub);background:#fff;border-color:var(--border)}
  .btn-ghost:hover{color:var(--sec);border-color:rgba(18,143,142,.5)}
  .more{position:relative}
  .menu{position:absolute;right:0;top:46px;background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);padding:6px;display:none;min-width:170px;z-index:40}
  .menu.on{display:block}
  .menu button{display:block;width:100%;text-align:left;border:none;background:none;padding:9px 12px;border-radius:8px;font-family:inherit;font-size:12.5px;font-weight:600;color:var(--sub);cursor:pointer}
  .menu button:hover{background:var(--hair);color:var(--ink)}
  .stats{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);display:grid;grid-template-columns:repeat(5,1fr);margin-bottom:20px;overflow:hidden}
  @media(max-width:900px){.stats{grid-template-columns:repeat(2,1fr)}}
  .stat{padding:16px 20px;border-left:1px solid var(--line)}
  .stat:first-child{border-left:none}
  .stat .l{font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
  .stat .v{font-size:20px;font-weight:850;color:var(--ink);margin-top:4px;font-variant-numeric:tabular-nums}
  .stat .v.red{color:var(--red)}
  .stat .n{font-size:10.5px;color:var(--faint);margin-top:2px}
  .grid{display:grid;grid-template-columns:1.4fr 1fr;gap:20px;margin-bottom:20px}
  @media(max-width:1000px){.grid{grid-template-columns:1fr}}
  .card{background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow);overflow:hidden;margin-bottom:20px}
  .grid .card{margin-bottom:0}
  .card-h{display:flex;align-items:center;gap:10px;padding:14px 20px;border-bottom:1px solid var(--line);flex-wrap:wrap}
  .card-h h2{font-size:13px;font-weight:800;color:var(--ink)}
  .card-h a{margin-left:auto;font-size:11.5px;font-weight:700;color:var(--sec);text-decoration:none}
  .card-b{padding:16px 20px}
  .tabs{display:flex;gap:4px;background:#e8f0f0;padding:4px;border-radius:11px;margin-left:auto}
  .tabs button{border:none;background:transparent;height:30px;padding:0 12px;border-radius:8px;font-weight:800;font-size:11px;color:var(--muted);cursor:pointer}
  .tabs button.on{background:#fff;color:var(--sec);box-shadow:0 1px 2px rgba(8,40,44,.1)}
  .age{display:flex;flex-direction:column;gap:10px}
  .age .r{display:grid;grid-template-columns:80px 1fr 100px;gap:12px;align-items:center}
  .age .lab{font-size:11.5px;font-weight:700;color:var(--sub)}
  .age .bar{height:8px;border-radius:4px;background:var(--hair);overflow:hidden}
  .age .bar i{display:block;height:100%;border-radius:4px}
  .age .amt{font-size:12px;font-weight:700;color:var(--ink);text-align:right;font-variant-numeric:tabular-nums}
  .due{display:flex;flex-direction:column}
  .due .i{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--hair)}
  .due .i:last-child{border-bottom:none}
  .due .dot{width:8px;height:8px;border-radius:50%;flex:none}
  .due .t{font-size:12.5px;font-weight:700;color:var(--ink)}
  .due .s{font-size:11px;color:var(--muted)}
  .due .amt{margin-left:auto;font-size:12.5px;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
  table{width:100%;border-collapse:collapse}
  th{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);text-align:left;padding:8px 20px;border-bottom:1px solid var(--line)}
  th.num,td.num{text-align:right}
  td{padding:11px 20px;border-bottom:1px solid var(--hair);font-size:12.5px;color:var(--sub)}
  tr:last-child td{border-bottom:none}
  td.num{font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)}
  .name{font-weight:700;color:var(--ink)}
  .neg{color:var(--red)}
  .att{display:flex;flex-direction:column}
  .att .i{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--hair);font-size:12.5px;color:var(--sub)}
  .att .i:last-child{border-bottom:none}
  .att .dot{width:8px;height:8px;border-radius:50%;flex:none}
  .att .n{margin-left:auto;font-size:12px;font-weight:800;color:var(--ink)}
</style>
</head>
<body>
<div class="wrap">

  <!-- header + single-line toolbar -->
  <div class="head">
    <div><h1>Vendors</h1><div class="sub">Accounts payable overview</div></div>
    <div class="grow"></div>
    <input class="search" placeholder="Search vendors…">
    <button class="btn btn-cta">＋ New Vendor</button>
    <button class="btn btn-ghost">Purchase Order</button>
    <button class="btn btn-ghost">Invoice</button>
    <button class="btn btn-ghost">Payment</button>
    <button class="btn btn-ghost">Credit Note</button>
    <div class="more">
      <button class="btn btn-ghost" id="moreBtn">More ⌄</button>
      <div class="menu" id="moreMenu">
        <button>Import Vendors</button><button>Export Vendors</button>
        <button>Vendor Statement</button><button>Aging Report</button>
      </div>
    </div>
  </div>

  <!-- stat strip -->
  <div class="stats">
    <div class="stat"><div class="l">Vendors</div><div class="v">280</div><div class="n">245 active</div></div>
    <div class="stat"><div class="l">Total Payables</div><div class="v">K73.9M</div><div class="n">outstanding</div></div>
    <div class="stat"><div class="l">Due This Week</div><div class="v">K3.92M</div><div class="n">4 payments</div></div>
    <div class="stat"><div class="l">Overdue</div><div class="v red">K16.4M</div><div class="n">23 vendors</div></div>
    <div class="stat"><div class="l">Purchases YTD</div><div class="v">K485.6M</div><div class="n">+8.2% vs LY</div></div>
  </div>

  <!-- aging + upcoming -->
  <div class="grid">
    <div class="card">
      <div class="card-h"><h2>Payables Aging</h2><a href="#">Aging report →</a></div>
      <div class="card-b"><div class="age">
        <div class="r"><span class="lab">Current</span><div class="bar"><i style="width:100%;background:var(--sec)"></i></div><span class="amt">K45.0M</span></div>
        <div class="r"><span class="lab">1–30</span><div class="bar"><i style="width:28%;background:#3aa7a0"></i></div><span class="amt">K12.5M</span></div>
        <div class="r"><span class="lab">31–60</span><div class="bar"><i style="width:15%;background:var(--amber)"></i></div><span class="amt">K6.8M</span></div>
        <div class="r"><span class="lab">61–90</span><div class="bar"><i style="width:7%;background:#d97706"></i></div><span class="amt">K3.2M</span></div>
        <div class="r"><span class="lab">91–120</span><div class="bar"><i style="width:4%;background:var(--red)"></i></div><span class="amt">K1.9M</span></div>
        <div class="r"><span class="lab">120+</span><div class="bar"><i style="width:10%;background:var(--red)"></i></div><span class="amt">K4.5M</span></div>
      </div></div>
    </div>
    <div class="card">
      <div class="card-h"><h2>Upcoming Payments</h2><a href="#">Calendar →</a></div>
      <div class="card-b"><div class="due">
        <div class="i"><span class="dot" style="background:var(--red)"></span><div><div class="t">ABC Suppliers</div><div class="s">Today</div></div><span class="amt">K2,500,000</span></div>
        <div class="i"><span class="dot" style="background:#d97706"></span><div><div class="t">XYZ Hardware</div><div class="s">Tomorrow</div></div><span class="amt">K850,000</span></div>
        <div class="i"><span class="dot" style="background:var(--amber)"></span><div><div class="t">Office Solutions</div><div class="s">25 Aug</div></div><span class="amt">K450,000</span></div>
        <div class="i"><span class="dot" style="background:var(--green)"></span><div><div class="t">Malawi Stationery</div><div class="s">28 Aug</div></div><span class="amt">K120,000</span></div>
      </div></div>
    </div>
  </div>

  <!-- top vendors with tabs -->
  <div class="card">
    <div class="card-h"><h2>Top Vendors</h2>
      <div class="tabs" id="topTabs">
        <button class="on" data-k="spend">By Purchase Value</button>
        <button data-k="out">By Outstanding</button>
        <button data-k="count">By Transaction Count</button>
      </div>
      <a href="#" style="margin-left:12px">All vendors →</a></div>
    <table><thead><tr><th>Vendor</th><th class="num">Purchases</th><th class="num">Outstanding</th><th class="num">Transactions</th><th>Last Purchase</th></tr></thead>
    <tbody id="topBody"></tbody></table>
  </div>

  <!-- pending transactions + attention -->
  <div class="grid">
    <div class="card">
      <div class="card-h"><h2>Pending Vendor Transactions</h2><a href="#">Authorizations →</a></div>
      <table><thead><tr><th>Stage</th><th>Status</th><th class="num">Count</th></tr></thead><tbody>
        <tr><td class="name">Purchase Orders</td><td>Awaiting Approval</td><td class="num">6</td></tr>
        <tr><td class="name">Purchase Orders</td><td>Partially Received</td><td class="num">3</td></tr>
        <tr><td class="name">Goods Received</td><td>Awaiting Invoice / Verification</td><td class="num">7</td></tr>
        <tr><td class="name">Purchase Invoices</td><td>Awaiting Approval</td><td class="num">5</td></tr>
        <tr><td class="name">Purchase Invoices</td><td>Unpaid / Partially Paid</td><td class="num">15</td></tr>
        <tr><td class="name">Payments</td><td>Pending Authorization</td><td class="num">4</td></tr>
      </tbody></table>
    </div>
    <div class="card">
      <div class="card-h"><h2>Needs Attention</h2><a href="#">Alerts →</a></div>
      <div class="card-b"><div class="att">
        <div class="i"><span class="dot" style="background:var(--red)"></span>Overdue balances<span class="n">23</span></div>
        <div class="i"><span class="dot" style="background:var(--amber)"></span>Invoices due within 7 days<span class="n">12</span></div>
        <div class="i"><span class="dot" style="background:var(--amber)"></span>Awaiting authorization<span class="n">5</span></div>
        <div class="i"><span class="dot" style="background:#d97706"></span>Documents expiring soon<span class="n">3</span></div>
      </div></div>
    </div>
  </div>

  <!-- balances + documents -->
  <div class="grid">
    <div class="card">
      <div class="card-h"><h2>Vendor Balances</h2><a href="#">All balances →</a></div>
      <table><thead><tr><th>Vendor</th><th class="num">Opening</th><th class="num">Purchases</th><th class="num">Payments</th><th class="num">Returns</th><th class="num">Closing</th></tr></thead><tbody>
        <tr><td class="name">ABC Suppliers</td><td class="num">5.0M</td><td class="num">20.0M</td><td class="num">(15.0M)</td><td class="num">(1.0M)</td><td class="num">9.0M</td></tr>
        <tr><td class="name">Kamuzu Estates</td><td class="num">0</td><td class="num">6.5M</td><td class="num">0</td><td class="num">0</td><td class="num">6.5M</td></tr>
        <tr><td class="name">XYZ Ltd</td><td class="num">2.0M</td><td class="num">12.0M</td><td class="num">(8.0M)</td><td class="num">0</td><td class="num">6.0M</td></tr>
        <tr><td class="name">Office Solutions</td><td class="num">1.0M</td><td class="num">4.5M</td><td class="num">(3.0M)</td><td class="num">(0.5M)</td><td class="num">2.0M</td></tr>
      </tbody></table>
    </div>
    <div class="card">
      <div class="card-h"><h2>Documents Expiring Soon</h2><a href="#">Compliance →</a></div>
      <table><thead><tr><th>Vendor</th><th>Document</th><th>Expiry</th></tr></thead><tbody>
        <tr><td class="name">ABC Suppliers</td><td>Tax Certificate</td><td class="neg">15 Sep 2026</td></tr>
        <tr><td class="name">XYZ Ltd</td><td>Insurance</td><td class="neg">28 Sep 2026</td></tr>
        <tr><td class="name">Office Solutions</td><td>Business Registration</td><td>02 Oct 2026</td></tr>
      </tbody></table>
    </div>
  </div>

</div>
<script>
var VEND=[
 {n:'ABC Suppliers',spend:85,out:12,count:214,last:'20 Aug'},
 {n:'XYZ Limited',spend:62,out:8,count:178,last:'18 Aug'},
 {n:'Office Solutions',spend:45,out:2,count:156,last:'15 Aug'},
 {n:'Kamuzu Estates',spend:38,out:6.5,count:92,last:'12 Aug'},
 {n:'AHL Group',spend:29,out:0,count:84,last:'10 Aug'}];
function renderTop(k){
  var s=VEND.slice().sort(function(a,b){return k==='out'?b.out-a.out:(k==='count'?b.count-a.count:b.spend-a.spend);});
  document.getElementById('topBody').innerHTML=s.map(function(v){
    return '<tr><td class="name">'+v.n+'</td><td class="num">K'+v.spend.toFixed(1)+'M</td><td class="num'+(v.out>=10?' neg':'')+'">K'+v.out.toFixed(1)+'M</td><td class="num">'+v.count+'</td><td>'+v.last+'</td></tr>';
  }).join('');
}
document.getElementById('topTabs').addEventListener('click',function(e){
  var b=e.target.closest('button');if(!b)return;
  this.querySelectorAll('button').forEach(function(x){x.classList.remove('on');});
  b.classList.add('on');renderTop(b.dataset.k);
});
document.getElementById('moreBtn').addEventListener('click',function(e){
  e.stopPropagation();document.getElementById('moreMenu').classList.toggle('on');
});
document.addEventListener('click',function(){document.getElementById('moreMenu').classList.remove('on');});
renderTop('spend');
</script>
</body>
</html>
```