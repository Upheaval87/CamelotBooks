@extends('layouts.app')
@section('content')
<body>

<nav class="topnav">
  <div class="topnav-brand">
    <div class="topnav-logo">L</div>
    <span class="topnav-app">CamelotBooks</span>
    <span class="topnav-sep">|</span>
    <span class="topnav-org"><strong>Acme Corporation</strong> &nbsp;·&nbsp; Headquarters</span>
  </div>
  <div class="topnav-center">
    <a class="topnav-pill" href="#"><i class="fa-solid fa-list-check"></i> My Tasks <span class="badge-count">3</span></a>
    <a class="topnav-pill" href="#"><i class="fa-solid fa-star"></i> Favourites <span class="badge-count">16</span></a>
    <div class="topnav-search"><i class="fa-solid fa-magnifying-glass"></i> Search vendors, bills… <kbd>Ctrl K</kbd></div>
  </div>
  <div class="topnav-right">
    <button class="topnav-icon-btn" data-tip="Notifications"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
    <button class="topnav-icon-btn" data-tip="Help"><i class="fa-solid fa-circle-question"></i></button>
    <div class="topnav-user">
      <div class="topnav-avatar">ES</div>
      <div><div class="topnav-user-name">Elvis Seyama</div><div class="topnav-user-role">SYSTEM_ADMIN</div></div>
    </div>
  </div>
</nav>

<nav class="modulenav">
  <a class="module-item" href="#"><i class="fa-solid fa-cart-shopping"></i> Sales <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item active" href="#"><i class="fa-solid fa-truck-field"></i> Purchasing <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-cubes"></i> Inventory <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-building-columns"></i> Banking <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-book-open"></i> Accounting <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-building"></i> Assets <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-users"></i> Payroll <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-chart-bar"></i> Reports <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-file-invoice-dollar"></i> Taxation <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-cash-register"></i> POS <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-chart-line"></i> Analytics <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-gear"></i> System <i class="fa-solid fa-chevron-down chevron"></i></a>
</nav>

<aside class="sidebar">
  <a class="sidebar-item" data-tip="Dashboard" href="#"><i class="fa-solid fa-gauge-high"></i></a>
  <a class="sidebar-item active" data-tip="Vendors" href="#"><i class="fa-solid fa-handshake"></i></a>
  <a class="sidebar-item" data-tip="Purchase Orders" href="#"><i class="fa-solid fa-file-lines"></i></a>
  <a class="sidebar-item" data-tip="Bills" href="#"><i class="fa-solid fa-receipt"></i></a>
  <a class="sidebar-item" data-tip="Payments" href="#"><i class="fa-solid fa-credit-card"></i></a>
  <div class="sidebar-divider"></div>
  <a class="sidebar-item" data-tip="Inventory" href="#"><i class="fa-solid fa-boxes-stacked"></i></a>
  <a class="sidebar-item" data-tip="Analytics" href="#"><i class="fa-solid fa-chart-pie"></i></a>
  <a class="sidebar-item" data-tip="Reports" href="#"><i class="fa-solid fa-chart-column"></i></a>
  <div class="sidebar-spacer"></div>
  <a class="sidebar-item" data-tip="Settings" href="#"><i class="fa-solid fa-sliders"></i></a>
</aside>

