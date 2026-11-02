/**
 * Invoices (AR) register — APPENDIX A workspace interactions.
 *
 * Server adaptations (design record):
 * 1. Rows are server-rendered and the modals read `window.INV_DATA` (the spec mockup mutated an
 *    in-memory `IV` map client-side instead).
 * 2. Mutations POST JSON to the real invoice routes; outcomes replay through `sessionStorage`
 *    (flash toast / reopen intent) after a reload, or through the spec success box.
 * 3. Success-box "View invoice" reloads with `invOpenView` set, then re-opens the workspace modal
 *    (the spec reopened its client-side state directly).
 * 4. Split legs carry method-specific required details (`.legcell` inputs stacked above the leg
 *    reference) — the spec's legs only had a free-text reference.
 * 5. Period/search/sort submit the GET form (results are server-rendered): no client period state,
 *    `#pLabel` is server-owned, and `refresh()`/`sortRows()`/`renderQueue()` from the spec are
 *    intentionally dropped.
 * 6. `esc()` guards every payload string that reaches `innerHTML`.
 * 7. Create/edit (spec js.txt L717–932) posts the real `#invoice-form` instead of the spec's
 *    client-side `commitInvoice`: a submit helper syncs the hidden `mode` input and calls
 *    `requestSubmit()` on the clicked button (native fallback `form.submit()`).
 * 8. Terms use the server's keys (`due_on_receipt`/`net_15`/…) with `setTermsKey()` injecting a
 *    missing `<option>`, and `addDaysISO()` does TZ-safe day math via `localISO()` (the spec's
 *    `toISOString().slice(0,10)` shifts the day on non-UTC hosts).
 * 9. The quotation picker shows the payload's `q.total` (the spec recomputed `reduce() * 1.165`
 *    client-side) and generated line rows carry `name="lines[i][…]"` fields (the spec's rows had
 *    no names — the server form serialises them natively).
 *
 * Spec: docs/INVOICES MODULE.txt — APPENDIX A (L236+), JS (L1070+).
 */
