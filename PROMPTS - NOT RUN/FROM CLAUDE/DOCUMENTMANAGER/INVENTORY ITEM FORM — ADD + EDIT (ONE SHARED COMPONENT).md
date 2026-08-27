INVENTORY ITEM FORM — ADD + EDIT (ONE SHARED COMPONENT) — FULL IMPLEMENTATION SPEC
(SELF-CONTAINED — complete reference mockup HTML in APPENDIX A.)
SCOPE: "Add New Inventory Item" and "Edit Inventory Item" pages built from ONE shared
form component with identical sections, styling and behaviour; barcode/QR scanning;
conditional Stock and Returnable sections; per-item GL wiring incl. returnables.
SYSTEM CONSTRAINTS: currency from system setting (never hard-coded); system live-search
results overlay above all content (z-index ≥ 9999); pages render as mocked.
HARD GUARD — UPDATE, DON'T REPLACE: existing item model/handlers, inventory stock logic,
COGS, POS checkout, intake/redemption workflows and journal posting handler remain as-is;
item form writes master data only; postings flow through existing handlers.

==================== 0 · DISCOVERY ====================
0.1 Locate items/products table + create/update handlers, category/UOM/supplier lists,
account picker endpoints, price-list model, returnable items (deposit value) if any,
scanner hardware config, audit-log infrastructure.
0.2 List CURRENT add/edit item controls + handlers (drives §11 audit).

==================== 1 · SCHEMA (migrations) ====================
items ADD/CONFIRM: name, sku UQ, barcode UQ NULL, item_type ENUM[inventory,service,bundle],
category_id NULL, brand NULL, uom, tax_rate DEC, description NULL, track_inventory BOOL,
purchase_price DEC, sales_price DEC, reorder_point INT, reorder_qty INT, max_stock INT,
lead_time_days INT, default_supplier_id NULL, costing_method ENUM[weighted_avg,fifo],
income_account_id FK, expense_account_id FK, inventory_account_id FK, price_list_id NULL,
opening_stock DEC, opening_as_at DATE, warehouse_id NULL, low_stock_alerts BOOL,
batch_expiry_tracking BOOL, serial_tracking BOOL, active BOOL.
items_returnable (1:1): item_id FK UQ, is_returnable BOOL, container_type ENUM[bottle,
crate,keg,cylinder], deposit_value DEC, deposit_tax_handling ENUM[excluded,taxed],
return_window_days INT DEF 30, linked_empty_item_id FK NULL, linked_filled_item_id FK
NULL, required_return ENUM[one_to_one,free], container_stock_account_id FK (1320),
container_stock_tracking BOOL, allow_cash_refund BOOL.

==================== 2 · FORM STRUCTURE (shared add/edit) ====================
Sections in order, each a card with icon tile + title + note:
2.1 BASIC INFORMATION: Item Name* (span2) · Item Type* · Item Code/SKU* [+⚙ generate] ·
Barcode/QR [+📷 scan][+⚙ generate] + hint · Category · Brand · UoM · Tax Rate ·
Description (span3); toggle rows: Track Inventory · Is Returnable.
2.2 PRICING & GL: Purchase Price · Sales Price · Margin (live read-only) · Reorder Point ·
Income Account · Expense Account · Inventory Account · Price List.
2.3 STOCK & REORDERING (visible iff Track Inventory): Opening Stock · As At Date ·
Warehouse · Max Stock · Reorder Qty · Lead Time · Default Supplier · Costing Method;
toggles Low-stock alerts · Batch/expiry · Serial tracking.
2.4 RETURNABLE PARAMETERS (visible iff Is Returnable): Container Type · Deposit Value* ·
Deposit Tax Handling · Return Window · Linked Empty Container · Linked Filled Product ·
Required Return · Container Stock Account; toggles Container stock tracking · Allow cash
refund; GL wiring note (see §5).
2.5 STATUS: Active toggle + "Inactive items are hidden from transactions".
2.6 FOOTER (sticky): [Cancel][Save & Add Another (add only)][Save Item / Save Changes].