<main class="main" style="margin-top:0 !important;margin-left:0 !important;padding:20px 24px 40px !important;max-width:100% !important;width:100% !important;">

  <div class="breadcrumb anim-1">
    <a href="#">Vendor Centre</a><i class="fa-solid fa-chevron-right sep"></i>
    <a href="#">Vendors</a><i class="fa-solid fa-chevron-right sep"></i>
    <span class="current">Gracian Seyama</span>
  </div>

  <div class="page-header anim-1">
    <div>
      <h1 class="page-title">Vendor Detail — Gracian Seyama</h1>
      <div class="page-subtitle">
        <span>Since Aug 2026</span><span class="dot"></span>
        <span class="status-badge-inline"><span class="dot"></span> Inactive</span><span class="dot"></span>
        <span>Last updated 2 hours ago</span>
      </div>
    </div>
    <div class="page-header-actions">
      <button class="btn-icon" data-tip="Print"><i class="fa-solid fa-print"></i></button>
      <button class="btn-icon" data-tip="Export PDF"><i class="fa-solid fa-file-export"></i></button>
      <button class="btn btn-ghost"><i class="fa-solid fa-ellipsis-vertical"></i> More</button>
      <button class="btn btn-deep" data-open-modal="modal-edit-vendor"><i class="fa-solid fa-pen"></i> Edit Vendor</button>
    </div>
  </div>

  <div class="vendor-hero glass anim-2">
    <div class="vendor-hero-inner">
      <div class="vendor-avatar-wrap">
        <div class="vendor-avatar">GS</div>
        <div class="vendor-avatar-status"></div>
      </div>
      <div class="vendor-info">
        <div class="vendor-name-row">
          <span class="vendor-name">Gracian Seyama</span>
          <span class="status-pill status-inactive"><span class="sdot"></span> Inactive</span>
          <span class="vendor-code">VEN-00002</span>
        </div>
        <div class="vendor-meta">
          <span class="vendor-meta-item"><i class="fa-solid fa-calendar"></i> Created Aug 14, 2026</span>
          <span class="vendor-meta-item"><i class="fa-solid fa-tag"></i> Office Supplies</span>
          <span class="vendor-meta-item"><i class="fa-solid fa-coins"></i> Kwacha (K)</span>
          <span class="vendor-meta-item"><i class="fa-solid fa-clock"></i> Net 15 · Due on receipt</span>
        </div>
      </div>
      <div class="vendor-hero-actions">
        <button class="btn btn-danger-ghost" onclick="if(confirm('Delete this vendor?')){document.getElementById('vendor-delete-form').submit();}"><i class="fa-solid fa-trash-can"></i> Delete</button>
        <button class="btn btn-primary" id="activate-vendor-btn" onclick="activateVendor()"><i class="fa-solid fa-bolt"></i> Activate Vendor</button>
      </div>
    </div>
    <div class="vendor-quick-actions">
      <a class="quick-chip green" data-open-modal="modal-new-bill"><i class="fa-solid fa-plus"></i> New Bill</a>
      <a class="quick-chip" data-open-modal="modal-new-po"><i class="fa-solid fa-file-lines"></i> New Purchase Order</a>
      <a class="quick-chip" data-open-modal="modal-record-payment"><i class="fa-solid fa-credit-card"></i> Record Payment</a>
      <a class="quick-chip" data-open-modal="modal-send-message"><i class="fa-solid fa-envelope"></i> Send Message</a>
      <a class="quick-chip" data-open-modal="modal-add-note"><i class="fa-solid fa-note-sticky"></i> Add Note</a>
      <a class="quick-chip" href="#"><i class="fa-solid fa-star"></i> Add to Favourites</a>
    </div>
  </div>

  <div class="fin-strip">
    <div class="fin-card highlight">
      <div class="fin-card-top"><span class="fin-label">Open Balance</span><div class="fin-icon-wrap"><i class="fa-solid fa-wallet"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>1,875.50</div>
      <div class="fin-sub">1 open bill · due Sep 30, 2026</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Total Billed</span><div class="fin-icon-wrap"><i class="fa-solid fa-receipt"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>10,325.50</div>
      <div class="fin-sub">3 bills issued to date</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Opening Balance</span><div class="fin-icon-wrap"><i class="fa-solid fa-scale-balanced"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>0.00</div>
      <div class="fin-sub">No opening balance set</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Payment Terms</span><div class="fin-icon-wrap"><i class="fa-solid fa-clock-rotate-left"></i></div></div>
      <div class="fin-value terms">Net 15</div>
      <div class="fin-sub">Due on receipt of invoice</div>
    </div>
  </div>

  <div class="content-grid anim-4">
    <div class="tabs-card glass">
      <div class="tabs-nav">
        <button class="tab-btn active" data-tab="overview">Overview</button>
        <button class="tab-btn" data-tab="bills">Bills <span class="tab-count">3</span></button>
        <button class="tab-btn" data-tab="payments">Payments <span class="tab-count">2</span></button>
        <button class="tab-btn" data-tab="credits">Credits <span class="tab-count">0</span></button>
        <button class="tab-btn" data-tab="ledger">Ledger</button>
        <button class="tab-btn" data-tab="documents">Documents <span class="tab-count">3</span></button>
        <button class="tab-btn" data-tab="communication">Communication <span class="tab-count">2</span></button>
        <button class="tab-btn" data-tab="evaluation">Evaluation</button>
      </div>

      <div class="tab-body">

        <div class="tab-panel active" id="panel-overview">
          <div class="overview-section-title">Contact Information</div>
          <div class="info-grid">
            <div class="info-cell"><div class="info-cell-label">Display Name</div><div class="info-cell-value">Yeah</div></div>
            <div class="info-cell"><div class="info-cell-label">Email Address</div><div class="info-cell-value muted">Not provided</div></div>
            <div class="info-cell"><div class="info-cell-label">Phone Number</div><div class="info-cell-value muted">Not provided</div></div>
            <div class="info-cell"><div class="info-cell-label">Currency</div><div class="info-cell-value mono">K — Kwacha</div></div>
            <div class="info-cell"><div class="info-cell-label">Payment Terms (Days)</div><div class="info-cell-value mono">15</div></div>
            <div class="info-cell"><div class="info-cell-label">Opening Balance</div><div class="info-cell-value mono">K 0.00</div></div>
            <div class="info-cell"><div class="info-cell-label">Tax ID / VAT Number</div><div class="info-cell-value muted">Not provided</div></div>
            <div class="info-cell"><div class="info-cell-label">Vendor Category</div><div class="info-cell-value">Office Supplies</div></div>
            <div class="info-cell"><div class="info-cell-label">Internal Ref.</div><div class="info-cell-value mono">VEN-00002</div></div>
          </div>
          <div class="overview-section-title">Addresses</div>
          <div class="addresses-row">
            <div class="address-block"><div class="address-block-label"><i class="fa-solid fa-file-invoice"></i> Billing Address</div><div class="address-block-value empty">No billing address on file. Click Edit to add one.</div></div>
            <div class="address-block"><div class="address-block-label"><i class="fa-solid fa-money-bill-transfer"></i> Remit-To Address</div><div class="address-block-value empty">No remit-to address on file. Click Edit to add one.</div></div>
          </div>
          <div class="overview-section-title">Recent Activity</div>
          <div class="timeline">
            <div class="timeline-item"><div class="timeline-dot blue"></div><div class="timeline-title">Payment PAY-2026-0057 recorded</div><div class="timeline-desc">Cheque #00127 for K 4,250.00 applied to BILL-2026-0002.</div><div class="timeline-time">Sep 18, 2026 · 02:14 PM</div></div>
            <div class="timeline-item"><div class="timeline-dot"></div><div class="timeline-title">Bill BILL-2026-0003 created</div><div class="timeline-desc">Invoice for K 1,875.50 received — due Sep 30, 2026.</div><div class="timeline-time">Sep 15, 2026 · 10:02 AM</div></div>
            <div class="timeline-item"><div class="timeline-dot"></div><div class="timeline-title">Vendor record created</div><div class="timeline-desc">Gracian Seyama was added to the vendor directory by Elvis Seyama.</div><div class="timeline-time">Aug 14, 2026 · 09:32 AM</div></div>
          </div>
        </div>

        <div class="tab-panel" id="panel-bills">
          <div class="tab-toolbar">
            <div class="toolbar-left">
              <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="Search bills…"></div>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Status: All</button>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-download"></i> Export</button>
            </div>
            <button class="btn btn-primary btn-sm" data-open-modal="modal-new-bill"><i class="fa-solid fa-plus"></i> New Bill</button>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Bill No.</th><th>Bill Date</th><th>Due Date</th><th>Category</th><th class="td-right">Amount</th><th>Status</th><th></th></tr></thead>
                <tbody>
                  <tr class="clickable-row" data-open-bill="1" data-no="BILL-2026-0001" data-date="Aug 20, 2026" data-due="Sep 04, 2026" data-po="PO-2026-0004" data-amt="4,200.00" data-status="paid">
                    <td><a class="row-link" href="#" onclick="this.closest('tr.clickable-row')?.click();return false;">BILL-2026-0001</a><div class="td-sub">PO-2026-0004</div></td>
                    <td>Aug 20, 2026</td><td>Sep 04, 2026</td><td>Office Supplies</td>
                    <td class="td-right td-mono td-strong">4,200.00</td>
                    <td><span class="pill pill-paid"><span class="pdot"></span> Paid</span></td>
                    <td><button type="button" class="row-icon-btn" data-tip="View Bill"><i class="fa-solid fa-eye"></i></button></td>
                  </tr>
                  <tr class="clickable-row" data-open-bill="1" data-no="BILL-2026-0002" data-date="Sep 05, 2026" data-due="Sep 20, 2026" data-po="PO-2026-0007" data-amt="4,250.00" data-status="paid">
                    <td><a class="row-link" href="#" onclick="this.closest('tr.clickable-row')?.click();return false;">BILL-2026-0002</a><div class="td-sub">PO-2026-0007</div></td>
                    <td>Sep 05, 2026</td><td>Sep 20, 2026</td><td>Office Supplies</td>
                    <td class="td-right td-mono td-strong">4,250.00</td>
                    <td><span class="pill pill-paid"><span class="pdot"></span> Paid</span></td>
                    <td><button type="button" class="row-icon-btn" data-tip="View Bill"><i class="fa-solid fa-eye"></i></button></td>
                  </tr>
                  <tr class="clickable-row" data-open-bill="1" data-no="BILL-2026-0003" data-date="Sep 15, 2026" data-due="Sep 30, 2026" data-po="—" data-amt="1,875.50" data-status="open">
                    <td><a class="row-link" href="#" onclick="this.closest('tr.clickable-row')?.click();return false;">BILL-2026-0003</a><div class="td-sub">—</div></td>
                    <td>Sep 15, 2026</td><td>Sep 30, 2026</td><td>Office Supplies</td>
                    <td class="td-right td-mono td-strong">1,875.50</td>
                    <td><span class="pill pill-open"><span class="pdot"></span> Open</span></td>
                    <td><button type="button" class="row-icon-btn" data-tip="View Bill"><i class="fa-solid fa-eye"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="table-footer">
              <span>Showing 1–3 of 3 bills</span>
              <div class="pager"><button class="pager-btn"><i class="fa-solid fa-chevron-left"></i></button><button class="pager-btn active">1</button><button class="pager-btn"><i class="fa-solid fa-chevron-right"></i></button></div>
            </div>
          </div>
        </div>

        <div class="tab-panel" id="panel-payments">
          <div class="tab-toolbar">
            <div class="toolbar-left">
              <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="Search payments…"></div>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Method: All</button>
            </div>
            <button class="btn btn-primary btn-sm" data-open-modal="modal-record-payment"><i class="fa-solid fa-plus"></i> Record Payment</button>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Payment No.</th><th>Date</th><th>Method</th><th>Reference</th><th>Applied To</th><th class="td-right">Amount</th><th>Status</th></tr></thead>
                <tbody>
                  <tr><td><a class="row-link" href="#" onclick="return false;">PAY-2026-0041</a></td><td>Sep 02, 2026</td><td><i class="fa-solid fa-building-columns" style="opacity:.5;margin-right:5px"></i>Bank Transfer</td><td class="td-mono">REF-88213</td><td class="td-mono">BILL-2026-0001</td><td class="td-right td-mono td-strong">4,200.00</td><td><span class="pill pill-paid"><span class="pdot"></span> Cleared</span></td></tr>
                  <tr><td><a class="row-link" href="#" onclick="return false;">PAY-2026-0057</a></td><td>Sep 18, 2026</td><td><i class="fa-solid fa-money-check" style="opacity:.5;margin-right:5px"></i>Cheque</td><td class="td-mono">CHQ-00127</td><td class="td-mono">BILL-2026-0002</td><td class="td-right td-mono td-strong">4,250.00</td><td><span class="pill pill-paid"><span class="pdot"></span> Cleared</span></td></tr>
                </tbody>
              </table>
            </div>
            <div class="table-footer"><span>Showing 1–2 of 2 payments</span><div class="pager"><button class="pager-btn"><i class="fa-solid fa-chevron-left"></i></button><button class="pager-btn active">1</button><button class="pager-btn"><i class="fa-solid fa-chevron-right"></i></button></div></div>
          </div>
        </div>

        <div class="tab-panel" id="panel-credits">
          <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="empty-title">No credit notes yet</div>
            <div class="empty-sub">Credits, refunds and returns issued to this vendor will appear here.</div>
            <button class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> New Credit Note</button>
          </div>
        </div>

        <div class="tab-panel" id="panel-ledger">
          <div class="tab-toolbar">
            <div class="toolbar-left">
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-calendar"></i> Aug 01 – Sep 30, 2026</button>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-download"></i> Export Statement</button>
            </div>
            <span style="font-size:12px;color:var(--ink-soft);font-weight:600">Closing balance: <strong style="color:var(--deep-2)">K 1,875.50</strong></span>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Date</th><th>Entry</th><th>Type</th><th>Reference</th><th class="td-right">Debit</th><th class="td-right">Credit</th><th class="td-right">Balance</th></tr></thead>
                <tbody>
                  <tr><td>Aug 20, 2026</td><td class="td-mono">JE-1001</td><td>Bill</td><td class="td-mono">BILL-2026-0001</td><td class="td-right td-mono">—</td><td class="td-right td-mono">4,200.00</td><td class="td-right td-mono td-strong">4,200.00</td></tr>
                  <tr><td>Sep 02, 2026</td><td class="td-mono">JE-1044</td><td>Payment</td><td class="td-mono">PAY-2026-0041</td><td class="td-right td-mono">4,200.00</td><td class="td-right td-mono">—</td><td class="td-right td-mono td-strong">0.00</td></tr>
                  <tr><td>Sep 05, 2026</td><td class="td-mono">JE-1062</td><td>Bill</td><td class="td-mono">BILL-2026-0002</td><td class="td-right td-mono">—</td><td class="td-right td-mono">4,250.00</td><td class="td-right td-mono td-strong">4,250.00</td></tr>
                  <tr><td>Sep 15, 2026</td><td class="td-mono">JE-1088</td><td>Bill</td><td class="td-mono">BILL-2026-0003</td><td class="td-right td-mono">—</td><td class="td-right td-mono">1,875.50</td><td class="td-right td-mono td-strong">6,125.50</td></tr>
                  <tr><td>Sep 18, 2026</td><td class="td-mono">JE-1103</td><td>Payment</td><td class="td-mono">PAY-2026-0057</td><td class="td-right td-mono">4,250.00</td><td class="td-right td-mono">—</td><td class="td-right td-mono td-strong">1,875.50</td></tr>
                  <tr class="ledger-total"><td colspan="4">Totals</td><td class="td-right td-mono">8,450.00</td><td class="td-right td-mono">10,325.50</td><td class="td-right td-mono">1,875.50</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="tab-panel" id="panel-documents">
          <div class="dropzone">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <div class="dropzone-title">Drop files here or click to upload</div>
            <div class="dropzone-sub">Contracts, tax certificates, price lists · PDF, XLSX, JPG · Max 25 MB</div>
          </div>
          <div class="doc-grid">
            <div class="doc-card"><div class="doc-card-top"><div class="doc-ext ext-pdf">PDF</div><div><div class="doc-name">Supplier_Agreement_2026.pdf</div><div class="doc-meta">248 KB · Aug 14, 2026 · ES</div></div></div><div class="doc-card-actions"><button class="doc-action-btn"><i class="fa-solid fa-eye"></i> View</button><button class="doc-action-btn"><i class="fa-solid fa-download"></i> Download</button></div></div>
            <div class="doc-card"><div class="doc-card-top"><div class="doc-ext ext-pdf">PDF</div><div><div class="doc-name">Tax_Clearance_Certificate.pdf</div><div class="doc-meta">112 KB · Aug 14, 2026 · ES</div></div></div><div class="doc-card-actions"><button class="doc-action-btn"><i class="fa-solid fa-eye"></i> View</button><button class="doc-action-btn"><i class="fa-solid fa-download"></i> Download</button></div></div>
            <div class="doc-card"><div class="doc-card-top"><div class="doc-ext ext-xls">XLSX</div><div><div class="doc-name">Price_List_Sep2026.xlsx</div><div class="doc-meta">64 KB · Sep 01, 2026 · GS</div></div></div><div class="doc-card-actions"><button class="doc-action-btn"><i class="fa-solid fa-eye"></i> View</button><button class="doc-action-btn"><i class="fa-solid fa-download"></i> Download</button></div></div>
          </div>
        </div>

        <div class="tab-panel" id="panel-communication">
          <div class="tab-toolbar">
            <div class="toolbar-left"><button class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> All messages</button></div>
            <button class="btn btn-primary btn-sm" data-open-modal="modal-send-message"><i class="fa-solid fa-envelope"></i> New Message</button>
          </div>
          <div class="thread">
            <div class="msg-card">
              <div class="msg-head"><div class="msg-avatar in">GS</div><div><div class="msg-subject">Re: September Statement</div><div class="msg-meta">Gracian Seyama · Sep 20, 2026 · 08:41 AM</div></div><span class="msg-dir dir-in">INBOUND</span></div>
              <div class="msg-body">Good morning. I confirm receipt of the September statement. Please note invoice 0003 reflects the revised stationery prices agreed on the 1st. Kindly process payment before month-end as discussed…</div>
              <div class="msg-foot"><button class="msg-link"><i class="fa-solid fa-reply"></i> Reply</button><button class="msg-link"><i class="fa-solid fa-share"></i> Forward</button><button class="msg-link"><i class="fa-solid fa-paperclip"></i> statement_sep.pdf</button></div>
            </div>
            <div class="msg-card">
              <div class="msg-head"><div class="msg-avatar out">ES</div><div><div class="msg-subject">Welcome & Onboarding Documents</div><div class="msg-meta">Elvis Seyama · Aug 14, 2026 · 09:50 AM</div></div><span class="msg-dir dir-out">OUTBOUND</span></div>
              <div class="msg-body">Welcome aboard! Kindly submit your signed supplier agreement, tax clearance certificate and current price list so we can complete your onboarding and activate your account for purchase orders…</div>
              <div class="msg-foot"><button class="msg-link"><i class="fa-solid fa-reply"></i> Reply</button><button class="msg-link"><i class="fa-solid fa-share"></i> Forward</button></div>
            </div>
          </div>
        </div>

        <div class="tab-panel" id="panel-evaluation">
          <div class="eval-grid">
            <div>
              <div class="overview-section-title">Performance Scorecard — Q3 2026</div>
              <div class="criteria-row"><div><div class="criteria-name">Product / Service Quality</div><div class="criteria-hint">Conformance to specifications & returns rate</div></div><span class="criteria-weight">Weight 30%</span><div class="stars-input"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
              <div class="criteria-row"><div><div class="criteria-name">Delivery Timeliness</div><div class="criteria-hint">On-time & in-full delivery performance</div></div><span class="criteria-weight">Weight 25%</span><div class="stars-input"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
              <div class="criteria-row"><div><div class="criteria-name">Price Competitiveness</div><div class="criteria-hint">Benchmarked against market rates</div></div><span class="criteria-weight">Weight 20%</span><div class="stars-input"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
              <div class="criteria-row"><div><div class="criteria-name">Communication & Responsiveness</div><div class="criteria-hint">Query resolution speed and clarity</div></div><span class="criteria-weight">Weight 15%</span><div class="stars-input"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
              <div class="criteria-row"><div><div class="criteria-name">Compliance & Documentation</div><div class="criteria-hint">Tax compliance, contracts and invoicing accuracy</div></div><span class="criteria-weight">Weight 10%</span><div class="stars-input"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
              <div class="form-field" style="margin-top:18px"><label>Evaluator Comments</label><textarea class="form-control" placeholder="Summarise strengths, issues and recommended actions…"></textarea></div>
              <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
                <button class="btn btn-ghost">Save as Draft</button>
                <button class="btn btn-primary"><i class="fa-solid fa-star"></i> Submit Evaluation</button>
              </div>
            </div>
            <div>
              <div class="eval-summary">
                <div class="eval-summary-score">—</div>
                <div style="justify-content:center;margin:8px 0 4px;display:flex;gap:3px">
                  <i class="fa-solid fa-star" style="color:var(--border);font-size:14px"></i><i class="fa-solid fa-star" style="color:var(--border);font-size:14px"></i><i class="fa-solid fa-star" style="color:var(--border);font-size:14px"></i><i class="fa-solid fa-star" style="color:var(--border);font-size:14px"></i><i class="fa-solid fa-star" style="color:var(--border);font-size:14px"></i>
                </div>
                <div class="eval-summary-label">Overall Rating</div>
                <div class="eval-history">0 evaluations on record</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div class="side-panel">
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Risk Assessment</span><a class="side-card-action" href="#">Details</a></div>
        <div class="side-card-body">
          <div class="risk-meter"><div class="risk-meter-top"><span class="risk-label">Credit Risk</span><span class="risk-value low">Low</span></div><div class="risk-track"><div class="risk-fill low" style="width:15%"></div></div></div>
          <div class="risk-meter"><div class="risk-meter-top"><span class="risk-label">Delivery Reliability</span><span class="risk-value med">N/A</span></div><div class="risk-track"><div class="risk-fill med" style="width:0%"></div></div></div>
          <div class="risk-meter" style="margin-bottom:0"><div class="risk-meter-top"><span class="risk-label">Compliance Score</span><span class="risk-value med">Pending</span></div><div class="risk-track"><div class="risk-fill med" style="width:5%"></div></div></div>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Contact Details</span><a class="side-card-action" href="#">Edit</a></div>
        <div class="side-card-body">
          <div class="contact-item"><div class="contact-icon email"><i class="fa-solid fa-envelope"></i></div><div><div class="contact-type">Email</div><div class="contact-val empty">Not provided</div></div></div>
          <div class="contact-item"><div class="contact-icon phone"><i class="fa-solid fa-phone"></i></div><div><div class="contact-type">Phone</div><div class="contact-val empty">Not provided</div></div></div>
          <div class="contact-item"><div class="contact-icon web"><i class="fa-solid fa-globe"></i></div><div><div class="contact-type">Website</div><div class="contact-val empty">Not provided</div></div></div>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Recent Documents</span><a class="side-card-action" href="#">View all</a></div>
        <div class="side-card-body">
          <div class="mini-doc"><div class="mini-doc-icon ext-pdf">PDF</div><div style="flex:1;overflow:hidden"><div class="mini-doc-name">Supplier_Agreement_2026.pdf</div><div class="mini-doc-meta">248 KB · Aug 14, 2026</div></div></div>
          <div class="mini-doc"><div class="mini-doc-icon ext-xls">XLSX</div><div style="flex:1;overflow:hidden"><div class="mini-doc-name">Price_List_Sep2026.xlsx</div><div class="mini-doc-meta">64 KB · Sep 01, 2026</div></div></div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- ═════════ MODALS ═════════ -->