(function () {
  'use strict';

  if (!document.getElementById('listView') && !document.getElementById('queueView') && !document.getElementById('createView')) return;

  /* ---------- globals (set by the blade script) ---------- */
  var DATA = window.INV_DATA || {};
  var NEXT_RC = window.INV_NEXT_RC || '';
  var CS = window.INV_CS || 'K';

  var EMAIL_RE = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;

  function $(id) { return document.getElementById(id); }
  function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  var toastEl = $('toast'), toastT;
  function toast(m) { if (!toastEl) return; toastEl.textContent = m; toastEl.classList.add('on'); clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('on'); }, 3400); }

  function fmt(n) { return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function num(s) { return Number(String(s == null ? 0 : s).replace(/,/g, '')) || 0; }
  function dfmt(iso) { if (!iso) return '—'; var d = new Date(iso + 'T00:00:00'); return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }
  function pad2(n) { return (n < 10 ? '0' : '') + n; }
  function localISO(y, m, d) { return y + '-' + pad2(m + 1) + '-' + pad2(d); }
  function todayISO() { var d = new Date(); return localISO(d.getFullYear(), d.getMonth(), d.getDate()); }
  function emailOk(v) { return EMAIL_RE.test(String(v || '').trim()); }

  var ONES = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
  var TENS = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
  function w3(n) { var s = ''; if (n >= 100) { s += ONES[Math.floor(n / 100)] + ' Hundred'; n %= 100; if (n) s += ' '; } if (n >= 20) { s += TENS[Math.floor(n / 10)]; if (n % 10) s += '-' + ONES[n % 10]; } else if (n > 0) s += ONES[n]; return s; }
  function inWords(n) { n = Math.floor(Math.abs(n)); if (!n) return 'Zero'; var out = [], sc = [[1e6, 'Million'], [1e3, 'Thousand']]; sc.forEach(function (s) { var v = Math.floor(n / s[0]); if (v) { out.push(w3(v) + ' ' + s[1]); n %= s[0]; } }); if (n) out.push(w3(n)); return out.join(' '); }

  var PILL = { draft: ['dr', 'Draft'], sent: ['se', 'Sent'], partial: ['pa', 'Partially paid'], paid: ['pd', 'Paid'], void: ['vo', 'Void'] };
  var SVG_VIEW = '<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  var SVG_PEN = '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4z"/></svg>';
  var SVG_SEND = '<svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>';
  var SVG_PRINT = '<svg viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>';
  var SVG_PAY = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 10h8M8 14h8"/></svg>';
  var SVG_POST = '<svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>';
  var SVG_USER = '<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
  var SVG_X = '<svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>';
  var CLOSE_BTN = '<button class="btn btn-close btn-sm" data-close="viewScrim">' + SVG_X + 'Close</button>';
  var ICONS = {
    primary: SVG_SEND,
    ok: '<svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>',
    danger: '<svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>'
  };

  /* spec method labels → server invoice_payment enums */
  var METHOD_VALUE = { 'Bank transfer': 'bank_transfer', 'Mobile money': 'mobile_money', 'Cash': 'cash', 'Card': 'card', 'Cheque': 'cheque', 'Split': 'split' };

  var PYFIELDS = {
    'Bank transfer': [
      { k: 'bank', l: 'Bank', t: 'sel', opts: ['National Bank of Malawi', 'Standard Bank', 'Ecobank Malawi', 'FDH Bank'] },
      { k: 'payer', l: 'Payer account name', t: 'in' },
      { k: 'txref', l: 'Transaction reference', t: 'in' }
    ],
    'Mobile money': [
      { k: 'provider', l: 'Service provider', t: 'sel', opts: ['Airtel Money', 'TNM Mpamba', 'Mo630 Wallet'] },
      { k: 'phone', l: 'Phone number', t: 'in', req: true },
      { k: 'accname', l: 'Account name', t: 'in' },
      { k: 'txref', l: 'Transaction reference', t: 'in' }
    ],
    'Cash': [
      { k: 'teller', l: 'Received by (teller)', t: 'in' },
      { k: 'till', l: 'Cash drawer / till', t: 'in' }
    ],
    'Card': [
      { k: 'card', l: 'Card type', t: 'sel', opts: ['Visa', 'Mastercard'] },
      { k: 'last4', l: 'Card last 4 digits', t: 'in' },
      { k: 'cardname', l: 'Name on card', t: 'in' },
      { k: 'issbank', l: 'Issuing bank', t: 'in' }
    ],
    'Cheque': [
      { k: 'bank', l: 'Drawn on bank', t: 'in', req: true },
      { k: 'chq', l: 'Cheque number', t: 'in', req: true },
      { k: 'chqdate', l: 'Cheque date', t: 'date' }
    ]
  };
  var SPLIT_METHODS = ['Bank transfer', 'Mobile money', 'Cash', 'Card', 'Cheque'];

  /* ---------- state ---------- */
  var viewingId = null, payId = null, okId = null, cbFn = null, pendingReload = false, okPrintUrl = null;

  function closeAllScrims() {
    ['viewScrim', 'payScrim', 'cfScrim', 'okScrim'].forEach(function (id) { var el = $(id); if (el) el.classList.remove('on'); });
  }
  function flashReload(msg) {
    try { if (msg) sessionStorage.setItem('invFlash', msg); } catch (e) { /* private mode */ }
    window.location.reload();
  }
  function submitForm() { var f = $('inv-list-form'); if (f) f.submit(); }

  /* ---------- fetch ---------- */
  function sendAction(url, data) {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': meta ? meta.getAttribute('content') : ''
      },
      body: JSON.stringify(data || {})
    }).then(function (res) {
      return res.json().catch(function () { return null; }).then(function (b) {
        if (!res.ok) { toast((b && b.message) || 'Something went wrong — please try again.'); return null; }
        return b || {};
      });
    }).catch(function () { toast('Network error — please try again.'); return null; });
  }

  /* ---------- confirm box (spec ask/cbOk/cbRow) ---------- */
  function ask(o) {
    $('cbIc').className = 'cb-ic ' + o.tone;
    $('cbIc').innerHTML = ICONS[o.tone] || ICONS.primary;
    $('cbTitle').textContent = o.title;
    $('cbMsg').innerHTML = o.msg;
    var ok = $('cbOk');
    ok.textContent = o.okLabel || 'Confirm';
    ok.className = 'btn btn-sm ' + (o.tone === 'danger' ? 'btn-danger' : o.tone === 'ok' ? 'btn-green' : 'btn-cta');
    cbFn = o.onOk;
    $('cfScrim').classList.add('on');
  }
  $('cbOk').addEventListener('click', function () {
    var f = cbFn;
    $('cfScrim').classList.remove('on');
    cbFn = null;
    if (f) f();
  });
  function cbRow(l, v, cls) {
    return '<div class="cb-row"><span class="l">' + esc(l) + '</span><b class="' + esc(cls || '') + '">' + esc(v) + '</b></div>';
  }

  /* ---------- success box ---------- */
  function showSuccess(o) {
    okId = o.id || null;
    okPrintUrl = o.printUrl || null;
    $('okTitle').textContent = o.title;
    $('okBody').innerHTML = o.body;
    $('okView').style.display = okId && DATA[okId] ? '' : 'none';
    if (okId && DATA[okId]) $('okView').textContent = 'View ' + DATA[okId].ref;
    $('okPrint').style.display = (o.print || (okId && DATA[okId])) ? '' : 'none';
    $('okScrim').classList.add('on');
  }
  $('okView').addEventListener('click', function () {
    try { if (okId) sessionStorage.setItem('invOpenView', okId); } catch (e) { /* private mode */ }
    window.location.reload();
  });
  $('okPrint').addEventListener('click', function () {
    $('okScrim').classList.remove('on');
    if (okId && DATA[okId]) {
      // R16: the preview sheet renders from the payload in a new tab (or the
      // in-app overlay when popups are blocked). Reload only when the tab
      // opened — closePreview() picks the flag up on the fallback path.
      if (openPrintTab(okId) && pendingReload) window.location.reload();
    } else {
      var url = okPrintUrl || null;
      if (url) window.open(url, '_blank');
      if (pendingReload) window.location.reload();
    }
  });

  /* ---------- PRINT (spec js.txt L538-637: sheet + new tab + in-app fallback) ---------- */
  function rowOf(id) { return document.querySelector('#iBody tr[data-id="' + id + '"], #qTBody tr[data-id="' + id + '"]'); }
  function cell(l, v, mono) { return '<div class="cell"><div class="l">' + esc(l) + '</div><div class="v' + (mono ? ' mono' : '') + '">' + v + '</div></div>'; }
  function sheetBodyHTML(id) {
    var iv = DATA[id];
    if (!iv) return '';
    var tr = rowOf(id), st = iv.status, pill = PILL[st] || PILL.sent;
    var ref = (tr && tr.querySelector('.ino') ? tr.querySelector('.ino').textContent : '') || iv.ref || '';
    var pc = iv.pc || {};
    var total = iv.sub + iv.tax, paid = iv.paid, bal = iv.balance;
    var now = new Date();
    var draft = st === 'draft';
    var h = '<div class="wm"><span style="' + (draft ? '' : 'display:none') + '">Draft</span></div><div class="sheet-body">';
    h += '<header class="v-head"><div class="brand"><div class="mark">' + esc(pc.mark || '—') + '</div><div><div class="bn">' + esc(pc.name || '—') + '</div><div class="bl">' + esc(pc.addr || '—') +
      ((pc.phone || pc.email) ? '<br>' + (pc.phone ? 'Phone: ' + esc(pc.phone) : '') + (pc.phone && pc.email ? ' · ' : '') + (pc.email ? 'Email: ' + esc(pc.email) : '') : '') + '</div></div></div>' +
      '<div class="docblock"><div class="dt">Tax Invoice</div><div class="dn">' + esc(ref) + '</div>' +
      '<span class="dst' + (draft ? ' draftst' : st === 'paid' ? ' paidst' : '') + '"><i></i>' + esc(st === 'partial' ? 'Part paid' : pill[1]) + '</span>' +
      '<div class="dp">Printed ' + now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ' · ' + now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }) + '</div></div></header>';
    h += '<div class="meta">' +
      cell('Bill to', esc(iv.cust)) + cell('Contact person', esc(iv.contact || '—')) + cell('Email', esc(iv.email || '—'), true) + cell('Phone', esc(iv.phone || '—')) +
      cell('Issue date', dfmt(iv.issue)) + cell('Due date', dfmt(iv.due)) + cell('Payment terms', esc(iv.terms || '—')) + cell('Source', esc(iv.source || '—'), true) +
      cell('Customer ref', esc(iv.refNo || '—')) + cell('Cost centre', esc(iv.cc || '—'), true) + cell('Prepared by', esc(iv.prepared || '—')) + cell('Balance due', esc(iv.cs + ' ' + fmt(bal)), true) +
      '</div>';
    h += '<div class="vlines"><div class="lt">Billed items</div><table><thead><tr><th style="width:30px">#</th><th>Description</th><th class="r" style="width:60px">Qty</th><th class="r" style="width:110px">Unit price</th><th class="r" style="width:110px">Amount</th></tr></thead><tbody>';
    h += iv.lines.map(function (l, i) { return '<tr><td class="no">' + (i + 1) + '</td><td>' + esc(l[0]) + '</td><td class="num">' + esc(l[1]) + '</td><td class="num">' + esc(l[2]) + '</td><td class="num">' + esc(l[3]) + '</td></tr>'; }).join('');
    h += '<tr class="totals"><td></td><td style="font-size:10px;font-weight:900;letter-spacing:.14em;text-transform:uppercase;color:var(--p-ink)">Totals</td><td></td><td class="num" style="font-size:11px">' + fmt(iv.sub) + '</td><td class="num" style="font-size:12.5px;font-weight:900">' + fmt(total) + '</td></tr>';
    h += '<tr class="words"><td colspan="5"><span class="words-l">Amount in words</span><span class="words-v">' + esc((pc.currency || 'Kwacha') + ' ' + inWords(Math.floor(total)) + ' only') + '</span><span class="balnote">' + esc(bal > 0 ? 'BALANCE DUE ' + iv.cs + ' ' + fmt(bal) : 'PAID IN FULL') + '</span></td></tr>';
    h += '</tbody></table></div>';
    if (iv.payments.length) {
      h += '<div class="paystrip"><div class="nh">Payments received</div><table><tbody>';
      h += iv.payments.map(function (p) {
        return '<tr><td style="padding:7px 12px;font-size:10.5px;color:var(--p-sub)">' + esc(p.date) + '</td><td style="padding:7px 12px;font-size:10.5px;color:var(--p-sub)">' + esc(p.method) + '</td><td style="padding:7px 12px;font-size:10.5px;font-family:var(--mono);color:var(--p-accent);font-weight:800">' + esc(p.ref) + '</td><td style="padding:7px 12px;font-size:10.5px;text-align:right;font-weight:800;color:var(--p-ink)">' + esc(p.amount) + '</td></tr>';
      }).join('');
      h += '<tr><td colspan="3" style="padding:7px 12px;font-size:9px;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:var(--p-muted)">Total received</td><td style="padding:7px 12px;font-size:10.5px;text-align:right;font-weight:900;color:var(--p-accent)">' + fmt(paid) + '</td></tr></tbody></table></div>';
    }
    h += '<div class="qpanels"><div class="narr"><div class="nh">Notes to customer</div><div class="nb">' + esc(iv.notes || '—') + '</div></div>' +
      '<div class="narr"><div class="nh">Remittance information</div><div class="tlist">' +
      '<div>Bank: ' + esc(pc.bank_name || '—') + ' · Branch: ' + esc(pc.bank_branch || '-') + '.</div>' +
      '<div>Account name: ' + esc(pc.name || '—') + ' · Account № ' + esc(pc.bank_account || '—') + '.</div>' +
      (pc.phone ? '<div>Mobile money: ' + esc(pc.phone) + ' (' + esc(pc.name || 'Company') + ' Collections).</div>' : '') +
      '<div>Quote invoice number ' + esc(ref) + ' as payment reference.</div>' +
      '<div>Late payments attract 2% monthly interest per terms.</div>' +
      '</div></div></div>';
    h += '</div>';
    h += '<div class="v-foot"><span class="fa">AUDIT · issued ' + dfmt(iv.issue) + ' · ' + esc(iv.prepared || '—') + ' · status ' + esc(pill[1]) + (iv.emailed ? ' · emailed ' + esc(iv.emailed) : '') + ' · receipts ' + iv.payments.length + '</span>' +
      '<span class="fb">Computer-generated tax invoice — valid without signature</span><span class="fc">PAGE 1 OF 1</span></div>';
    return h;
  }
  var PRINT_CSS = 'body{font-family:Inter,-apple-system,sans-serif;background:linear-gradient(180deg,#e6edec,#dde6e5);margin:0;color:#13292C;-webkit-print-color-adjust:exact;print-color-adjust:exact}' +
    '.ptoolbar{position:sticky;top:0;z-index:50;display:flex;align-items:center;gap:12px;padding:12px 22px;background:rgba(255,255,255,.96);border-bottom:1px solid #DCE7E5;flex-wrap:wrap}' +
    '.tb-title{font-size:13px;font-weight:800}.tb-title span{font-family:monospace;font-size:11.5px;font-weight:800;color:#0E7473;background:rgba(14,116,115,.08);border:1px solid rgba(14,116,115,.22);padding:3px 8px;border-radius:7px;margin-left:8px}' +
    '.tb-spacer{flex:1}.tb-btn{display:inline-flex;align-items:center;gap:8px;height:36px;padding:0 15px;border-radius:10px;border:1px solid #DCE7E5;background:#fff;font-size:12.5px;font-weight:700;color:#3E5658;cursor:pointer}.tb-btn.primary{background:linear-gradient(180deg,#149897,#128F8E);border-color:transparent;color:#fff}.tb-btn svg{width:14px;height:14px;stroke:currentColor;stroke-width:2.2;fill:none;stroke-linecap:round;stroke-linejoin:round}' +
    '.sheet-wrap{display:flex;justify-content:center;padding:34px 20px 60px}' +
    '.sheet{position:relative;width:794px;min-height:1123px;background:#fff;border:1px solid #DCE7E5;box-shadow:0 2px 6px rgba(10,42,46,.08),0 30px 70px -30px rgba(10,42,46,.45);overflow:hidden;display:flex;flex-direction:column}' +
    '.sheet::before{content:"";display:block;height:5px;flex:none;background:linear-gradient(90deg,#0E7473,#149897 55%,#7FD1C0)}' +
    '.wm{position:absolute;inset:0;display:grid;place-items:center;pointer-events:none}.wm span{transform:rotate(-24deg);font-size:100px;font-weight:900;letter-spacing:.18em;color:rgba(14,116,115,.05);text-transform:uppercase}' +
    '.sheet-body{flex:1 0 auto;display:flex;flex-direction:column;position:relative;z-index:1}' +
    '.v-head{display:flex;justify-content:space-between;gap:24px;padding:26px 46px 20px;background:linear-gradient(180deg,#F0F5F5,#f7fbfa);border-bottom:1px solid #DCE7E5}' +
    '.brand{display:flex;gap:13px}.brand .mark{width:42px;height:42px;border-radius:10px;background:linear-gradient(150deg,#0E7473,#0a5453);color:#fff;font-size:17px;font-weight:900;display:grid;place-items:center}' +
    '.brand .bn{font-size:15.5px;font-weight:900}.brand .bl{font-size:10px;font-weight:600;color:#5F7476;line-height:1.55;margin-top:3px}' +
    '.docblock{text-align:right}.docblock .dt{font-size:9.5px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;color:#0E7473}.docblock .dn{font-family:monospace;font-size:19px;font-weight:800;margin-top:4px}' +
    '.docblock .dst{display:inline-flex;align-items:center;gap:6px;margin-top:7px;padding:4px 11px;border-radius:999px;background:rgba(14,116,115,.10);border:1px solid rgba(14,116,115,.35);color:#0E7473;font-size:9.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.docblock .dst i{width:5px;height:5px;border-radius:50%;background:#0E7473}' +
    '.docblock .dst.draftst{background:rgba(138,163,162,.14);border-color:rgba(138,163,162,.4);color:#5F7476}.docblock .dst.draftst i{background:#5F7476}.docblock .dst.paidst{background:rgba(21,128,61,.10);border-color:rgba(21,128,61,.35);color:#15803d}.docblock .dst.paidst i{background:#15803d}' +
    '.docblock .dp{font-size:9.5px;font-weight:600;color:#8AA3A2;margin-top:6px}' +
    '.meta{display:grid;grid-template-columns:repeat(4,1fr);border-bottom:1px solid #DCE7E5}.meta .cell{padding:10px 16px;border-left:1px solid #E7EFED}.meta .cell:first-child{border-left:none}.meta .cell:nth-child(n+5){border-top:1px solid #E7EFED}' +
    '.meta .l{font-size:8px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#8AA3A2}.meta .v{font-size:11px;font-weight:700;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.meta .v.mono{font-family:monospace;font-size:10.5px;color:#0E7473}' +
    '.vlines{padding:20px 46px 0}.vlines .lt{font-size:9.5px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#5F7476;margin-bottom:9px}' +
    '.sheet table{width:100%;border-collapse:collapse}.sheet thead th{background:#0E7473;color:#fff;font-size:8.5px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;padding:8px 12px;text-align:left}.sheet thead th.r{text-align:right}.sheet thead th:first-child{border-radius:6px 0 0 6px}.sheet thead th:last-child{border-radius:0 6px 6px 0}' +
    '.sheet tbody td{padding:9px 12px;border-bottom:1px solid #E7EFED;font-size:11px;color:#3E5658;vertical-align:top}.sheet tbody td.no{width:30px;color:#8AA3A2;font-weight:700}.sheet tbody td.num{text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:#13292C;white-space:nowrap}' +
    '.sheet tr.totals td{border-bottom:none;border-top:3px double #13292C;padding:10px 12px;background:#F0F5F5}.sheet tr.words td{padding:9px 12px;border-bottom:1px solid #DCE7E5;background:#fbfdfc}' +
    '.words-l{font-size:8px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#8AA3A2;margin-right:8px}.words-v{font-size:11px;font-weight:700;color:#3E5658;font-style:italic}.balnote{float:right;font-size:9.5px;font-weight:800;color:#0E7473;letter-spacing:.06em}' +
    '.paystrip{margin:16px 46px 0;border:1px solid #DCE7E5;border-radius:6px;overflow:hidden}.paystrip .nh{padding:7px 12px;background:#F0F5F5;border-bottom:1px solid #E7EFED;font-size:8px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#5F7476}.paystrip table td{border-bottom:1px solid #E7EFED}.paystrip table tr:last-child td{border-bottom:none}' +
    '.qpanels{display:grid;grid-template-columns:1.35fr 1fr;gap:18px;margin:16px 46px 0}.narr{border:1px solid #DCE7E5;border-radius:6px;overflow:hidden}.narr .nh{padding:7px 12px;background:#F0F5F5;border-bottom:1px solid #E7EFED;font-size:8px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#5F7476}.narr .nb{padding:10px 12px;font-size:10.5px;font-weight:500;color:#3E5658;line-height:1.6}' +
    '.tlist{padding:10px 12px;display:flex;flex-direction:column;gap:6px}.tlist div{font-size:9.5px;font-weight:600;color:#3E5658;line-height:1.5;padding-left:12px;position:relative}.tlist div::before{content:"";position:absolute;left:0;top:5px;width:5px;height:5px;border-radius:50%;background:#0E7473;opacity:.55}' +
    '.v-foot{margin-top:auto;padding:12px 46px;border-top:1px solid #DCE7E5;background:#fbfdfc;display:flex;justify-content:space-between;gap:16px;position:relative;z-index:1}.v-foot .fa{font-family:monospace;font-size:8.5px;font-weight:600;color:#8AA3A2}.v-foot .fb{font-size:8.5px;font-weight:600;color:#8AA3A2;text-align:center;flex:1}.v-foot .fc{font-size:9px;font-weight:800;color:#5F7476;letter-spacing:.08em;white-space:nowrap}' +
    '@page{size:A4;margin:10mm}@media print{.ptoolbar{display:none!important}body{background:#fff}.sheet-wrap{padding:0;display:block}.sheet{width:auto;min-height:277mm;border:none;box-shadow:none}}';
  function openPrintTab(id) {
    var iv = DATA[id];
    if (!iv) return false;
    var tr = rowOf(id);
    var ref = (tr && tr.querySelector('.ino') ? tr.querySelector('.ino').textContent : '') || iv.ref || '';
    var doc = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Tax Invoice ' + esc(ref) + '</title><style>' + PRINT_CSS + '</style></head><body>' +
      '<div class="ptoolbar"><span class="tb-title">Tax Invoice <span>' + esc(ref) + '</span></span><span class="tb-spacer"></span>' +
      '<button class="tb-btn" id="tPdf"><svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>Save as PDF</button>' +
      '<button class="tb-btn primary" id="tPrint"><svg viewBox="0 0 24 24"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>Print</button>' +
      '<button class="tb-btn" id="tClose">Close tab</button></div>' +
      '<div class="sheet-wrap"><div class="sheet">' + sheetBodyHTML(id) + '</div></div>' +
      '<scr' + 'ipt>document.getElementById("tPrint").onclick=function(){window.print()};document.getElementById("tPdf").onclick=function(){window.print()};document.getElementById("tClose").onclick=function(){window.close()};</scr' + 'ipt>' +
      '</body></html>';
    var w = window.open('', '_blank');
    if (w) {
      w.document.open(); w.document.write(doc); w.document.close();
      toast('🖨 Tax invoice ' + ref + ' opened in a new tab');
      return true;
    }
    var sheet = $('oSheet'), pv = $('printView');
    if (sheet && pv) {
      sheet.innerHTML = sheetBodyHTML(id);
      $('pvRef').textContent = ref;
      pv.classList.add('on'); pv.setAttribute('aria-hidden', 'false');
      document.body.classList.add('pv-on'); pv.scrollTop = 0;
      toast('Popup blocked — showing in-app preview instead');
      return false;
    }
    if (iv.urls && iv.urls.print) { window.open(iv.urls.print, '_blank'); return true; }
    return false;
  }
  function closePreview() {
    var pv = $('printView');
    if (!pv) return;
    pv.classList.remove('on'); pv.setAttribute('aria-hidden', 'true'); document.body.classList.remove('pv-on');
    if (pendingReload) window.location.reload();
  }
  function safePrint() {
    var pv = $('printView');
    if (!pv) return;
    document.body.classList.add('pv-on'); pv.classList.add('on');
    if (typeof window.print !== 'function') { toast('Print is unavailable in this environment — use Save as PDF'); return; }
    try { window.print(); } catch (e) { toast('Print is unavailable — use Save as PDF'); }
  }
  on('pvBack', 'click', closePreview);
  on('pvPrint', 'click', function () { toast('🖨 Print dialog opened — choose your printer'); safePrint(); });
  on('pvPdf', 'click', function () { toast('🧾 Print dialog opened — set destination to “Save as PDF”'); safePrint(); });

  /* ---------- view modal ---------- */
  function openView(id) {
    var iv = DATA[id];
    if (!iv) return;
    var st = iv.status;
    var days = iv.days || ['—', null, false];
    viewingId = id;
    $('vwRef').textContent = iv.ref;
    $('vwCustH').innerHTML = SVG_USER + esc(iv.cust);
    var pl = PILL[st] || PILL.sent;
    $('vwPill').className = 'pill ' + pl[0];
    $('vwPill').innerHTML = '<i></i>' + pl[1];
    $('vwMeta').innerHTML = (iv.meta || []).map(function (m) {
      return '<span class="mchip' + (m[1] ? ' ' + m[1] : '') + '">' + esc(m[0]) + '</span>';
    }).join('');
    $('vwTiles').innerHTML =
      '<div class="mtile"><div class="l">Total (' + CS + ')</div><div class="v">' + fmt(iv.total) + '</div></div>' +
      '<div class="mtile"><div class="l">Paid (' + CS + ')</div><div class="v">' + fmt(iv.paid) + '</div></div>' +
      '<div class="mtile hero"><div class="l">Balance due (' + CS + ')</div><div class="v">' + fmt(iv.balance) + '</div></div>' +
      '<div class="mtile' + (days[2] ? ' warn' : '') + '"><div class="l">' + esc(days[0]) + '</div><div class="v">' + (days[1] == null ? '—' : esc(days[1])) + '</div></div>';
    $('vwCust').textContent = iv.cust;
    $('vwContact').textContent = (iv.contact || '—') + ' · ' + (iv.phone || '—');
    $('vwEmail').textContent = iv.email || '—';
    $('vwAddr').textContent = iv.addr || '—';
    $('vwTerms').textContent = (iv.terms || '—') + ' · ' + dfmt(iv.due);
    $('vwSource').textContent = iv.source || '—';
    $('vwRefNo').textContent = iv.refNo || '—';
    $('vwBy').textContent = iv.prepared || '—';
    $('vwNotes').textContent = iv.notes || '—';
    $('vwLines').innerHTML = (iv.lines || []).map(function (l) {
      return '<tr><td>' + esc(l[0]) + '</td><td class="r">' + esc(l[1]) + '</td><td class="r">' + esc(l[2]) + '</td><td class="r">' + esc(l[3]) + '</td></tr>';
    }).join('');
    $('vwPays').innerHTML = (iv.payments && iv.payments.length)
      ? iv.payments.map(function (p) {
          return '<tr><td>' + esc(p.date) + '</td><td>' + esc(p.method) + '</td><td class="mono">' + esc(p.ref) + '</td><td class="r">' + esc(p.amount) + '</td></tr>';
        }).join('')
      : '<tr><td colspan="4" style="text-align:center;color:var(--faint);font-weight:600;padding:14px">No payments recorded yet</td></tr>';
    $('vwPayTot').textContent = fmt(iv.paid);
    $('vwBalDue').textContent = fmt(iv.balance);
    document.querySelectorAll('#vwTabs .tab').forEach(function (t, i) { t.classList.toggle('on', i === 0); });
    ['ov', 'li', 'py'].forEach(function (k, i) { $('pane-' + k).classList.toggle('on', i === 0); });
    $('vwFoot').innerHTML = (iv.foot || '') + CLOSE_BTN;
    $('viewScrim').classList.add('on');
  }
  $('vwTabs').addEventListener('click', function (e) {
    var t = e.target.closest('.tab');
    if (!t) return;
    document.querySelectorAll('#vwTabs .tab').forEach(function (x) { x.classList.toggle('on', x === t); });
    ['ov', 'li', 'py'].forEach(function (k) { $('pane-' + k).classList.toggle('on', k === t.dataset.tab); });
  });

  /* ---------- post / void / send ---------- */
  function postInvoice(id) {
    var iv = DATA[id];
    if (!iv) return;
    var ref = iv.ref;
    ask({
      tone: 'primary',
      title: 'Post ' + ref + '?',
      msg: 'Issues the invoice to the customer ledger and starts the payment clock (due ' + dfmt(iv.due) + ').',
      okLabel: 'Post invoice',
      onOk: function () {
        sendAction(iv.urls.post).then(function (b) {
          if (!b) return;
          pendingReload = true;
          showSuccess({
            id: id,
            title: 'Invoice posted',
            body: 'Invoice <span class="okref">' + esc(ref) + '</span> for <b>' + esc(iv.cust) + '</b> (' + CS + ' ' + fmt(b.total) + ') is now <b>Sent</b> and due ' + dfmt(b.due) + '.<br>Record payments as they arrive.',
            print: true
          });
        });
      }
    });
  }
  function voidInvoice(id) {
    var iv = DATA[id];
    if (!iv) return;
    var ref = iv.ref;
    if (iv.payments && iv.payments.length) { toast('Cannot void an invoice with payments — issue a credit note'); return; }
    ask({
      tone: 'danger',
      title: 'Void ' + ref + '?',
      msg: 'Voids the invoice permanently. Void documents are retained for audit and excluded from AR.',
      okLabel: 'Void invoice',
      onOk: function () {
        sendAction(iv.urls.void).then(function (b) {
          if (b) flashReload('⃠ ' + ref + ' voided');
        });
      }
    });
  }
  function sendInvoice(id) {
    var iv = DATA[id];
    if (!iv) return;
    var ref = iv.ref;
    if (iv.status === 'draft') { toast('Post the invoice before sending it'); return; }
    if (!emailOk(iv.email)) { toast('Provide a valid customer email to send'); return; }
    ask({
      tone: 'primary',
      title: 'Email ' + ref + ' to ' + iv.email + '?',
      msg: 'Sends the tax invoice PDF' + (num(iv.balance) <= 0.005 ? ' and receipt pack' : '') + ' to the customer and records the dispatch.',
      okLabel: 'Send now',
      onOk: function () {
        sendAction(iv.urls.send).then(function (b) {
          if (b) flashReload('📨 ' + ref + ' emailed to ' + (b.recipient || iv.email));
        });
      }
    });
  }
  function act(a, id) {
    var iv = DATA[id];
    if (!iv || !a) return;
    if (a === 'view') { openView(id); return; }
    if (a === 'pay') { openPay(id); return; }
    if (a === 'print') { openPrintTab(id); return; }
    if (a === 'edit') {
      if (iv.status !== 'draft') { toast('🔒 ' + iv.ref + ' is posted — only drafts can be edited'); return; }
      if ($('viewScrim').classList.contains('on')) $('viewScrim').classList.remove('on');
      window.location.href = iv.urls.edit;
      return;
    }
    if (a === 'post') { postInvoice(id); return; }
    if (a === 'void') { voidInvoice(id); return; }
    if (a === 'send') { sendInvoice(id); return; }
  }

  /* ---------- payments ---------- */
  function renderPyDetails() {
    var m = $('pyMethod').value;
    var wrap = $('pyDetails');
    if (m === 'Split') {
      $('pyDetailsTitle').style.display = 'none';
      wrap.innerHTML = '';
      $('pySplitWrap').style.display = '';
      if (!$('pySplitBody').rows.length) { $('pySplitBody').innerHTML = splitRow() + splitRow(); }
      updateSplitSum();
      return;
    }
    $('pyDetailsTitle').style.display = '';
    $('pyDetailsTitle').textContent = 'Method details — ' + m;
    $('pySplitWrap').style.display = 'none';
    var fs = PYFIELDS[m] || [];
    wrap.innerHTML = fs.map(function (f) {
      var ctl = f.t === 'sel'
        ? '<select class="sel" id="pyd_' + f.k + '">' + f.opts.map(function (o) { return '<option>' + o + '</option>'; }).join('') + '</select>'
        : f.t === 'date'
          ? '<input class="in" type="date" id="pyd_' + f.k + '">'
          : '<input class="in" id="pyd_' + f.k + '" placeholder="' + (f.req ? 'Required' : '—') + '">';
      return '<div class="fld"><label>' + f.l + (f.req ? ' <b>*</b>' : '') + '</label>' + ctl + '</div>';
    }).join('');
  }

  function legCellHTML(method, vals) {
    vals = vals || {};
    var defs = (PYFIELDS[method] || []).filter(function (f) { return f.req; });
    var h = '<div class="legcell">';
    defs.forEach(function (f) {
      h += '<input class="lin legdet" data-s="d" data-k="' + f.k + '" placeholder="' + esc(f.l) + ' *" value="' + esc(vals[f.k] || '') + '">';
    });
    h += '<input class="lin" data-s="r" value="' + esc(vals.ref || '') + '" placeholder="Leg reference">';
    return h + '</div>';
  }

  function splitRow(method, amount, ref, det) {
    var vals = { ref: ref || '' };
    Object.keys(det || {}).forEach(function (k) { if (det[k]) vals[k] = det[k]; });
    return '<tr><td><select class="lin" data-s="m">' + SPLIT_METHODS.map(function (m) { return '<option' + (m === method ? ' selected' : '') + '>' + m + '</option>'; }).join('') + '</select></td>' +
      '<td><input class="lin num" data-s="a" type="number" min="0" step="0.01" value="' + (amount == null ? '' : amount) + '"></td>' +
      '<td>' + legCellHTML(method, vals) + '</td>' +
      '<td><button class="xb" type="button" title="Remove leg">' + SVG_X + '</button></td></tr>';
  }

  function splitLegs() {
    var legs = [];
    Array.prototype.forEach.call($('pySplitBody').rows, function (r) {
      var det = {};
      Array.prototype.forEach.call(r.querySelectorAll('.legdet'), function (el) { det[el.dataset.k] = el.value.trim(); });
      legs.push({
        method: r.querySelector('[data-s="m"]').value,
        amount: num(r.querySelector('[data-s="a"]').value),
        ref: r.querySelector('[data-s="r"]').value.trim(),
        details: det
      });
    });
    return legs;
  }

  function updateSplitSum() {
    var amt = num($('pyAmount').value);
    var sum = splitLegs().reduce(function (s, l) { return s + l.amount; }, 0);
    var el = $('pySplitSum');
    var ok = Math.abs(sum - amt) < 0.005 && amt > 0;
    el.className = 'splitsum ' + (ok ? 'ok' : 'bad');
    el.textContent = 'Legs total ' + CS + ' ' + fmt(sum) + ' of ' + CS + ' ' + fmt(amt) + (ok ? ' ✓ tied' : ' — must equal payment amount');
  }

  function collectDetails(m) {
    var d = {};
    (PYFIELDS[m] || []).forEach(function (f) {
      var el = $('pyd_' + f.k);
      d[f.k] = el ? el.value.trim() : '';
    });
    return d;
  }

  function legMissing(l) {
    var miss = [];
    (PYFIELDS[l.method] || []).forEach(function (f) { if (f.req && !l.details[f.k]) miss.push(f.l.toLowerCase()); });
    return miss;
  }

  /* spec `chq` key → server cheque detail `number`; drops empty values */
  function mapDetails(d) {
    var o = {};
    Object.keys(d || {}).forEach(function (k) {
      if (!d[k]) return;
      o[k === 'chq' ? 'number' : k] = d[k];
    });
    return o;
  }

  function legBody(l) {
    var d = mapDetails(l.details);
    if (l.ref) d['reference'] = l.ref;
    return { method: METHOD_VALUE[l.method] || 'bank_transfer', amount: l.amount, details: d };
  }

  function openPay(id) {
    var iv = DATA[id];
    if (!iv) return;
    var bal = num(iv.balance);
    if (!((iv.status === 'sent' || iv.status === 'partial') && bal > 0)) { toast('No open balance to allocate'); return; }
    closeAllScrims();
    payId = id;
    $('pyRef').textContent = iv.ref;
    $('pyCustH').innerHTML = SVG_USER + esc(iv.cust);
    $('pyMeta').innerHTML = '<span class="mchip">Total ' + CS + ' ' + fmt(iv.total) + '</span><span class="mchip warn">Balance ' + CS + ' ' + fmt(bal) + '</span><span class="mchip">Due ' + dfmt(iv.due) + '</span>';
    $('pyDate').value = todayISO();
    $('pyAmount').value = bal.toFixed(2);
    $('pyAmount').max = bal;
    $('pyReceipt').value = NEXT_RC;
    $('pyNote').value = '';
    $('pyMethod').value = 'Bank transfer';
    $('pySplitBody').innerHTML = '';
    renderPyDetails();
    $('pyAfter').textContent = CS + ' 0.00';
    $('payScrim').classList.add('on');
  }

  $('pyMethod').addEventListener('change', renderPyDetails);
  $('pyAmount').addEventListener('input', function () {
    var iv = DATA[payId];
    if (!iv) return;
    var bal = num(iv.balance);
    var v = Math.max(0, Math.min(bal, num(this.value)));
    $('pyAfter').textContent = CS + ' ' + fmt(bal - v);
    if ($('pyMethod').value === 'Split') updateSplitSum();
  });
  $('pySplitAdd').addEventListener('click', function () {
    $('pySplitBody').insertAdjacentHTML('beforeend', splitRow());
    updateSplitSum();
  });
  $('pySplitBody').addEventListener('input', updateSplitSum);
  $('pySplitBody').addEventListener('change', function (e) {
    var sel = e.target.closest('[data-s="m"]');
    if (sel) {
      var row = sel.closest('tr');
      var refEl = row.querySelector('[data-s="r"]');
      var vals = { ref: refEl ? refEl.value : '' };
      Array.prototype.forEach.call(row.querySelectorAll('.legdet'), function (el) { if (el.value) vals[el.dataset.k] = el.value; });
      var cell = refEl ? refEl.closest('td') : null;
      if (cell) cell.innerHTML = legCellHTML(sel.value, vals);
    }
    updateSplitSum();
  });
  $('pySplitBody').addEventListener('click', function (e) {
    var x = e.target.closest('.xb');
    if (!x) return;
    if ($('pySplitBody').rows.length <= 2) { toast('A split payment needs at least two legs'); return; }
    x.closest('tr').remove();
    updateSplitSum();
  });

  $('pySave').addEventListener('click', function () {
    var iv = DATA[payId];
    if (!iv) return;
    var ref = iv.ref;
    var bal = num(iv.balance);
    var amt = num($('pyAmount').value);
    if (!(amt > 0)) { $('pyAmount').classList.add('bad'); toast('Enter a payment amount greater than zero'); return; }
    if (amt > bal) { $('pyAmount').classList.add('bad'); toast('Payment cannot exceed the open balance (' + CS + ' ' + fmt(bal) + ')'); return; }
    $('pyAmount').classList.remove('bad');
    var m = $('pyMethod').value;
    var details = {}, splits = null, methodLabel = m;
    if (m === 'Split') {
      splits = splitLegs().filter(function (l) { return l.amount > 0 || l.ref || Object.keys(l.details).some(function (k) { return l.details[k]; }); });
      if (splits.length < 2) { toast('A split payment needs at least two legs with amounts'); return; }
      var sum = splits.reduce(function (s, l) { return s + l.amount; }, 0);
      for (var i = 0; i < splits.length; i++) {
        if (!(splits[i].amount > 0)) { toast('Every split leg needs an amount greater than zero'); return; }
        var miss = legMissing(splits[i]);
        if (miss.length) { toast('Complete: leg ' + (i + 1) + ' — ' + miss.join(', ')); return; }
      }
      if (Math.abs(sum - amt) > 0.005) { toast('Split legs (' + CS + ' ' + fmt(sum) + ') must equal the payment amount (' + CS + ' ' + fmt(amt) + ')'); return; }
      methodLabel = 'Split · ' + splits.map(function (l) { return l.method; }).join(' + ');
    } else {
      details = collectDetails(m);
      var missing = [];
      (PYFIELDS[m] || []).forEach(function (f) { if (f.req && !details[f.k]) missing.push(f.l.toLowerCase()); });
      if (missing.length) { toast('Complete: ' + missing.join(', ')); return; }
    }
    var p = { paid_on: $('pyDate').value || todayISO(), ref: $('pyReceipt').value, amount: amt, note: $('pyNote').value.trim(), details: details, splits: splits };
    var settled = Number((bal - amt).toFixed(2)) <= 0;
    var rowsHtml = cbRow('Receipt №', p.ref, 'mono') + cbRow('Payment date', dfmt(p.paid_on)) + cbRow('Method', methodLabel) + cbRow('Payment amount', CS + ' ' + fmt(amt), 'green') + cbRow('Balance before', CS + ' ' + fmt(bal)) + cbRow('Balance after', CS + ' ' + fmt(bal - amt), settled ? 'green' : '');
    if (splits) {
      rowsHtml += splits.map(function (l) {
        var extra = [];
        if (l.ref) extra.push(l.ref);
        Object.keys(l.details).forEach(function (k) { if (l.details[k]) extra.push(l.details[k]); });
        return cbRow('Leg — ' + l.method, CS + ' ' + fmt(l.amount) + (extra.length ? ' · ' + extra.join(' · ') : ''), 'mono');
      }).join('');
    } else {
      (PYFIELDS[m] || []).forEach(function (f) { if (details[f.k]) rowsHtml += cbRow(f.l, details[f.k], f.k === 'txref' || f.k === 'chq' ? 'mono' : ''); });
    }
    if (p.note) rowsHtml += cbRow('Note', p.note);
    var body = {
      paid_on: p.paid_on,
      amount: p.amount,
      method: METHOD_VALUE[m] || 'bank_transfer',
      method_label: methodLabel,
      note: p.note
    };
    if (m === 'Split') body.splits = splits.map(legBody);
    else body.details = mapDetails(details);
    ask({
      tone: 'ok',
      title: 'Confirm payment receipt',
      okLabel: 'Confirm payment',
      msg: 'Post this payment against <span class="okref">' + esc(ref) + '</span> (' + esc(iv.cust) + ')?<div class="cb-box">' + rowsHtml + '</div>' +
        (settled ? '<div style="margin-top:10px;font-weight:700;color:var(--green)">This payment settles the invoice in full.</div>' : '<div style="margin-top:10px;font-weight:600;color:var(--amber-2)">Partial payment — invoice moves to Partially paid.</div>'),
      onOk: function () { applyPayment(payId, body); }
    });
  });

  function applyPayment(id, body) {
    var iv = DATA[id];
    if (!iv) return;
    var ref = iv.ref;
    sendAction(iv.urls.pay, body).then(function (b) {
      if (!b) return;
      $('payScrim').classList.remove('on');
      var settled = b.status === 'paid' || num(b.balance) <= 0.005;
      if (settled) {
        pendingReload = true;
        showSuccess({
          id: id,
          title: 'Invoice settled in full',
          body: 'Invoice <span class="okref">' + esc(ref) + '</span> for <b>' + esc(iv.cust) + '</b> is fully paid via ' + esc(b.receipt) + ' (' + esc(String(body.method_label).toLowerCase()) + ', ' + CS + ' ' + fmt(b.amount) + ').<br>AR balance is now <b>' + CS + ' 0.00</b>.',
          print: true
        });
      } else {
        flashReload('✓ ' + ref + ' payment recorded — balance ' + CS + ' ' + fmt(b.balance));
      }
    });
  }

  /* ---------- delegation (register rows / queue rows / workspace footer / rail) ---------- */
  function on(id, evt, fn) { var el = $(id); if (el) el.addEventListener(evt, fn); }
  function rowDelegate(e) {
    var b = e.target.closest('[data-act]');
    if (b) {
      if (b.disabled) return;
      var tr0 = b.closest('tr.irow');
      if (tr0) act(b.dataset.act, tr0.dataset.id);
      return;
    }
    var tr = e.target.closest('tr.irow');
    if (tr) act('view', tr.dataset.id);
  }
  on('iBody', 'click', rowDelegate);
  on('qTBody', 'click', rowDelegate);
  on('vwFoot', 'click', function (e) {
    var b = e.target.closest('[data-vact]');
    if (!b || b.disabled) return;
    act(b.dataset.vact, viewingId);
  });
  on('queue', 'click', function (e) {
    var b = e.target.closest('[data-open]');
    if (b) openView(b.dataset.open);
  });

  /* ---------- dismissal (spec R2: Close / ✕ / Esc only) ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-close]');
    if (!t) return;
    var scrim = $(t.dataset.close);
    if (!scrim) return;
    scrim.classList.remove('on');
    if (t.dataset.close === 'okScrim' && pendingReload) window.location.reload();
  });
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
      var pvv = $('printView');
      if (pvv && pvv.classList.contains('on')) { e.preventDefault(); safePrint(); }
      return;
    }
    if (e.key !== 'Escape') return;
    var pv = $('printView');
    if (pv && pv.classList.contains('on')) { closePreview(); return; }
    if ($('cfScrim').classList.contains('on')) { $('cfScrim').classList.remove('on'); cbFn = null; return; }
    if ($('okScrim').classList.contains('on')) { $('okScrim').classList.remove('on'); if (pendingReload) window.location.reload(); return; }
    var qs = $('quoteScrim');
    if (qs && qs.classList.contains('on')) { qs.classList.remove('on'); return; }
    if ($('payScrim').classList.contains('on')) { $('payScrim').classList.remove('on'); return; }
    if ($('viewScrim').classList.contains('on')) { $('viewScrim').classList.remove('on'); }
  });

  /* ---------- period / search / sort (submit-based; server renders results) ---------- */
  var bar = $('periodBar');
  if ($('btnPeriod') && bar) {
    $('btnPeriod').classList.toggle('period-on', bar.style.display !== 'none');
    $('btnPeriod').addEventListener('click', function () {
      var show = bar.style.display === 'none';
      bar.style.display = show ? 'flex' : 'none';
      this.classList.toggle('period-on', show);
    });
  }
  function checkRange() {
    var f = $('pFrom').value, t = $('pTo').value;
    if (f && t && f > t) {
      $('pFrom').classList.add('bad');
      $('pTo').classList.add('bad');
      toast('Period "from" must be on/before "to"');
      return false;
    }
    $('pFrom').classList.remove('bad');
    $('pTo').classList.remove('bad');
    return true;
  }
  on('pPreset', 'change', function () {
    var custom = this.value === 'custom';
    $('pFrom').disabled = !custom;
    $('pTo').disabled = !custom;
    submitForm();
  });
  on('pFrom', 'change', function () { if (checkRange()) submitForm(); });
  on('pTo', 'change', function () { if (checkRange()) submitForm(); });
  on('pClear', 'click', function () {
    $('pPreset').value = '';
    $('pFrom').value = '';
    $('pTo').value = '';
    $('pFrom').disabled = true;
    $('pTo').disabled = true;
    $('pFrom').classList.remove('bad');
    $('pTo').classList.remove('bad');
    submitForm();
  });
  var qT = null;
  on('q', 'input', function () {
    clearTimeout(qT);
    qT = setTimeout(submitForm, 350);
  });
  on('q', 'keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      clearTimeout(qT);
      submitForm();
    }
  });
  on('sort', 'change', submitForm);

  /* ---------- reports rail (buttons only — the first two are real links) ---------- */
  document.querySelectorAll('button[data-rep]').forEach(function (btn) {
    btn.addEventListener('click', function () { toast('📊 ' + btn.textContent.trim() + ' report queued'); });
  });

  /* ---------- create / edit form (spec js.txt L717–932 — posts the real #invoice-form) ---------- */
  if ($('createView')) {
    var CUSTOMERS = window.INV_CUSTOMERS || [];
    var QUOTES = window.INV_QUOTES || [];
    var TERMS = { due_on_receipt: 'Due on receipt', net_15: 'Net 15', net_30: 'Net 30', net_60: 'Net 60', net_90: 'Net 90', custom: 'Custom' };
    var TERMD = { due_on_receipt: 0, net_15: 15, net_30: 30, net_60: 60, net_90: 90, custom: 30 };
    var matchedCode = null;
    var lineCount = 0;
    var lineIndex = Number(window.INV_LINE_START) || 0;
    var formEl = $('invoice-form');
    var editId = !!(formEl && formEl.querySelector('input[name="_method"]'));
    var helperSubmit = false;

    /* terms — server keys; unknown/custom keys resolve their days from the visible label */
    function termsHas(key) { return Object.prototype.hasOwnProperty.call(TERMD, key); }
    function termsLabel(key) {
      if (TERMS[key]) return TERMS[key];
      var sel = $('ncTerms');
      if (sel) {
        for (var i = 0; i < sel.options.length; i++) {
          if (sel.options[i].value === key) return sel.options[i].textContent.trim();
        }
      }
      return key;
    }
    function termsDays(key) {
      if (termsHas(key)) return TERMD[key];
      return Number((String(termsLabel(key)).match(/\d+/) || [30])[0]);
    }
    /* TZ-safe day math (spec's toISOString().slice(0,10) shifts a day on non-UTC hosts) */
    function addDaysISO(iso, days) {
      var p = String(iso || '').split('-');
      if (p.length !== 3) return iso || '';
      var d = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
      if (isNaN(d.getTime())) return iso;
      d.setDate(d.getDate() + (Number(days) || 0));
      return localISO(d.getFullYear(), d.getMonth(), d.getDate());
    }
    function setTermsKey(key) {
      var sel = $('ncTerms');
      var k = key && termsHas(key) ? key : 'net_30';
      if (!sel.querySelector('option[value="' + k + '"]')) {
        var o = document.createElement('option');
        o.value = k;
        o.textContent = termsLabel(k);
        sel.appendChild(o);
      }
      sel.value = k;
    }
    function syncDue() { $('ncDue').value = addDaysISO($('ncDate').value || todayISO(), termsDays($('ncTerms').value)); }
    function setLock(lock) {
      ['ncContact', 'ncEmail', 'ncPhone', 'ncAddress'].forEach(function (id) {
        var el = $(id);
        if (!el) return;
        el.readOnly = lock;
        el.classList.toggle('ro', lock);
      });
    }
    function fillCustDatalist() {
      var dl = $('custList');
      if (!dl) return;
      dl.innerHTML = CUSTOMERS.map(function (c) {
        return '<option value="' + esc(c.name) + '">' + esc(c.email || '#' + c.id) + '</option>';
      }).join('');
    }
    function updateBtns() {
      var walkin = $('ncMode').value === 'walkin';
      var nameOk = walkin ? $('ncName').value.trim() !== '' : !!matchedCode;
      var dueOk = $('ncDue').value !== '' && $('ncDate').value !== '' && $('ncDue').value >= $('ncDate').value;
      var linesOk = lineCount > 0;
      var missing = [];
      if (!nameOk) missing.push(walkin ? 'customer name' : 'customer (pick from registry)');
      if (!dueOk) missing.push('valid due date');
      if (!linesOk) missing.push('1 complete line (code·name·qty·price)');
      var ready = missing.length === 0;
      $('ncPost').disabled = !ready;
      $('ncDraft').disabled = !(nameOk && linesOk);
      var g = $('nsGate');
      g.textContent = ready ? 'Ready ✓' : 'Missing: ' + missing.join(', ');
      g.className = ready ? 'gate-ok' : 'gate-no';
      $('ncPost').title = ready ? 'Post this invoice to the customer ledger' : 'Still required — ' + missing.join('; ');
    }
    function ncLineRow(code, name, desc, q, p) {
      var n = 'lines[' + (lineIndex++) + ']';
      return '<tr><td><input class="lin" type="text" data-f="code" name="' + n + '[item_code]" value="' + esc(code || '') + '" placeholder="Code" autocomplete="off"></td>' +
        '<td><input class="lin" type="text" data-f="name" name="' + n + '[item_name]" value="' + esc(name || '') + '" placeholder="Item name" autocomplete="off"></td>' +
        '<td><input class="lin" type="text" data-f="desc" name="' + n + '[description]" value="' + esc(desc || '') + '" placeholder="Optional" autocomplete="off"></td>' +
        '<td><input class="lin num" type="number" data-f="q" name="' + n + '[quantity]" min="0" step="1" value="' + (q == null ? 1 : q) + '"></td>' +
        '<td><input class="lin num" type="number" data-f="p" name="' + n + '[unit_price]" min="0" step="0.01" value="' + (p || 0) + '"></td>' +
        '<td class="amtcell ramt">0.00</td>' +
        '<td><button class="xb" type="button" title="Remove line">' + SVG_X + '</button></td></tr>';
    }
    function lineValid(r, amt) {
      return r.querySelector('[data-f="code"]').value.trim() !== '' && r.querySelector('[data-f="name"]').value.trim() !== '' && num(r.querySelector('[data-f="q"]').value) > 0 && num(r.querySelector('[data-f="p"]').value) > 0 && amt > 0;
    }
    function ncRecompute() {
      var sub = 0, n = 0;
      Array.prototype.forEach.call($('ncLines').rows, function (r) {
        var q = num(r.querySelector('[data-f="q"]').value), p = num(r.querySelector('[data-f="p"]').value);
        var amt = q * p; sub += amt;
        var ok = lineValid(r, amt); if (ok) n++;
        r.querySelector('.ramt').innerHTML = fmt(amt) + (ok ? '<span class="lok">✓</span>' : '');
      });
      lineCount = n;
      var tax = sub * 0.165;
      $('nsSub').textContent = fmt(sub); $('nsTax').textContent = fmt(tax); $('nsGrand').textContent = CS + ' ' + fmt(sub + tax);
      $('nsLines').textContent = n ? n + ' complete line' + (n === 1 ? '' : 's') : 'No complete lines yet';
      updateBtns();
      return { sub: sub, tax: tax, total: sub + tax };
    }
    function ncSummary() {
      var walkin = $('ncMode').value === 'walkin';
      $('nsCust').textContent = walkin ? ($('ncName').value.trim() || '—') : ($('ncSearch').value.trim() || '—');
      $('nsDate').textContent = dfmt($('ncDate').value);
      $('nsDue').textContent = dfmt($('ncDue').value);
      updateBtns();
    }
    function collectLines() {
      var lines = [], sub = 0;
      Array.prototype.forEach.call($('ncLines').rows, function (r) {
        var code = r.querySelector('[data-f="code"]').value.trim();
        var nm = r.querySelector('[data-f="name"]').value.trim();
        var ds = r.querySelector('[data-f="desc"]').value.trim();
        var q = num(r.querySelector('[data-f="q"]').value), p = num(r.querySelector('[data-f="p"]').value);
        if (code && nm && q > 0 && p > 0) { lines.push([code + ' — ' + nm + (ds ? ' (' + ds + ')' : ''), q, fmt(p), fmt(q * p)]); sub += q * p; }
      });
      return { lines: lines, sub: sub };
    }
    /* server rules: lines.*.quantity required min:0.01, lines.*.unit_price required min:0 */
    function rowsContract() {
      var rows = $('ncLines').rows;
      for (var i = 0; i < rows.length; i++) {
        var qEl = rows[i].querySelector('[data-f="q"]');
        var pEl = rows[i].querySelector('[data-f="p"]');
        qEl.classList.remove('bad');
        pEl.classList.remove('bad');
        var qv = String(qEl.value).trim(), pv = String(pEl.value).trim();
        if (qv === '' || isNaN(Number(qv)) || Number(qv) < 0.01) {
          qEl.classList.add('bad'); qEl.focus();
          toast('Line ' + (i + 1) + ' — quantity must be at least 0.01');
          return false;
        }
        if (pv === '') {
          pEl.classList.add('bad'); pEl.focus();
          toast('Line ' + (i + 1) + ' — unit price is required');
          return false;
        }
        if (isNaN(Number(pv)) || Number(pv) < 0) {
          pEl.classList.add('bad'); pEl.focus();
          toast('Line ' + (i + 1) + ' — unit price must be 0 or more');
          return false;
        }
      }
      return true;
    }
    function validateInvoice(post) {
      var walkin = $('ncMode').value === 'walkin';
      var name = walkin ? $('ncName').value.trim() : $('ncSearch').value.trim();
      if (!name) { (walkin ? $('ncName') : $('ncSearch')).classList.add('bad'); toast(walkin ? 'Customer name is required' : 'Select a customer from the registry'); return null; }
      (walkin ? $('ncName') : $('ncSearch')).classList.remove('bad');
      if (!walkin && !matchedCode) { $('ncSearch').classList.add('bad'); toast('Pick a matching customer from the list'); return null; }
      $('ncSearch').classList.remove('bad');
      var c = collectLines();
      if (!c.lines.length) { toast('Add at least one complete line (code, item name, qty, unit price)'); return null; }
      if (!$('ncDue').value || $('ncDue').value < $('ncDate').value) { $('ncDue').classList.add('bad'); toast('Due date must be on/after the issue date'); return null; }
      $('ncDue').classList.remove('bad');
      if (!rowsContract()) return null;
      return {
        walkin: walkin, name: name,
        contact: $('ncContact').value.trim(), email: $('ncEmail').value.trim(), phone: $('ncPhone').value.trim(), address: $('ncAddress').value.trim(),
        terms: $('ncTerms').value, source: $('ncSource').value.trim(), refNo: $('ncRef').value.trim(),
        date: $('ncDate').value, due: $('ncDue').value, cc: $('ncCC').value,
        lines: c.lines, sub: c.sub, tax: c.sub * 0.165, total: c.sub * 1.165,
        notesC: $('ncNotesC').value.trim(), notesI: $('ncNotesI').value.trim(), post: post
      };
    }
    /* posts the real form: sync the hidden mode, re-enable the clicked submitter, requestSubmit() */
    function submitInvoiceForm(btn, mode) {
      var modeEl = formEl && formEl.querySelector('input[name="mode"][type="hidden"]');
      if (modeEl) modeEl.value = mode;
      btn.disabled = false;
      helperSubmit = true;
      if (formEl.requestSubmit) {
        try { formEl.requestSubmit(btn); return; } catch (e) { /* fall through */ }
      }
      helperSubmit = false;
      formEl.submit();
    }
    function renderQuoteList() {
      var term = ($('qqSearch').value || '').trim().toLowerCase();
      var avail = QUOTES.filter(function (q) { return !q.invoiced; });
      if (term) {
        avail = avail.filter(function (q) {
          var hay = (q.ref + ' ' + q.cust + ' ' + q.status + ' ' + q.lines.map(function (l) { return l.code + ' ' + l.name + ' ' + l.desc; }).join(' ')).toLowerCase();
          return hay.indexOf(term) > -1;
        });
      }
      $('quoteList').innerHTML = avail.length ? avail.map(function (q) {
        return '<div class="qcard"><div style="min-width:0"><span class="qr">' + esc(q.ref) + '</span> <span class="pill ' + (q.status === 'Accepted' ? 'pd' : 'se') + '" style="margin-left:6px"><i></i>' + esc(q.status) + '</span>' +
          '<div class="qc">' + esc(q.cust) + '</div>' +
          '<div class="qs">' + CS + ' ' + fmt(q.total) + ' · ' + q.lines.length + ' line' + (q.lines.length === 1 ? '' : 's') + ' · valid until ' + dfmt(q.valid) + '</div></div>' +
          '<button class="btn btn-cta btn-sm" data-qref="' + esc(q.ref) + '">Use</button></div>';
      }).join('') : '<div class="empty" style="padding:26px 10px"><div class="t">' + (term ? 'No quotations match &quot;' + esc(term) + '&quot;' : 'No uninvoiced quotations') + '</div><div class="s">' + (term ? 'Clear the search to see all uninvoiced quotations.' : 'Accepted or sent quotations appear here for one-click conversion.') + '</div></div>';
    }
    function openQuotePicker() { $('qqSearch').value = ''; renderQuoteList(); $('quoteScrim').classList.add('on'); }
    function loadQuotation(q) {
      if (editId) return;
      var reg = CUSTOMERS.filter(function (c) { return c.name === q.cust; })[0];
      if (reg) {
        $('ncMode').value = 'existing';
        $('ncMode').dispatchEvent(new Event('change'));
        $('ncSearch').value = q.cust;
        $('ncCustomerId').value = reg.id;
        matchedCode = reg.id;
        setLock(true);
        $('ncContact').value = q.cust;
        $('ncEmail').value = q.email || '';
        $('ncPhone').value = q.phone || '';
        $('ncAddress').value = q.address || '';
      } else {
        $('ncMode').value = 'walkin';
        $('ncMode').dispatchEvent(new Event('change'));
        $('ncName').value = q.cust;
        $('ncContact').value = q.cust;
        $('ncEmail').value = q.email || '';
        $('ncPhone').value = q.phone || '';
        $('ncAddress').value = q.address || '';
      }
      setTermsKey(q.terms);
      $('ncSource').value = q.ref;
      $('ncSourceQid').value = q.id;
      $('ncNotesC').value = q.notes || '';
      $('ncDate').value = todayISO();
      syncDue();
      lineIndex = 0;
      $('ncLines').innerHTML = (q.lines.length ? q.lines.map(function (l) { return ncLineRow(l.code, l.name, l.desc, l.qty, l.price); }) : [ncLineRow()]).join('');
      ncRecompute();
      ncSummary();
      toast('⇄ ' + q.ref + ' loaded — customer, terms and ' + q.lines.length + ' line' + (q.lines.length === 1 ? '' : 's') + ' prefilled');
    }

    on('ncMode', 'change', function () {
      var w = this.value === 'walkin';
      $('ncSearchWrap').style.display = w ? 'none' : '';
      $('ncNameWrap').style.display = w ? '' : 'none';
      $('ncSaveNote').classList.toggle('on', w);
      if (w) { setLock(false); matchedCode = null; $('ncCustomerId').value = ''; }
      else { $('ncName').value = ''; setLock(!!matchedCode); }
      ncSummary();
    });
    on('ncSearch', 'input', function () {
      var v = this.value.trim().toLowerCase();
      matchedCode = null;
      $('ncCustomerId').value = '';
      if (v) {
        var c = CUSTOMERS.filter(function (x) { return x.name.toLowerCase().indexOf(v) > -1; })[0];
        if (c) {
          matchedCode = c.id;
          $('ncCustomerId').value = c.id;
          $('ncContact').value = c.contact || c.name;
          $('ncEmail').value = c.email || '';
          $('ncPhone').value = c.phone || '';
          $('ncAddress').value = c.address || '';
        }
      }
      setLock(!!matchedCode && $('ncMode').value === 'existing');
      ncSummary();
    });
    ['ncName', 'ncContact', 'ncAddress'].forEach(function (id) { on(id, 'input', ncSummary); });
    on('ncDate', 'change', function () { syncDue(); ncSummary(); });
    on('ncDue', 'change', ncSummary);
    on('ncTerms', 'change', function () { syncDue(); ncSummary(); });
    on('ncSource', 'input', function () { $('ncSourceQid').value = ''; });
    on('ncAdd', 'click', function () { $('ncLines').insertAdjacentHTML('beforeend', ncLineRow()); ncRecompute(); });
    on('ncLines', 'input', function (e) { if (e.target.classList.contains('lin')) ncRecompute(); });
    on('ncLines', 'click', function (e) {
      var x = e.target.closest('.xb'); if (!x) return;
      if ($('ncLines').rows.length <= 1) { toast('An invoice needs at least one line'); return; }
      x.closest('tr').remove(); ncRecompute();
    });
    on('ncPickQ', 'click', openQuotePicker);
    on('ncFromQ', 'click', openQuotePicker);
    on('qqSearch', 'input', renderQuoteList);
    on('quoteList', 'click', function (e) {
      var b = e.target.closest('[data-qref]'); if (!b) return;
      var q = QUOTES.filter(function (x) { return x.ref === b.dataset.qref && !x.invoiced; })[0];
      if (!q) return;
      loadQuotation(q);
      $('quoteScrim').classList.remove('on');
    });
    on('ncDraft', 'click', function (e) {
      e.preventDefault();
      if (!validateInvoice(false)) return;
      submitInvoiceForm(this, 'draft');
    });
    on('ncPost', 'click', function (e) {
      e.preventDefault();
      var d = validateInvoice(true);
      if (!d) return;
      ask({
        tone: 'primary',
        title: 'Post invoice to the ledger?',
        okLabel: 'Post invoice',
        msg: 'Review the invoice before posting — posting issues it to the customer ledger and starts the payment clock.' +
          '<div class="cb-box">' +
          cbRow('Customer', esc(d.name)) +
          cbRow('Issue date', dfmt(d.date)) +
          cbRow('Due date', dfmt(d.due) + ' · ' + esc(termsLabel(d.terms))) +
          cbRow('Lines', d.lines.length + ' complete line' + (d.lines.length === 1 ? '' : 's')) +
          cbRow('Subtotal', CS + ' ' + fmt(d.sub)) +
          cbRow('Tax (16.5%)', CS + ' ' + fmt(d.tax)) +
          cbRow('Invoice total', CS + ' ' + fmt(d.total), 'green') +
          (d.source ? cbRow('Source', esc(d.source), 'mono') : '') +
          cbRow('Email on post', emailOk(d.email) ? esc(d.email) : '— not sent (no valid email)') +
          '</div>',
        onOk: function () { submitInvoiceForm($('ncPost'), 'post'); }
      });
    });
    if (formEl) {
      formEl.addEventListener('submit', function (e) {
        if (helperSubmit) { helperSubmit = false; return; }
        if (e.submitter === $('ncPost')) { e.preventDefault(); return; } // posting always goes through the confirm box
        if (!validateInvoice(false)) e.preventDefault(); // Enter/implicit submission → draft gate
      });
    }

    /* boot — server-rendered state; never overwrite dates, terms or existing lines */
    fillCustDatalist();
    var startId = Number($('ncCustomerId') ? $('ncCustomerId').value : '');
    if (!isNaN(startId) && startId > 0) matchedCode = startId;
    if ($('ncMode').value === 'walkin') { matchedCode = null; $('ncCustomerId').value = ''; }
    setLock(!!matchedCode && $('ncMode').value === 'existing');
    if (!$('ncLines').rows.length) { lineIndex = 0; $('ncLines').innerHTML = ncLineRow(); }
    ncRecompute();
    ncSummary();
  }

  /* ---------- init (server outcome first, then flash toast + workspace reopen replay) ---------- */
  var flash = null, openIntent = null;
  var OUTCOME = window.INV_OUTCOME || null;
  window.INV_OUTCOME = null;
  if (OUTCOME) {
    try { sessionStorage.removeItem('invFlash'); sessionStorage.removeItem('invOpenView'); } catch (e) { /* private mode */ }
  } else {
    try { flash = sessionStorage.getItem('invFlash'); sessionStorage.removeItem('invFlash'); } catch (e) { /* private mode */ }
    try { openIntent = sessionStorage.getItem('invOpenView'); sessionStorage.removeItem('invOpenView'); } catch (e) { /* private mode */ }
    if (flash) toast(flash);
    if (openIntent && DATA[openIntent]) openView(openIntent);
  }
  if (OUTCOME && OUTCOME.mode === 'draft') {
    toast(OUTCOME.msg || ('✓ ' + (OUTCOME.ref || '') + ' saved as draft · ' + (OUTCOME.cust || '')));
  } else if (OUTCOME && OUTCOME.mode === 'post') {
    var obody = 'Invoice <span class="okref">' + esc(OUTCOME.ref) + '</span> for <b>' + esc(OUTCOME.cust) + '</b> (' + CS + ' ' + fmt(OUTCOME.total) + ') is now <b>Sent</b> and due ' + dfmt(OUTCOME.due) + '.';
    if (OUTCOME.source) obody += '<br>Converted from <span class="okref">' + esc(OUTCOME.source) + '</span>.';
    if (OUTCOME.emailed && emailOk(OUTCOME.emailed)) obody += '<br>Acknowledgement emailed to <b>' + esc(OUTCOME.emailed) + '</b>.';
    showSuccess({ id: OUTCOME.id, title: 'Invoice posted', body: obody, print: !!OUTCOME.printUrl, printUrl: OUTCOME.printUrl || null });
  }
})();