==================== 3 · EDIT PAGE PARITY ====================
Route inv.edit loads same component pre-filled (incl. returnable record); header shows
"{name} · {sku}" + status chip; toggles initialize section visibility; SKU/barcode
regenerate + scan available; "Save & Add Another" hidden; change audit on save
(old→new per field); deactivating an item with stock-on-hand or open transactions shows a
warning confirm (blocks only if unredeemed returnable credit exists).

==================== 4 · SCAN INTEGRATION ====================
4.1 📷 opens camera modal (html5-qrcode or zxing; QR + EAN/UPC/Code128); animated viewfinder;
on decode: fill Barcode; if payload maps to existing item → prompt "Open existing item?"
(edit) else continue new; USB wedge scanners type into focused Barcode input.
4.2 ⚙ generators: SKU-{seq} and EAN-13; uniqueness enforced server-side.
4.3 Recently-scanned chips (session) for quick re-select.

==================== 5 · GL WIRING + RETURNABLES ====================
5.1 Item posts use selected income/expense/inventory accounts on sale/purchase/COGS via
existing handlers. 5.2 Returnable: intake Dr {container_stock_account 1320} / Cr 2300
Customer Bottle Credits (qty × deposit); redemption at checkout Dr 2300 / Cr deposit
revenue (covered); extras' deposit Cr deposit revenue; void reverses intake.
5.3 deposit_tax_handling: excluded → deposit outside tax base; taxed → deposit included at
item tax rate. 5.4 return_window_days feeds BRR expiry validation; required_return drives
1:1 exchange at intake.

==================== 6 · VALIDATION ====================
Name/SKU required; SKU+barcode unique; deposit_value > 0 required when is_returnable;
prices/costs ≥ 0; opening stock ≥ 0; linked filled/empty items must themselves be active
inventory items (prevent self-link); conditional sections' fields required only when
visible (server mirrors client).

==================== 7 · PERMISSIONS / SECURITY ====================
Create/edit items: Inventory Manager+; view: all stock roles; manage returnable GL links:
Accountant+; audit all saves.

==================== 8 · A11Y / RESPONSIVE ====================
Labels associated; toggles keyboard-operable; modal focus-trapped; grids 4→2→1; sticky
footer never overlaps content; text-size matrix 90–125 no clipping; no console errors.

==================== 9 · CONSTRAINTS (PIXEL PARITY) ====================
Replicate APPENDIX A exactly on BOTH add and edit: card order/icons, small-caps labels,
⚙/ square buttons, toggle rows, conditional cards, GL note, sticky footer, scanner
modal. System currency; search overlay top-most.

==================== 10 · VERIFY ====================
10.1 Add: save creates item (+returnable row when flagged); Save & Add Another resets.
10.2 Edit: pre-fills all fields incl. returnable; save updates + audits; parity with add.
10.3 Conditionals: Track/Returnable toggles show/hide + server-side required rules.
10.4 Scan: decode fills barcode; existing-item prompt; generators unique.
10.5 GL: sale/purchase use item accounts; intake/redemption post §5.2; tax handling per
flag. 10.6 Screens match APPENDIX A (add + edit); no console errors; handlers untouched.
REPORT: migration list; form-field→column map; scan library + flow; validation matrix;
GL posting samples (sale/intake/redemption/void); edit-parity + audit samples;
confirmation NO SECTION SKIPPED.