<!-- BILL VIEW -->
<div class="modal-overlay" id="modal-bill-view">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
      <div class="modal-head-text">
        <div class="modal-title" id="bv-no">BILL-2026-0003</div>
        <div class="modal-subtitle">Gracian Seyama · VEN-00002</div>
      </div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="bv-chips">
        <span id="bv-status"><span class="pill pill-open"><span class="pdot"></span> Open</span></span>
        <span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Amount Due: K <span id="bv-amt-chip">1,875.50</span></span>
      </div>
      <div class="bv-meta">
        <div class="bv-meta-cell"><div class="bv-meta-label">Bill Date</div><div class="bv-meta-val" id="bv-date">Sep 15, 2026</div></div>
        <div class="bv-meta-cell"><div class="bv-meta-label">Due Date</div><div class="bv-meta-val" id="bv-due">Sep 30, 2026</div></div>
        <div class="bv-meta-cell"><div class="bv-meta-label">Linked PO</div><div class="bv-meta-val" id="bv-po">—</div></div>
        <div class="bv-meta-cell"><div class="bv-meta-label">Currency</div><div class="bv-meta-val">K — Kwacha</div></div>
      </div>
      <div class="form-section-title">Line Items</div>
      <table class="items-table">
        <thead><tr><th style="width:52%">Description</th><th style="width:14%">Qty</th><th style="width:17%" class="td-right">Unit (K)</th><th style="width:17%" class="td-right">Amount (K)</th></tr></thead>
        <tbody>
          <tr><td>Stationery bundle — pens, files, binders</td><td>15</td><td class="td-right td-mono">98.00</td><td class="td-right td-mono">1,470.00</td></tr>
          <tr><td>Delivery & handling — HQ</td><td>1</td><td class="td-right td-mono">350.00</td><td class="td-right td-mono">350.00</td></tr>
          <tr><td>VAT 16% on goods</td><td>—</td><td class="td-right td-mono">—</td><td class="td-right td-mono">55.50</td></tr>
        </tbody>
      </table>
      <div class="totals-block">
        <div class="totals-row"><span>Subtotal</span><span class="td-mono">1,820.00</span></div>
        <div class="totals-row"><span>VAT</span><span class="td-mono">55.50</span></div>
        <div class="totals-row grand"><span>Total</span><span class="td-mono" id="bv-amt">1,875.50</span></div>
      </div>
      <div class="form-section-title">Payment History</div>
      <div id="bv-payrow-empty" class="form-alert info" style="margin-bottom:0"><i class="fa-solid fa-circle-info"></i> No payments applied to this bill yet.</div>
      <div id="bv-payrow" style="display:none" class="form-alert ok"><i class="fa-solid fa-circle-check"></i> Paid in full — payment cleared against this bill.</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost"><i class="fa-solid fa-print"></i> Print</button>
      <button class="btn btn-ghost"><i class="fa-solid fa-download"></i> Download PDF</button>
      <button class="btn btn-deep" id="bv-pay-btn"><i class="fa-solid fa-credit-card"></i> Record Payment</button>
    </div>
  </div>
</div>

<!-- ══ RECORD PAYMENT ══ -->
<div class="modal-overlay" id="modal-record-payment">
  <div class="modal mid">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-credit-card"></i></div>
      <div class="modal-head-text"><div class="modal-title">Record Payment</div><div class="modal-subtitle">Apply a payment to open bills for Gracian Seyama</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field"><label>Payment Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-09-28"></div>
        <div class="form-field"><label>Payment Method <span class="req">*</span></label>
          <select class="form-control" id="pay-method">
            <option value="">— Select method —</option>
            <option value="bank">Bank Transfer</option>
            <option value="mobile">Mobile Money</option>
            <option value="card">Card</option>
            <option value="cash">Cash</option>
            <option value="split">Split Payment</option>
          </select>
        </div>
        <div class="form-field"><label>Paying Bank Account</label>
          <select class="form-control"><option>Zanaco — ****4402</option><option>Stanbic — ****1188</option><option>Cash Drawer — HQ</option></select>
        </div>
        <div class="form-field"><label>Payment Amount (K) <span class="req">*</span></label><input class="form-control" id="pay-amount" style="text-align:right" value="1,875.50"></div>
      </div>

      <!-- electronic method details -->
      <div id="pay-elec" style="display:none">
        <div class="form-section-title">Transaction Details <span class="method-flag" id="elec-flag"><i class="fa-solid fa-building-columns"></i> Bank Transfer</span></div>
        <div class="form-grid">
          <div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control" id="pay-ref" placeholder="e.g. TXN-88213 / UTR-004512"></div>
          <div class="form-field"><label>Provider / Institution <span class="req">*</span></label><select class="form-control" id="pay-provider"></select></div>
          <div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Main Operating Account"></div>
          <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. Gracian Seyama"></div>
        </div>
      </div>

      <!-- cash details -->
      <div id="pay-cash" style="display:none">
        <div class="form-section-title">Cash Handling <span class="method-flag" style="background:var(--green-tint);color:var(--green)"><i class="fa-solid fa-money-bill"></i> Cash</span></div>
        <div class="form-grid">
          <div class="form-field"><label>Cash Tendered</label><input class="form-control" style="text-align:right" placeholder="—"></div>
          <div class="form-field"><label>Change Given</label><input class="form-control" style="text-align:right" placeholder="—"></div>
        </div>
      </div>

      <!-- ═ SPLIT PAYMENT — CHECKBOX TILES ═ -->
      <div id="pay-split" style="display:none">
        <div class="form-section-title">Split Allocation <span class="method-flag"><i class="fa-solid fa-layer-group"></i> Tick every method used</span></div>

        <div class="split-opts" id="split-opts">

          <!-- CASH -->
          <div class="split-opt" data-method="cash">
            <label class="split-head">
              <input type="checkbox" class="sp-check">
              <span class="sp-box"><i class="fa-solid fa-check"></i></span>
              <span class="sp-icon"><i class="fa-solid fa-money-bill"></i></span>
              <span class="sp-text"><span class="sp-name">Cash</span><span class="sp-hint">Tendered & change</span></span>
              <span class="sp-amt-preview"></span>
              <i class="fa-solid fa-chevron-down sp-chev"></i>
            </label>
            <div class="split-fields">
              <div class="form-grid">
                <div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div>
                <div class="form-field"><label>Cash Tendered</label><input class="form-control" style="text-align:right" placeholder="—"></div>
                <div class="form-field"><label>Change Given</label><input class="form-control" style="text-align:right" placeholder="—"></div>
              </div>
            </div>
          </div>

          <!-- BANK TRANSFER -->
          <div class="split-opt" data-method="bank">
            <label class="split-head">
              <input type="checkbox" class="sp-check">
              <span class="sp-box"><i class="fa-solid fa-check"></i></span>
              <span class="sp-icon"><i class="fa-solid fa-building-columns"></i></span>
              <span class="sp-text"><span class="sp-name">Bank Transfer</span><span class="sp-hint">Reference & bank details</span></span>
              <span class="sp-amt-preview"></span>
              <i class="fa-solid fa-chevron-down sp-chev"></i>
            </label>
            <div class="split-fields">
              <div class="form-grid">
                <div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div>
                <div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control sp-ref" placeholder="e.g. UTR-004512"></div>
                <div class="form-field"><label>Provider / Institution <span class="req">*</span></label>
                  <select class="form-control sp-provider"><option>Zanaco</option><option>Stanbic Bank</option><option>FNB Zambia</option><option>Absa Bank</option><option>Investrust Bank</option></select></div>
                <div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Vendor Settlement Account"></div>
                <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. Gracian Seyama"></div>
              </div>
            </div>
          </div>

          <!-- CARD -->
          <div class="split-opt" data-method="card">
            <label class="split-head">
              <input type="checkbox" class="sp-check">
              <span class="sp-box"><i class="fa-solid fa-check"></i></span>
              <span class="sp-icon"><i class="fa-solid fa-credit-card"></i></span>
              <span class="sp-text"><span class="sp-name">Card</span><span class="sp-hint">Terminal / scheme details</span></span>
              <span class="sp-amt-preview"></span>
              <i class="fa-solid fa-chevron-down sp-chev"></i>
            </label>
            <div class="split-fields">
              <div class="form-grid">
                <div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div>
                <div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control sp-ref" placeholder="e.g. AUTH-771203"></div>
                <div class="form-field"><label>Provider / Institution <span class="req">*</span></label>
                  <select class="form-control sp-provider"><option>Visa</option><option>Mastercard</option><option>American Express</option><option>UnionPay</option></select></div>
                <div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Merchant Settlement"></div>
                <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. Acme Corporation"></div>
              </div>
            </div>
          </div>

          <!-- MOBILE MONEY -->
          <div class="split-opt" data-method="mobile">
            <label class="split-head">
              <input type="checkbox" class="sp-check">
              <span class="sp-box"><i class="fa-solid fa-check"></i></span>
              <span class="sp-icon"><i class="fa-solid fa-mobile-screen"></i></span>
              <span class="sp-text"><span class="sp-name">Mobile Money</span><span class="sp-hint">Wallet transaction capture</span></span>
              <span class="sp-amt-preview"></span>
              <i class="fa-solid fa-chevron-down sp-chev"></i>
            </label>
            <div class="split-fields">
              <div class="form-grid">
                <div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div>
                <div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control sp-ref" placeholder="e.g. MOMO-558812"></div>
                <div class="form-field"><label>Provider / Institution <span class="req">*</span></label>
                  <select class="form-control sp-provider"><option>MTN MoMo</option><option>Airtel Money</option><option>Zamtel Kwacha</option></select></div>
                <div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Wallet Settlement"></div>
                <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. Gracian Seyama"></div>
              </div>
            </div>
          </div>

        </div>

        <div class="totals-block fullw">
          <div class="totals-row"><span>Payment Amount</span><span class="td-mono" id="sp-total">0.00</span></div>
          <div class="totals-row"><span>Allocated Across Selected Methods</span><span class="td-mono" id="sp-alloc">0.00</span></div>
          <div class="totals-row grand"><span>Unallocated</span><span class="td-mono" id="sp-remain">0.00</span></div>
        </div>
        <div id="split-banner" style="margin-top:12px"></div>
      </div>

      <div class="form-section-title">Apply To Open Bills</div>
      <label class="apply-row"><input type="checkbox" checked><div class="apply-info"><div class="apply-title">BILL-2026-0003</div><div class="apply-sub">Due Sep 30, 2026 · Open</div></div><span class="apply-amt">1,875.50</span></label>
      <div class="form-alert info" style="margin-bottom:0"><i class="fa-solid fa-circle-info"></i> Unapplied amount: K 0.00 — payment fully allocated.</div>
      <div class="form-field" style="margin-top:14px"><label>Payment Note</label><textarea class="form-control" style="min-height:60px" placeholder="Optional…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep" id="post-payment" disabled><i class="fa-solid fa-check"></i> Record Payment</button>
    </div>
  </div>