==================== APPENDIX A — EMBEDDED REFERENCE MOCKUP (HTML) ====================
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add New Inventory Item — returnable parameters</title>
<style>
  :root{--deep-2:#0c3539;--sec:#128F8E;--ink:#0B2A2D;--sub:#41585c;--muted:#5f7476;--faint:#8aa5a7;--border:#dceaea;--line:#e2ecec;--green:#15803d;--red:#b91c1c;--amber:#b45309;--hair:#EEF3F1;--bg:#eef4f4;
    --shadow:0 1px 2px rgba(10,42,46,.04),0 12px 30px -14px rgba(10,42,46,.22);}
  *{box-sizing:border-box;margin:0;padding:0}html,body{overflow-x:clip}
  body{font-family:Inter,"Segoe UI",system-ui,sans-serif;background:var(--bg);color:#374151;font-size:14px;-webkit-font-smoothing:antialiased}
  .wrap{max-width:1100px;margin:0 auto;padding:24px 28px 110px}
  .crumbs{font-size:12px;font-weight:700;color:var(--muted);margin-bottom:10px}
  .crumbs .here{color:var(--ink)}
  .page-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:18px}
  h1{color:var(--ink);font-size:24px;font-weight:850}
  .sub{color:var(--muted);font-size:12.5px;margin-top:5px}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 18px;border-radius:12px;border:1px solid transparent;cursor:pointer;font-family:inherit;font-weight:700;font-size:13px;white-space:nowrap}
  .btn-cta{color:#fff;background:var(--deep-2)}
  .btn-ghost{color:var(--ink);background:#e8f0f0;border-color:var(--border)}
  .card{background:#fff;border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;margin-bottom:16px}
  .card-h{display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,#fbfdfd,#f4f8f8)}
  .card-h .ic{width:34px;height:34px;border-radius:11px;background:var(--deep-2);color:#fff;display:grid;place-items:center;font-size:15px;flex:none}
  .card-h .ic.teal{background:var(--sec)}
  .card-h h2{color:var(--ink);font-size:14px;font-weight:850}
  .card-h .n{margin-left:auto;font-size:11px;color:var(--muted);font-weight:700}
  .card-b{padding:20px 24px}
  .g3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
  .g4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
  .s2{grid-column:span 2}.s3{grid-column:span 3}.s4{grid-column:span 4}
  @media(max-width:900px){.g3,.g4{grid-template-columns:1fr 1fr}.s3,.s4{grid-column:span 2}}
  @media(max-width:600px){.g3,.g4{grid-template-columns:1fr}.s2,.s3,.s4{grid-column:span 1}}
  .f{display:flex;flex-direction:column;gap:6px}
  .f label{font-size:10.5px;font-weight:850;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);display:flex;justify-content:space-between}
  .f .req{color:var(--red)}
  .f .opt{color:var(--faint)}
  .f .in{height:44px;border-radius:11px;border:1px solid var(--border);padding:0 14px;font-family:inherit;font-size:13.5px;color:var(--ink);background:#fff;width:100%}
  .f textarea.in{height:auto;min-height:84px;padding:12px 14px;resize:vertical}
  .f select.in{appearance:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center;padding-right:36px}
  .f .in:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.12)}
  .ig{display:flex;gap:8px}
  .ig .in{flex:1}
  .sq{height:44px;width:44px;border-radius:11px;border:1px solid var(--border);background:#fff;cursor:pointer;display:grid;place-items:center;font-size:16px;color:var(--muted);flex:none;transition:.15s}
  .sq:hover{background:var(--sec);color:#fff;border-color:var(--sec)}
  .hint{font-size:10.5px;color:var(--faint)}
  .sw{width:40px;height:23px;border-radius:999px;background:#CBD8D6;position:relative;cursor:pointer;flex:none;transition:.2s}
  .sw.on{background:var(--sec)}
  .sw::after{content:"";position:absolute;top:3px;left:3px;width:17px;height:17px;border-radius:50%;background:#fff;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .sw.on::after{left:20px}
  .swrow{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:10px 0;border-bottom:1px solid var(--hair)}
  .swrow:last-child{border-bottom:none}
  .swrow .t{font-size:13px;font-weight:700;color:var(--ink)}
  .swrow .s{font-size:11px;color:var(--muted);margin-top:2px}
  .hidden{display:none}
  .gl-note{border:1px dashed rgba(18,143,142,.5);background:rgba(18,143,142,.06);border-radius:12px;padding:10px 14px;font-size:11.5px;color:var(--sec);font-weight:700;margin-top:14px}
  .footer{position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid var(--line);padding:14px 28px;z-index:50}
  .footer .inner{max-width:1100px;margin:0 auto;display:flex;justify-content:flex-end;gap:10px;align-items:center}
  .footer .left{margin-right:auto;font-size:12px;color:var(--muted);font-weight:700}
  .modal{position:fixed;inset:0;background:rgba(8,40,44,.85);display:none;place-items:center;z-index:90;padding:20px}
  .modal.on{display:grid}
  .scanner{width:min(420px,100%);background:#000;border-radius:20px;overflow:hidden;box-shadow:0 30px 60px -20px rgba(0,0,0,.6)}
  .scanner-h{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;color:#fff}
  .scanner-h h3{font-size:14px;font-weight:800}
  .scanner-h button{border:none;background:none;color:#fff;font-size:20px;cursor:pointer}
  .viewfinder{position:relative;height:340px;background:#1a1a1a}
  .viewfinder::before{content:"";position:absolute;inset:36px;border:3px solid rgba(18,143,142,.5);border-radius:16px}
  .viewfinder::after{content:"";position:absolute;left:36px;right:36px;height:2px;background:var(--sec);box-shadow:0 0 12px var(--sec);animation:scan 2s ease-in-out infinite}
  @keyframes scan{0%,100%{top:36px}50%{top:calc(100% - 38px)}}
  .vf-label{position:absolute;bottom:52px;left:0;right:0;text-align:center;color:#fff;font-size:13px;font-weight:700;text-shadow:0 2px 8px rgba(0,0,0,.6)}
  .scanner-f{padding:16px 20px;background:rgba(0,0,0,.8);display:flex;gap:10px;justify-content:center}
</style>
</head>
<body>
<div class="wrap">
  <div class="crumbs">Dashboard › Items › <span class="here">Add Item</span></div>

  <div class="page-head">
    <div><h1>Add New Inventory Item</h1>
      <div class="sub">Products, stock items and services with pricing, costing, stock control, barcode scanning &amp; returnables</div></div>
  </div>

  <!-- 1 · BASIC INFORMATION -->
  <div class="card">
    <div class="card-h"><span class="ic">📦</span><h2>Basic Information</h2><span class="n">Identity &amp; classification</span></div>
    <div class="card-b">
      <div class="g3">
        <div class="f s2"><label>Item Name <span class="req">*</span></label><input class="in" placeholder="e.g. Coca-Cola 500ml Filled"></div>
        <div class="f"><label>Item Type <span class="req">*</span></label>
          <select class="in"><option>Inventory Item</option><option>Service</option><option>Bundle</option></select></div>
        <div class="f"><label>Item Code / SKU <span class="req">*</span></label>
          <div class="ig"><input class="in" id="sku" placeholder="e.g. SKU-0001"><button class="sq" title="Generate SKU" onclick="genSKU()">⚙</button></div></div>
        <div class="f"><label>Barcode / QR</label>
          <div class="ig"><input class="in" id="barcode" placeholder="Scan or generate"><button class="sq" title="Scan QR / barcode" onclick="openScanner()">📷</button><button class="sq" title="Generate barcode" onclick="genBar()">⚙</button></div>
          <div class="hint">Scan with camera or USB scanner · EAN/UPC/QR supported</div></div>
        <div class="f"><label>Category</label><select class="in"><option>None</option><option>Staples</option><option>Beverages</option><option>Bakery</option><option>Dairy</option><option>Household</option></select></div>
        <div class="f"><label>Brand <span class="opt">Optional</span></label><input class="in" placeholder="e.g. Coca-Cola"></div>
        <div class="f"><label>Unit of Measure</label><select class="in"><option>pcs</option><option>kg</option><option>box</option><option>bag</option><option>bt</option><option>case</option><option>litre</option></select></div>
        <div class="f"><label>Tax Rate (%)</label><select class="in"><option>0</option><option>16</option><option>5</option></select></div>
        <div class="f s3"><label>Description <span class="opt">Optional</span></label><textarea class="in" placeholder="Item description, specifications or notes…"></textarea></div>
      </div>
      <div style="margin-top:14px">
        <div class="swrow"><div><div class="t">Track Inventory</div><div class="s">Maintain stock levels, COGS and stock movements for this item</div></div><span class="sw on" id="trackSw"></span></div>
        <div class="swrow"><div><div class="t">Is Returnable</div><div class="s">Item carries a refundable container deposit (bottle / crate / keg / cylinder)</div></div><span class="sw" id="retSw"></span></div>
      </div>
    </div>
  </div>

  <!-- 2 · PRICING & GL -->
  <div class="card">
    <div class="card-h"><span class="ic">💰</span><h2>Pricing &amp; GL</h2><span class="n">Margins &amp; posting accounts</span></div>
    <div class="card-b">
      <div class="g4">
        <div class="f"><label>Purchase Price (cost)</label><input class="in" type="number" step="0.01" id="cost" placeholder="0.00" oninput="margin()"></div>
        <div class="f"><label>Sales Price</label><input class="in" type="number" step="0.01" id="price" placeholder="0.00" oninput="margin()"></div>
        <div class="f"><label>Margin</label><input class="in" id="margin" placeholder="—" readonly><div class="hint">Live: (price − cost) ÷ price</div></div>
        <div class="f"><label>Reorder Point</label><input class="in" type="number" placeholder="0"></div>
        <div class="f"><label>Income Account</label><select class="in"><option>None</option><option>4000 · Sales Revenue</option><option>4100 · Service Revenue</option><option>4900 · Bottle Deposit Revenue</option></select></div>
        <div class="f"><label>Expense Account</label><select class="in"><option>None</option><option>5000 · Cost of Goods Sold</option></select></div>
        <div class="f"><label>Inventory Account</label><select class="in"><option>None</option><option>1300 · Inventory</option><option>1320 · Returnable Containers</option></select></div>
        <div class="f"><label>Price List</label><select class="in"><option>Retail (default)</option><option>Wholesale</option><option>VIP</option></select></div>
      </div>
    </div>
  </div>

  <!-- 3 · STOCK & REORDERING -->
  <div class="card" id="stockCard">
    <div class="card-h"><span class="ic">🗄</span><h2>Stock &amp; Reordering</h2><span class="n">Visible when Track Inventory is on</span></div>
    <div class="card-b">
      <div class="g4">
        <div class="f"><label>Opening Stock</label><input class="in" type="number" placeholder="0"></div>
        <div class="f"><label>As At Date</label><input class="in" type="date"></div>
        <div class="f"><label>Warehouse / Location</label><select class="in"><option>Main Store</option><option>Branch 01</option><option>Branch 02</option></select></div>
        <div class="f"><label>Max Stock Level</label><input class="in" type="number" placeholder="0"></div>
        <div class="f"><label>Reorder Qty</label><input class="in" type="number" placeholder="0"></div>
        <div class="f"><label>Lead Time (days)</label><input class="in" type="number" placeholder="0"></div>
        <div class="f"><label>Default Supplier</label><select class="in"><option>None</option><option>Kamuzu Estates</option><option>AHL Group</option></select></div>
        <div class="f"><label>Costing Method</label><select class="in"><option>Weighted Average</option><option>FIFO</option></select></div>
      </div>
      <div style="margin-top:14px">
        <div class="swrow"><div><div class="t">Low-stock alerts</div><div class="s">Notify when stock falls below reorder point</div></div><span class="sw on"></span></div>
        <div class="swrow"><div><div class="t">Batch / expiry tracking</div><div class="s">Capture batch numbers and expiry dates on receipts</div></div><span class="sw"></span></div>
        <div class="swrow"><div><div class="t">Serial-number tracking</div><div class="s">Track individual serials on receipt and sale</div></div><span class="sw"></span></div>
      </div>
    </div>
  </div>

  <!-- 4 · RETURNABLE PARAMETERS -->
  <div class="card hidden" id="retCard">
    <div class="card-h"><span class="ic teal">🍾</span><h2>Returnable Parameters</h2><span class="n">Deposit &amp; redemption settings</span></div>
    <div class="card-b">
      <div class="g4">
        <div class="f"><label>Container Type</label><select class="in"><option>Bottle</option><option>Crate (24)</option><option>Keg</option><option>Cylinder</option></select></div>
        <div class="f"><label>Deposit Value (K) <span class="req">*</span></label><input class="in" type="number" step="0.01" placeholder="e.g. 200"></div>
        <div class="f"><label>Deposit Tax Handling</label><select class="in"><option>Deposit excluded from tax</option><option>Deposit taxed at item rate</option></select></div>
        <div class="f"><label>Return Window (days)</label><input class="in" type="number" placeholder="30"></div>
        <div class="f"><label>Linked Empty Container</label><select class="in"><option>None</option><option>Coca-Cola Empty Bottle</option><option>Beer Empty Bottle</option><option>Empty Crate (24)</option></select></div>
        <div class="f"><label>Linked Filled Product</label><select class="in"><option>None (this item is the filled product)</option><option>Coca-Cola 500ml Filled</option></select></div>
        <div class="f"><label>Required Return</label><select class="in"><option>Yes — 1:1 exchange</option><option>No — free return</option></select></div>
        <div class="f"><label>Container Stock Account</label><select class="in"><option>1320 · Returnable Containers</option></select></div>
      </div>
      <div style="margin-top:14px">
        <div class="swrow"><div><div class="t">Container stock tracking</div><div class="s">Track empties on hand (intake +N, redemption −N)</div></div><span class="sw on"></span></div>
        <div class="swrow"><div><div class="t">Allow cash refund on return</div><div class="s">Pay cash instead of store credit at intake</div></div><span class="sw"></span></div>
      </div>
      <div class="gl-note">GL wiring: intake posts Dr 1320 Returnable Containers / Cr 2300 Customer Bottle Credits · redemption at checkout cancels the filled-product deposit (Dr 2300 / Cr deposit revenue) · unused credit carries on the BRR (valid per return window).</div>
    </div>
  </div>

  <!-- 5 · STATUS -->
  <div class="card">
    <div class="card-b" style="padding:14px 24px">
      <div class="swrow" style="border:none;padding:4px 0"><div><div class="t">Active</div><div class="s">Inactive items are hidden from transactions</div></div><span class="sw on"></span></div>
    </div>
  </div>
</div>

<div class="footer"><div class="inner">
  <span class="left">Fields marked * are required</span>
  <button class="btn btn-ghost">Cancel</button>
  <button class="btn btn-ghost">Save &amp; Add Another</button>
  <button class="btn btn-cta">Save Item</button>
</div></div>

<!-- scanner modal -->
<div class="modal" id="scanner">
  <div class="scanner">
    <div class="scanner-h"><h3>Scan QR / Barcode</h3><button onclick="closeScanner()">✕</button></div>
    <div class="viewfinder"><div class="vf-label">Align code within frame</div></div>
    <div class="scanner-f"><button class="btn btn-ghost" onclick="closeScanner()">Cancel</button><button class="btn btn-cta" onclick="simScan()">Simulate Scan</button></div>
  </div>
</div>

<script>
function openScanner(){document.getElementById('scanner').classList.add('on');}
function closeScanner(){document.getElementById('scanner').classList.remove('on');}
function simScan(){closeScanner();document.getElementById('barcode').value='6009876543218';}
function genSKU(){document.getElementById('sku').value='SKU-'+String(Math.floor(1000+Math.random()*9000));}
function genBar(){document.getElementById('barcode').value='600'+String(Math.floor(1000000000+Math.random()*9000000000));}
function margin(){
  var c=parseFloat(document.getElementById('cost').value)||0,p=parseFloat(document.getElementById('price').value)||0;
  document.getElementById('margin').value=(p>0)?(((p-c)/p)*100).toFixed(1)+'%':'—';
}
document.getElementById('trackSw').addEventListener('click',function(){
  this.classList.toggle('on');
  document.getElementById('stockCard').classList.toggle('hidden',!this.classList.contains('on'));
});
document.getElementById('retSw').addEventListener('click',function(){
  this.classList.toggle('on');
  document.getElementById('retCard').classList.toggle('hidden',!this.classList.contains('on'));
});
document.getElementById('scanner').addEventListener('click',function(e){if(e.target===this)closeScanner();});
</script>
</body>
</html>
```