</div>

<!-- NEW BILL -->
<div class="modal-overlay" id="modal-new-bill">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-receipt"></i></div>
      <div class="modal-head-text"><div class="modal-title">New Bill</div><div class="modal-subtitle">Record a supplier invoice against Gracian Seyama</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">Bill Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Vendor</label><input class="form-control" value="Gracian Seyama (VEN-00002)" disabled></div>
        <div class="form-field"><label>Bill Number <span class="req">*</span></label><input class="form-control" value="BILL-2026-0004"></div>
        <div class="form-field"><label>Bill Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-09-28"></div>
        <div class="form-field"><label>Due Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-10-13"></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Category</label><select class="form-control"><option>Office Supplies</option><option>IT Equipment</option><option>Services</option></select></div>
        <div class="form-field full"><label>Linked Purchase Order</label><select class="form-control"><option>— None —</option><option>PO-2026-0004</option><option>PO-2026-0007</option></select></div>
      </div>
      <div class="form-section-title">Line Items</div>
      <table class="items-table" id="bill-items">
        <thead><tr><th style="width:46%">Description</th><th style="width:12%">Qty</th><th style="width:18%">Unit Price (K)</th><th style="width:16%" class="td-right">Amount (K)</th><th></th></tr></thead>
        <tbody>
          <tr><td><input class="form-control" value="A4 Ream Paper — Box (50 reams)"></td><td><input class="form-control" value="10"></td><td><input class="form-control" value="320.00"></td><td class="td-right td-mono">3,200.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
          <tr><td><input class="form-control" value="Toner Cartridge HP 26A"></td><td><input class="form-control" value="4"></td><td><input class="form-control" value="480.00"></td><td class="td-right td-mono">1,920.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
        </tbody>
      </table>
      <button class="add-item-btn" data-add-item="bill-items"><i class="fa-solid fa-plus"></i> Add Line Item</button>
      <div class="totals-block">
        <div class="totals-row"><span>Subtotal</span><span class="td-mono">5,120.00</span></div>
        <div class="totals-row"><span>VAT 16%</span><span class="td-mono">819.20</span></div>
        <div class="totals-row grand"><span>Total Due</span><span class="td-mono">5,939.20</span></div>
      </div>
      <div class="form-section-title">Attachments & Notes</div>
      <div class="dropzone-sm"><i class="fa-solid fa-paperclip"></i> Attach invoice scan (PDF/JPG)</div>
      <div class="form-field" style="margin-top:12px"><label>Internal Notes</label><textarea class="form-control" placeholder="Optional notes for internal reference…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-ghost">Save as Draft</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Save Bill</button>
    </div>
  </div>
</div>

<!-- NEW PURCHASE ORDER -->
<div class="modal-overlay" id="modal-new-po">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-file-lines"></i></div>
      <div class="modal-head-text"><div class="modal-title">New Purchase Order</div><div class="modal-subtitle">Raise a PO for Gracian Seyama</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">Order Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Vendor</label><input class="form-control" value="Gracian Seyama (VEN-00002)" disabled></div>
        <div class="form-field"><label>PO Number <span class="req">*</span></label><input class="form-control" value="PO-2026-0012"></div>
        <div class="form-field"><label>Order Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-09-28"></div>
        <div class="form-field"><label>Expected Delivery</label><input class="form-control" type="date" value="2026-10-08"></div>
        <div class="form-field"><label>Deliver To</label><select class="form-control"><option>Headquarters — Main Store</option><option>Branch — Lusaka</option></select></div>
        <div class="form-field"><label>Priority</label><select class="form-control"><option>Normal</option><option>Urgent</option><option>Low</option></select></div>
      </div>
      <div class="form-section-title">Line Items</div>
      <table class="items-table" id="po-items">
        <thead><tr><th style="width:46%">Item Description</th><th style="width:12%">Qty</th><th style="width:18%">Unit Price (K)</th><th style="width:16%" class="td-right">Amount (K)</th><th></th></tr></thead>
        <tbody>
          <tr><td><input class="form-control" value="Office Chair — Executive"></td><td><input class="form-control" value="6"></td><td><input class="form-control" value="850.00"></td><td class="td-right td-mono">5,100.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
          <tr><td><input class="form-control" value="Filing Cabinet 4-Drawer"></td><td><input class="form-control" value="2"></td><td><input class="form-control" value="1,450.00"></td><td class="td-right td-mono">2,900.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
        </tbody>
      </table>
      <button class="add-item-btn" data-add-item="po-items"><i class="fa-solid fa-plus"></i> Add Line Item</button>
      <div class="totals-block">
        <div class="totals-row"><span>Subtotal</span><span class="td-mono">8,000.00</span></div>
        <div class="totals-row"><span>VAT 16%</span><span class="td-mono">1,280.00</span></div>
        <div class="totals-row grand"><span>PO Total</span><span class="td-mono">9,280.00</span></div>
      </div>
      <div class="form-section-title">Terms & Notes</div>
      <div class="form-field"><label>Notes to Vendor</label><textarea class="form-control" placeholder="Delivery instructions, specifications…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-ghost">Save as Draft</button>
      <button class="btn btn-deep"><i class="fa-solid fa-paper-plane"></i> Create & Send PO</button>
    </div>
  </div>
</div>

<!-- SEND MESSAGE -->
<div class="modal-overlay" id="modal-send-message">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-envelope"></i></div>
      <div class="modal-head-text"><div class="modal-title">Send Message</div><div class="modal-subtitle">Email or SMS the vendor contact</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-alert warn"><i class="fa-solid fa-triangle-exclamation"></i> No email on file for this vendor — add a contact email to deliver messages.</div>
      <div class="form-grid">
        <div class="form-field full"><label>To</label><input class="form-control" placeholder="No email on file" disabled></div>
        <div class="form-field"><label>Channel</label><select class="form-control"><option>Email</option><option>SMS</option></select></div>
        <div class="form-field"><label>Template</label><select class="form-control"><option>— Blank —</option><option>Statement Reminder</option><option>Payment Remittance Advice</option><option>PO Confirmation</option></select></div>
        <div class="form-field full"><label>Subject <span class="req">*</span></label><input class="form-control" value="Outstanding Invoice — BILL-2026-0003"></div>
        <div class="form-field full"><label>Message <span class="req">*</span></label><textarea class="form-control" style="min-height:120px">Dear Gracian,

This is a friendly reminder that invoice BILL-2026-0003 (K 1,875.50) falls due on 30 Sep 2026. Kindly confirm your bank details are unchanged for settlement.

Regards,
Elvis Seyama — Acme Corporation</textarea></div>
        <div class="form-field full"><div class="dropzone-sm"><i class="fa-solid fa-paperclip"></i> Attach files (optional)</div></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
    </div>
  </div>
</div>

<!-- ADD NOTE -->
<div class="modal-overlay" id="modal-add-note">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-note-sticky"></i></div>
      <div class="modal-head-text"><div class="modal-title">Add Note</div><div class="modal-subtitle">Internal note or reminder on this vendor</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field"><label>Note Type</label><select class="form-control"><option>Internal Note</option><option>Reminder</option><option>Follow-up</option></select></div>
        <div class="form-field"><label>Reminder Date</label><input class="form-control" type="date"></div>
        <div class="form-field full"><label>Title <span class="req">*</span></label><input class="form-control" placeholder="e.g. Await updated tax clearance"></div>
        <div class="form-field full"><label>Note <span class="req">*</span></label><textarea class="form-control" placeholder="Details visible to internal users only…"></textarea></div>
        <div class="form-field full">
          <div class="switch-row">
            <label class="switch"><input type="checkbox"><span class="switch-slider"></span></label>
            <div><div class="switch-row-label">Pin to vendor profile</div><div class="switch-row-sub">Shows the note at the top of the Overview tab</div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Save Note</button>
    </div>
  </div>
</div>

<!-- EDIT VENDOR -->
<div class="modal-overlay" id="modal-edit-vendor">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-pen-to-square"></i></div>
      <div class="modal-head-text"><div class="modal-title">Edit Vendor</div><div class="modal-subtitle">Update master data for VEN-00002</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">General</div>
      <div class="form-grid">
        <div class="form-field"><label>Display Name <span class="req">*</span></label><input class="form-control" value="Yeah"></div>
        <div class="form-field"><label>Legal Name <span class="req">*</span></label><input class="form-control" value="Gracian Seyama"></div>
        <div class="form-field"><label>Vendor Code</label><input class="form-control" value="VEN-00002" disabled></div>
        <div class="form-field"><label>Category</label><select class="form-control"><option>Office Supplies</option><option>IT Equipment</option><option>Services</option><option>Logistics</option></select></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Tax ID / VAT No.</label><input class="form-control" placeholder="e.g. 1002345678"></div>
      </div>
      <div class="form-section-title">Contact</div>
      <div class="form-grid">
        <div class="form-field"><label>Email</label><input class="form-control" type="email" placeholder="name@company.com"></div>
        <div class="form-field"><label>Phone</label><input class="form-control" placeholder="+260 …"></div>
        <div class="form-field full"><label>Website</label><input class="form-control" placeholder="https://…"></div>
      </div>
      <div class="form-section-title">Addresses</div>
      <div class="form-grid">
        <div class="form-field full"><label>Billing Address</label><textarea class="form-control" style="min-height:64px" placeholder="Street, city, country…"></textarea></div>
        <div class="form-field full"><label>Remit-To Address</label><textarea class="form-control" style="min-height:64px" placeholder="If different from billing…"></textarea></div>
      </div>
      <div class="form-section-title">Payment & Status</div>
      <div class="form-grid">
        <div class="form-field"><label>Payment Terms (Days)</label><input class="form-control" type="number" value="15"></div>
        <div class="form-field"><label>Preferred Payment Method</label><select class="form-control"><option>Bank Transfer</option><option>Cheque</option><option>Mobile Money</option></select></div>
        <div class="form-field"><label>Opening Balance (K)</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field" style="display:flex;align-items:flex-end;padding-bottom:6px">
          <div class="switch-row">
            <label class="switch"><input type="checkbox"><span class="switch-slider"></span></label>
            <div><div class="switch-row-label">Vendor is Active</div><div class="switch-row-sub">Currently inactive — toggle to re-activate</div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger-ghost spacer"><i class="fa-solid fa-trash-can"></i> Delete Vendor</button>
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Save Changes</button>
    </div>
  </div>
</div>

<script>
  /* tabs / sidebar */
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
    });
  });
  document.querySelectorAll('.sidebar-item').forEach(item => {
    item.addEventListener('click', e => {
      e.preventDefault();
      document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
      item.classList.add('active');
    });
  });

  /* modals */
  const openModal = id => { const ov = document.getElementById(id); ov.classList.remove('closing'); ov.classList.add('open'); document.body.classList.add('modal-open'); };
  const closeModal = ov => { ov.classList.add('closing'); setTimeout(() => { ov.classList.remove('open','closing'); document.body.classList.remove('modal-open'); }, 180); };
  document.querySelectorAll('[data-open-modal]').forEach(el => el.addEventListener('click', e => { e.preventDefault(); openModal(el.dataset.openModal); }));
  document.querySelectorAll('.modal-overlay').forEach(ov => {
    ov.addEventListener('mousedown', e => { if (e.target === ov) closeModal(ov); });
    ov.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => closeModal(ov)));
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(closeModal); });

  /* bill view — whole row clickable */
  function openBillFromData(d){
    document.getElementById('bv-no').textContent = d.no;
    document.getElementById('bv-date').textContent = d.date;
    document.getElementById('bv-due').textContent = d.due;
    document.getElementById('bv-po').textContent = d.po;
    document.getElementById('bv-amt').textContent = d.amt;
    document.getElementById('bv-amt-chip').textContent = d.amt;
    const paid = d.status === 'paid';
    document.getElementById('bv-status').innerHTML = paid
      ? '<span class="pill pill-paid"><span class="pdot"></span> Paid</span>'
      : '<span class="pill pill-open"><span class="pdot"></span> Open</span>';
    document.getElementById('bv-payrow').style.display = paid ? 'flex' : 'none';
    document.getElementById('bv-payrow-empty').style.display = paid ? 'none' : 'flex';
    document.getElementById('bv-pay-btn').style.display = paid ? 'none' : 'inline-flex';
    openModal('modal-bill-view');
  }

  document.querySelectorAll('tr[data-open-bill]').forEach(row => {
    row.setAttribute('tabindex', '0');
    row.addEventListener('click', e => {
      if (e.target.closest('a')) e.preventDefault();
      openBillFromData(row.dataset);
    });
    row.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openBillFromData(row.dataset);
      }
    });
  });

  document.getElementById('bv-pay-btn').addEventListener('click', () => {
    closeModal(document.getElementById('modal-bill-view'));
    setTimeout(() => openModal('modal-record-payment'), 200);
  });

  /* line items */
  document.querySelectorAll('[data-add-item]').forEach(btn => {
    btn.addEventListener('click', () => {
      const tbody = document.getElementById(btn.dataset.addItem).querySelector('tbody');
      const row = tbody.insertRow();
      row.style.animation = 'fadeUp .3s ease both';
      row.innerHTML = '<td><input class="form-control" placeholder="Item description"></td><td><input class="form-control" value="1"></td><td><input class="form-control" value="0.00"></td><td class="td-right td-mono">0.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td>';
    });
  });
  document.addEventListener('click', e => { const rm = e.target.closest('.item-remove'); if (rm) rm.closest('tr').remove(); });

  /* stars */
  document.querySelectorAll('.stars-input').forEach(group => {
    const stars = group.querySelectorAll('i');
    stars.forEach((star, idx) => star.addEventListener('click', () => stars.forEach((s, i) => s.classList.toggle('on', i <= idx))));
  });

  /* ══ RECORD PAYMENT LOGIC ══ */
  const PROVIDERS = {
    bank:   ['Zanaco', 'Stanbic Bank', 'FNB Zambia', 'Absa Bank', 'Investrust Bank'],
    mobile: ['MTN MoMo', 'Airtel Money', 'Zamtel Kwacha'],
    card:   ['Visa', 'Mastercard', 'American Express', 'UnionPay']
  };
  const METHOD_META = {
    bank:   { label: 'Bank Transfer', icon: 'fa-building-columns' },
    mobile: { label: 'Mobile Money',  icon: 'fa-mobile-screen' },
    card:   { label: 'Card',          icon: 'fa-credit-card' }
  };
  const fmt = n => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const num = v => parseFloat(String(v).replace(/,/g, '')) || 0;

  const payMethod  = document.getElementById('pay-method');
  const payAmount  = document.getElementById('pay-amount');
  const elecPanel  = document.getElementById('pay-elec');
  const cashPanel  = document.getElementById('pay-cash');
  const splitPanel = document.getElementById('pay-split');
  const splitOpts  = [...document.querySelectorAll('#split-opts .split-opt')];
  const postBtn    = document.getElementById('post-payment');

  function populateProviders(sel, method) { sel.innerHTML = (PROVIDERS[method] || []).map(p => `<option>${p}</option>`).join(''); }

  function syncMethodPanels() {
    const v = payMethod.value;
    elecPanel.style.display  = ['bank','mobile','card'].includes(v) ? '' : 'none';
    cashPanel.style.display  = v === 'cash' ? '' : 'none';
    splitPanel.style.display = v === 'split' ? '' : 'none';
    if (elecPanel.style.display === '') {
      populateProviders(document.getElementById('pay-provider'), v);
      document.getElementById('elec-flag').innerHTML = `<i class="fa-solid ${METHOD_META[v].icon}"></i> ${METHOD_META[v].label}`;
    }
    if (v === 'split') recomputeSplits();
    else validatePayment();
  }

  /* split tiles */
  function checkedOpts() { return splitOpts.filter(o => o.querySelector('.sp-check').checked); }
  function recomputeSplits() {
    const total = num(payAmount.value);
    const checked = checkedOpts();
    const alloc = checked.reduce((s, o) => s + num(o.querySelector('.sp-amt').value), 0);
    const remain = total - alloc;
    document.getElementById('sp-total').textContent = fmt(total);
    document.getElementById('sp-alloc').textContent = fmt(alloc);
    document.getElementById('sp-remain').textContent = (remain < 0 ? '-' : '') + fmt(Math.abs(remain));
    const banner = document.getElementById('split-banner');
    if (checked.length === 0) {
      banner.innerHTML = '<div class="form-alert info" style="margin:0"><i class="fa-solid fa-hand-pointer"></i> Tick the methods used in this split — each selected method expands its capture fields.</div>';
    } else if (Math.abs(remain) <= 0.01) {
      banner.innerHTML = `<div class="form-alert ok" style="margin:0"><i class="fa-solid fa-circle-check"></i> Fully allocated across ${checked.length} method${checked.length > 1 ? 's' : ''}.</div>`;
    } else if (remain > 0) {
      banner.innerHTML = `<div class="form-alert warn" style="margin:0"><i class="fa-solid fa-triangle-exclamation"></i> K ${fmt(remain)} still unallocated — enter amounts in the expanded method panels.</div>`;
    } else {
      banner.innerHTML = `<div class="form-alert warn" style="margin:0"><i class="fa-solid fa-triangle-exclamation"></i> Over-allocated by K ${fmt(Math.abs(remain))} — selected amounts exceed the payment.</div>`;
    }
    validatePayment();
  }
  splitOpts.forEach(opt => {
    const chk = opt.querySelector('.sp-check');
    chk.addEventListener('change', () => { opt.classList.toggle('on', chk.checked); recomputeSplits(); });
    opt.querySelector('.sp-amt').addEventListener('input', e => {
      const v = num(e.target.value);
      opt.querySelector('.sp-amt-preview').textContent = v > 0 ? fmt(v) : '';
      recomputeSplits();
    });
    const ref = opt.querySelector('.sp-ref');
    if (ref) ref.addEventListener('input', validatePayment);
  });

  function validatePayment() {
    const v = payMethod.value;
    let ok = v !== '' && num(payAmount.value) > 0;
    if (['bank','mobile','card'].includes(v)) ok = ok && document.getElementById('pay-ref').value.trim() !== '';
    if (v === 'split') {
      const checked = checkedOpts();
      const total = num(payAmount.value);
      const alloc = checked.reduce((s, o) => s + num(o.querySelector('.sp-amt').value), 0);
      ok = ok && checked.length > 0 && Math.abs(total - alloc) <= 0.01;
      checked.forEach(o => {
        if (o.dataset.method !== 'cash') {
          const r = o.querySelector('.sp-ref');
          if (r && !r.value.trim()) ok = false;
        }
      });
    }
    postBtn.disabled = !ok;
  }

  payMethod.addEventListener('change', syncMethodPanels);
  payAmount.addEventListener('input', () => { if (payMethod.value === 'split') recomputeSplits(); else validatePayment(); });
  document.getElementById('pay-ref').addEventListener('input', validatePayment);
  postBtn.addEventListener('click', () => closeModal(document.getElementById('modal-record-payment')));
</script>
</body>
</html>
@endsection
