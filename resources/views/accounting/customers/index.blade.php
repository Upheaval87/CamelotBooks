<x-app-layout>
@php $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), 'K'); @endphp

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
/* CUSTOMER INDEX MODULE CSS */

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
  body.modal-open{overflow:hidden}
  ::selection{background:var(--tint);color:var(--deep-2)}
  .glass{background:var(--card);backdrop-filter:blur(14px) saturate(1.15);-webkit-backdrop-filter:blur(14px) saturate(1.15)}
  @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  @keyframes slideRight{from{opacity:0;transform:translateX(18px)}to{opacity:1;transform:translateX(0)}}
  @keyframes panelIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
  @keyframes overlayIn{from{opacity:0}to{opacity:1}}
  @keyframes overlayOut{from{opacity:1}to{opacity:0}}
  @keyframes modalIn{from{opacity:0;transform:translateY(24px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
  @keyframes modalOut{from{opacity:1}to{opacity:0;transform:translateY(12px) scale(.97)}}
  @keyframes sheen{0%{transform:translateX(-130%) skewX(-12deg)}55%,100%{transform:translateX(320%) skewX(-12deg)}}
  @keyframes pulseMint{0%{box-shadow:0 0 0 0 rgba(127,209,192,.6)}70%{box-shadow:0 0 0 7px rgba(127,209,192,0)}100%{box-shadow:0 0 0 0 rgba(127,209,192,0)}}
  @keyframes expandIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
  .anim-1{animation:fadeUp .4s ease both;animation-delay:.05s}
  .anim-2{animation:fadeUp .4s ease both;animation-delay:.12s}
  .anim-4{animation:fadeUp .4s ease both;animation-delay:.26s}

  /* ═ CHROME ═ */
  .topnav{position:fixed;top:0;left:0;right:0;z-index:100;height:60px;display:flex;align-items:center;padding:0 24px;background:linear-gradient(180deg,var(--row1) 0%,var(--deep-2) 100%);box-shadow:0 2px 18px rgba(10,46,50,.35),inset 0 1px 0 rgba(255,255,255,.08)}
  .topnav::after{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(600px 120px at 82% 0%,rgba(127,209,192,.16),transparent 70%)}
  .topnav>*{position:relative;z-index:1}
  .topnav-brand{display:flex;align-items:center;gap:10px;padding-right:24px;border-right:1px solid rgba(255,255,255,.14);margin-right:20px;flex-shrink:0}
  .topnav-logo{width:32px;height:32px;background:linear-gradient(180deg,var(--mint),var(--sec));border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:var(--deep-3)}
  .topnav-app{font-size:14px;font-weight:700;color:#fff;letter-spacing:-.3px}
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
  .topnav-user-role{font-size:10px;color:rgba(255,255,255,.55);letter-spacing:.3px}
  .modulenav{position:fixed;top:60px;left:0;right:0;z-index:99;height:48px;background:linear-gradient(180deg,#E4EDEE,var(--row2));border-bottom:1px solid var(--line);display:flex;align-items:center;padding:0 24px 0 80px;gap:2px}
  .module-item{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;white-space:nowrap;text-decoration:none}
  .module-item i{font-size:13px;opacity:.75}
  .module-item:hover{background:rgba(255,255,255,.65);color:var(--deep-2)}
  .module-item.active{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff;font-weight:700;box-shadow:var(--cta-btn-shadow)}
  .module-item.active i{opacity:1;color:var(--mint)}
  .module-item .chevron{font-size:9px;opacity:.5;margin-left:2px}
  .sidebar{position:fixed;top:108px;left:0;bottom:0;width:60px;z-index:98;background:rgba(255,255,255,.82);backdrop-filter:blur(10px);border-right:1px solid var(--line);display:flex;flex-direction:column;align-items:center;padding:16px 0;gap:4px}
  .sidebar-item{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:10px;color:var(--ink-muted);cursor:pointer;transition:.18s;font-size:15px;position:relative;text-decoration:none}
  .sidebar-item:hover{background:var(--tint);color:var(--sec);transform:scale(1.06)}
  .sidebar-item.active{background:var(--tint);color:var(--sec)}
  .sidebar-item.active::before{content:'';position:absolute;left:-10px;width:3px;height:20px;background:linear-gradient(180deg,var(--sec),var(--mint));border-radius:0 3px 3px 0}
  .sidebar-divider{width:28px;height:1px;background:var(--line);margin:6px 0}
  .sidebar-spacer{flex:1}
  .main{margin-top:108px;margin-left:60px;padding:28px 32px 60px}
  .breadcrumb{display:flex;align-items:center;gap:6px;margin-bottom:20px;font-size:12.5px}
  .breadcrumb a{color:var(--ink-muted);text-decoration:none;font-weight:500}
  .breadcrumb a:hover{color:var(--sec)}
  .breadcrumb .sep{color:var(--border);font-size:10px}
  .breadcrumb .current{color:var(--ink);font-weight:600}
  .page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;gap:20px}
  .page-title{font-size:26px;font-weight:800;letter-spacing:-.6px;line-height:1.2;margin-bottom:6px}
  .page-subtitle{font-size:13px;color:var(--ink-soft);font-weight:500}
  .page-header-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}

  /* buttons */
  .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;border:none;text-decoration:none;font-family:'Inter',sans-serif;white-space:nowrap}
  .btn:active{transform:scale(.97)}
  .btn i{font-size:12px}
  .btn-sm{padding:7px 14px;font-size:12px}
  .btn-ghost{background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-soft)}
  .btn-ghost:hover{border-color:var(--mint);color:var(--deep-2);background:#fff;transform:translateY(-1px)}
  .btn-primary{background:linear-gradient(180deg,#14918F 0%,var(--sec) 45%,var(--sec-2) 100%);color:#fff;box-shadow:var(--teal-btn-shadow)}
  .btn-primary:hover{filter:brightness(1.07);transform:translateY(-1px)}
  .btn-deep{background:linear-gradient(180deg,var(--deep-1) 0%,var(--deep-2) 55%,var(--deep-3) 100%);color:#fff;box-shadow:var(--cta-btn-shadow)}
  .btn-deep:hover{filter:brightness(1.12);transform:translateY(-1px)}
  .btn-danger-ghost{background:var(--red-tint);border:1px solid rgba(220,38,38,.25);color:var(--red-2)}
  .btn-danger-ghost:hover{background:rgba(220,38,38,.16)}
  .btn-icon{width:38px;height:38px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:10px;background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-muted);cursor:pointer;transition:.2s;font-size:14px}
  .btn-icon:hover{border-color:var(--mint);color:var(--sec);background:#fff;transform:translateY(-1px)}

  /* cards / hairlines */
  .card{border:1px solid var(--border);border-radius:var(--r-xl);box-shadow:var(--shadow-card);overflow:hidden;position:relative;transition:.2s}
  .card:hover{box-shadow:var(--shadow-card-hov)}
  .card::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:0 0 6px 6px;background:linear-gradient(90deg,transparent 0%,var(--mint) 22%,var(--sec) 50%,var(--mint) 78%,transparent 100%);box-shadow:0 2px 10px rgba(16,124,123,.35);pointer-events:none;z-index:1}
  .card-head{display:flex;align-items:center;gap:10px;padding:16px 22px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.5)}
  .card-head-icon{width:30px;height:30px;border-radius:8px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;box-shadow:var(--teal-btn-shadow);flex-shrink:0}
  .card-title{font-size:13px;font-weight:800;color:var(--deep-2);letter-spacing:.2px}
  .card-sub{font-size:11px;color:var(--ink-muted);margin-top:1px}
  .card-head-right{margin-left:auto;display:flex;align-items:center;gap:8px}
  .card-body{padding:22px}

  /* pills */
  .pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:20px;font-size:10.5px;font-weight:700;white-space:nowrap}
  .pill .pdot{width:6px;height:6px;border-radius:50%}
  .pill-active{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .pill-active .pdot{background:var(--green)}
  .pill-inactive{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-inactive .pdot{background:#9DB4B4}
  .pill-draft{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-draft .pdot{background:#9DB4B4}
  .chip-count{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;background:var(--tint);border:1px solid rgba(16,124,123,.25);color:var(--sec)}

  /* tables */
  .tab-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap}
  .toolbar-left{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .toolbar-search{display:flex;align-items:center;gap:8px;padding:8px 12px;background:rgba(255,255,255,.9);border:1.5px solid var(--border);border-radius:8px;min-width:230px;color:var(--ink-muted);font-size:12.5px;transition:.18s}
  .toolbar-search:focus-within{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .toolbar-search input{border:none;outline:none;font-family:inherit;font-size:12.5px;color:var(--ink);width:100%;background:transparent}
  .table-wrap{border:1px solid var(--border);border-radius:12px;overflow:hidden;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card)}
  .table-scroll{overflow-x:auto}
  .data-table{width:100%;border-collapse:collapse;font-size:13px}
  .data-table thead th{background:linear-gradient(180deg,var(--mist-a),var(--mist-b));text-align:left;padding:10px 14px;font-size:10px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-soft);border-bottom:1px solid var(--line);white-space:nowrap}
  .data-table tbody td{padding:12px 14px;border-bottom:1px solid var(--line);vertical-align:middle}
  .data-table tbody tr:nth-child(even) td{background:rgba(244,248,248,.55)}
  .data-table tbody tr:last-child td{border-bottom:none}
  .data-table tbody tr{transition:background .15s}
  .data-table tbody tr:hover td{background:var(--tint)}
  .data-table tbody tr.clickable-row{cursor:pointer}
  .data-table tbody tr.clickable-row:active td{background:rgba(16,124,123,.14)}
  .data-table tbody tr.clickable-row:focus-visible{outline:2px solid var(--sec);outline-offset:-2px}
  .td-mono{font-family:'JetBrains Mono',monospace;font-size:12.5px;font-variant-numeric:tabular-nums}
  .td-right{text-align:right}
  .td-strong{font-weight:700}
  .td-red{color:var(--red-2);font-weight:800}
  .cell-name{font-size:13px;font-weight:700;color:var(--deep-2)}
  .cell-sub{font-size:10.5px;color:var(--ink-muted);margin-top:1px}
  .avatar-cell{display:flex;align-items:center;gap:12px}
  .avatar{width:38px;height:38px;border-radius:11px;background:linear-gradient(145deg,var(--tint),var(--mint));border:1px solid rgba(127,209,192,.5);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--deep-2);flex-shrink:0;position:relative}
  .avatar .astat{position:absolute;bottom:-2px;right:-2px;width:11px;height:11px;border-radius:50%;border:2px solid #fff}
  .avatar .astat.on{background:var(--green)}
  .row-link{color:var(--deep-2);font-weight:700;text-decoration:none}
  .row-link:hover{color:var(--sec);text-decoration:underline}
  .row-icon-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);cursor:pointer;font-size:11px;transition:.15s}
  .row-icon-btn:hover{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:transparent;color:#fff;transform:scale(1.1);box-shadow:var(--teal-btn-shadow)}
  .table-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--line);font-size:12px;color:var(--ink-soft);background:rgba(255,255,255,.6)}
  .pager{display:flex;gap:4px}
  .pager-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);font-size:11px;cursor:pointer;transition:.15s}
  .pager-btn.active{background:linear-gradient(180deg,var(--deep-1),var(--deep-2));color:#fff;border-color:transparent}
  .pager-btn:hover:not(.active){background:var(--tint)}

  /* portfolio strip */
  .portfolio-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}
  .pf-card{border:1px solid var(--border);border-radius:var(--r-md);padding:16px 18px;background:rgba(255,255,255,.7);box-shadow:var(--shadow-card);position:relative;overflow:hidden;transition:.22s}
  .pf-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-card-hov)}
  .pf-card.hl{background:linear-gradient(135deg,var(--sec) 0%,var(--sec-2) 55%,var(--deep-1) 135%);border-color:rgba(16,124,123,.6)}
  .pf-card.hl::before{content:'';position:absolute;top:0;bottom:0;left:0;width:38%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.20),transparent);animation:sheen 4.2s ease-in-out infinite}
  .pf-card.hl .pf-label,.pf-card.hl .pf-sub{color:rgba(255,255,255,.72)}
  .pf-card.hl .pf-value{color:#fff}
  .pf-card.hl .pf-icon{background:rgba(255,255,255,.16);color:#fff}
  .pf-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  .pf-label{font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--ink-muted)}
  .pf-icon{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;background:var(--mist-b);color:var(--ink-soft)}
  .pf-value{font-size:22px;font-weight:800;letter-spacing:-.5px;font-variant-numeric:tabular-nums}
  .pf-value .cur{font-size:13px;font-weight:700;opacity:.55;margin-right:2px}
  .pf-sub{font-size:11px;color:var(--ink-soft);font-weight:500;margin-top:2px}

  /* ageing strip */
  .ageing{display:grid;grid-template-columns:repeat(5,1fr);gap:0;border:1px solid var(--border);border-radius:var(--r-md);overflow:hidden;background:rgba(255,255,255,.7);box-shadow:var(--shadow-card);margin-bottom:18px}
  .ag-cell{padding:13px 16px;border-right:1px solid var(--line);position:relative}
  .ag-cell:last-child{border-right:none}
  .ag-cell.hot{background:linear-gradient(180deg,var(--red-tint),rgba(255,255,255,.4))}
  .ag-label{font-size:9.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:5px;display:flex;align-items:center;gap:6px}
  .ag-label .agdot{width:7px;height:7px;border-radius:50%}
  .ag-val{font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:800;color:var(--deep-2)}
  .ag-cell.hot .ag-val{color:var(--red-2)}
  .ag-bar{height:4px;border-radius:2px;background:var(--mist-b);margin-top:8px;overflow:hidden}
  .ag-fill{height:100%;border-radius:2px}

  /* side panel */
  .idx-grid{display:grid;grid-template-columns:1fr 240px;gap:20px;align-items:start}
  .side-panel{display:flex;flex-direction:column;gap:16px}
  .side-card{border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--shadow-card);overflow:hidden;transition:.2s}
  .side-card:hover{box-shadow:var(--shadow-card-hov)}
  .side-card-header{padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.5)}
  .side-card-title{font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--deep-2)}
  .side-list{display:flex;flex-direction:column;gap:2px;padding:10px}
  .side-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;font-size:12.5px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;text-decoration:none}
  .side-link:hover{background:var(--tint);color:var(--deep-2)}
  .side-link.active{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff;font-weight:700;box-shadow:var(--cta-btn-shadow)}
  .side-link.active i{color:var(--mint)}
  .side-link i{width:18px;text-align:center;font-size:12px;opacity:.8}
  .side-link .cnt{margin-left:auto;font-size:10px;font-weight:800;background:rgba(255,255,255,.14);padding:1px 7px;border-radius:10px}
  .side-link:not(.active) .cnt{background:var(--mist-b);color:var(--ink-soft)}

  [data-tip]{position:relative}
  [data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--deep-2);color:#fff;font-size:11px;font-weight:500;padding:4px 9px;border-radius:6px;white-space:nowrap;pointer-events:none;z-index:200;animation:fadeIn .15s ease both;box-shadow:var(--cta-btn-shadow)}
  ::-webkit-scrollbar{width:5px;height:5px}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
  ::-webkit-scrollbar-thumb:hover{background:var(--sec)}

  /* modals */
  .modal-overlay{position:fixed;inset:0;background:rgba(10,46,50,.55);backdrop-filter:blur(5px);z-index:300;display:none;align-items:flex-start;justify-content:center;padding:48px 20px;overflow-y:auto}
  .modal-overlay.open{display:flex;animation:overlayIn .22s ease both}
  .modal-overlay.closing{animation:overlayOut .18s ease both}
  .modal{background:#fff;border-radius:16px;width:100%;max-width:560px;box-shadow:0 24px 70px -18px rgba(10,46,50,.5);animation:modalIn .3s cubic-bezier(.2,.9,.3,1.15) both;overflow:hidden}
  .modal-overlay.closing .modal{animation:modalOut .18s ease both}
  .modal.wide{max-width:840px}
  .modal.mid{max-width:780px}
  @media (max-width:840px){ .modal.mid{max-width:100%} }
  .modal-header{display:flex;align-items:center;gap:14px;padding:18px 24px;position:relative;overflow:hidden;background:radial-gradient(120% 150% at 88% -30%, rgba(127,209,192,.28), transparent 55%),linear-gradient(135deg,var(--deep-2) 0%,var(--deep-3) 100%)}
  .modal-header::after{content:'';position:absolute;left:0;right:0;bottom:0;height:1px;background:linear-gradient(90deg,var(--mint),transparent 60%)}
  .modal-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;box-shadow:var(--teal-btn-shadow)}
  .modal-head-text{flex:1}
  .modal-title{font-size:16px;font-weight:800;color:#fff}
  .modal-subtitle{font-size:12px;color:rgba(255,255,255,.62);margin-top:2px}
  .modal-close{width:32px;height:32px;border-radius:8px;border:none;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;font-size:13px;transition:.18s;flex-shrink:0}
  .modal-close:hover{background:rgba(255,255,255,.22);transform:rotate(90deg)}
  .modal-body{padding:24px;max-height:calc(100vh - 300px);overflow-y:auto}
  .modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--line);background:linear-gradient(180deg,var(--mist-a),var(--mist-b))}
  .modal-footer .spacer{margin-right:auto}

  /* forms */
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 16px}
  .form-grid .full{grid-column:1/-1}
  .form-field label{display:block;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:6px}
  .form-field label .req{color:var(--red)}
  .form-control{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px;color:var(--ink);background:#fff;outline:none;transition:.15s}
  .form-control:focus{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .form-control:disabled{background:var(--mist-a);color:var(--ink-muted)}
  textarea.form-control{resize:vertical;min-height:84px}
  .form-section-title{font-size:10.5px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--deep-2);margin:22px 0 12px;display:flex;align-items:center;gap:8px}
  .form-section-title::after{content:'';flex:1;height:1px;background:var(--line)}
  .form-section-title:first-child{margin-top:0}
  .switch{position:relative;width:40px;height:22px;display:inline-block;flex-shrink:0}
  .switch input{opacity:0;width:0;height:0}
  .switch-slider{position:absolute;inset:0;background:var(--border);border-radius:20px;cursor:pointer;transition:.2s}
  .switch-slider::before{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .switch input:checked + .switch-slider{background:linear-gradient(90deg,var(--sec),var(--sec-2))}
  .switch input:checked + .switch-slider::before{transform:translateX(18px)}
  .switch-row{display:flex;align-items:center;gap:10px}
  .switch-row-label{font-size:12.5px;font-weight:600}
  .switch-row-sub{font-size:11px;color:var(--ink-muted)}

</style>
<main class="main" style="margin-top:0 !important;margin-left:0 !important;padding:20px 24px 40px !important;max-width:100% !important;width:100% !important;">


  <div class="breadcrumb anim-1">
    <a href="#">Sales</a><i class="fa-solid fa-chevron-right sep"></i>
    <span class="current">Customers</span>
  </div>

  <div class="page-header anim-1">
    <div>
      <h1 class="page-title">Customers</h1>
      <div class="page-subtitle">Manage customer records, terms and balances.</div>
    </div>
    <div class="page-header-actions">
      <button class="btn btn-ghost"><i class="fa-solid fa-download"></i> Export</button>
      <button class="btn btn-deep" onclick="openModal('modal-create-customer')"><i class="fa-solid fa-plus"></i> Create Customer</button>
    </div>
  </div>

  <div class="idx-grid anim-2">
    <div style="display:flex;flex-direction:column;gap:0">

      <!-- portfolio -->
      <div class="card glass" style="margin-bottom:18px">
        <div class="card-head">
          <div class="card-head-icon"><i class="fa-solid fa-users"></i></div>
          <div><div class="card-title">Portfolio</div><div class="card-sub">Directory snapshot · Oct 2026</div></div>
          <div class="card-head-right"><span class="chip-count"><i class="fa-solid fa-circle-check" style="font-size:9px"></i> Synced just now</span></div>
        </div>
        <div class="card-body">
          <div class="portfolio-strip" style="margin-bottom:0">
            <div class="pf-card">
              <div class="pf-top"><span class="pf-label">Customers</span><div class="pf-icon"><i class="fa-solid fa-user-group"></i></div></div>
              <div class="pf-value">6</div>
              <div class="pf-sub">All time created</div>
            </div>
            <div class="pf-card">
              <div class="pf-top"><span class="pf-label">Active</span><div class="pf-icon"><i class="fa-solid fa-circle-check"></i></div></div>
              <div class="pf-value">6</div>
              <div class="pf-sub">None archived</div>
            </div>
            <div class="pf-card hl">
              <div class="pf-top"><span class="pf-label">Outstanding (K)</span><div class="pf-icon" style="font-weight:800;font-size:14px">K</div></div>
              <div class="pf-value"><span class="cur">K</span>8,178.30</div>
              <div class="pf-sub">Across 3 open invoices</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ageing -->
      <div class="card glass" style="margin-bottom:18px">
        <div class="card-head">
          <div class="card-head-icon" style="background:linear-gradient(180deg,#E8930C,var(--warn))"><i class="fa-solid fa-hourglass-half"></i></div>
          <div><div class="card-title">Receivables Ageing</div><div class="card-sub">Open invoice balance by bucket · drill-down ready</div></div>
          <div class="card-head-right"><span class="pill" style="background:var(--red-tint);color:var(--red-2);border:1px solid rgba(220,38,38,.25)"><span class="pdot" style="background:var(--red)"></span> {{ $cs }} 8,178.30 overdue 30d+</span></div>
        </div>
        <div class="card-body" style="padding:0">
          <div class="ageing" style="border:none;border-radius:0;box-shadow:none;margin:0">
            <div class="ag-cell">
              <div class="ag-label"><span class="agdot" style="background:var(--green)"></span> Current</div>
              <div class="ag-val">0.00</div>
              <div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--green)"></div></div>
            </div>
            <div class="ag-cell">
              <div class="ag-label"><span class="agdot" style="background:var(--sec)"></span> 1–30 Days</div>
              <div class="ag-val">0.00</div>
              <div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--sec)"></div></div>
            </div>
            <div class="ag-cell">
              <div class="ag-label"><span class="agdot" style="background:var(--warn)"></span> 31–60 Days</div>
              <div class="ag-val">0.00</div>
              <div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--warn)"></div></div>
            </div>
            <div class="ag-cell hot">
              <div class="ag-label"><span class="agdot" style="background:#EA580C"></span> 61–90 Days</div>
              <div class="ag-val">8,178.30</div>
              <div class="ag-bar"><div class="ag-fill" style="width:100%;background:linear-gradient(90deg,#EA580C,var(--red))"></div></div>
            </div>
            <div class="ag-cell">
              <div class="ag-label"><span class="agdot" style="background:var(--red)"></span> 90+ Days</div>
              <div class="ag-val">0.00</div>
              <div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--red)"></div></div>
            </div>
          </div>
        </div>
      </div>

      <!-- filters + table -->
      <div class="card glass">
        <div class="card-body">
          <div class="tab-toolbar" style="margin-bottom:14px">
            <div class="toolbar-left">
              <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="Name or email…"></div>
              <select class="btn btn-ghost btn-sm" style="appearance:auto;-webkit-appearance:auto"><option>All Statuses</option><option>Active</option><option>Inactive</option></select>
              <select class="btn btn-ghost btn-sm" style="appearance:auto;-webkit-appearance:auto"><option>All Terms</option><option>Net 30</option><option>Due on receipt</option></select>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
            </div>
            <span class="chip-count">6 customers</span>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Terms</th><th class="td-right">Balance (K)</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='{{ route('accounting.customers.show', 2) }}'">
                    <td><div class="avatar-cell"><div class="avatar">JB<span class="astat on"></span></div><div><div class="cell-name">John Banda</div><div class="cell-sub">Since Jul 2026</div></div></div></td>
                    <td>john@example.com</td><td class="td-mono">+265 991 234 …</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-red">8,178.30</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" data-tip="View" onclick="event.stopPropagation();window.location.href='{{ route('accounting.customers.show', 2) }}'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" data-tip="Edit" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" data-tip="Archive" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='{{ route('accounting.customers.show', 3) }}'">
                    <td><div class="avatar-cell"><div class="avatar">MP<span class="astat on"></span></div><div><div class="cell-name">Mary Phiri</div><div class="cell-sub">Since Jul 2026</div></div></div></td>
                    <td>mary@example.com</td><td class="td-mono">+265 882 345 …</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-strong">0.00</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" onclick="event.stopPropagation();window.location.href='{{ route('accounting.customers.show', 3) }}'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='{{ route('accounting.customers.show', 4) }}'">
                    <td><div class="avatar-cell"><div class="avatar">PM<span class="astat on"></span></div><div><div class="cell-name">Peter Mwangonde</div><div class="cell-sub">Since Jul 2026</div></div></div></td>
                    <td>peter@example.com</td><td class="td-mono">+265 773 456 …</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-strong">0.00</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" onclick="event.stopPropagation();window.location.href='{{ route('accounting.customers.show', 4) }}'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='{{ route('accounting.customers.show', 5) }}'">
                    <td><div class="avatar-cell"><div class="avatar">ES<span class="astat on"></span></div><div><div class="cell-name">Elvis Seyama</div><div class="cell-sub">Since Jul 2026</div></div></div></td>
                    <td>elvis.seyama@gmail.com</td><td class="td-mono">0999602605</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-strong">0.00</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" onclick="event.stopPropagation();window.location.href='{{ route('accounting.customers.show', 5) }}'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='{{ route('accounting.customers.show', 6) }}'">
                    <td><div class="avatar-cell"><div class="avatar">PC<span class="astat on"></span></div><div><div class="cell-name">Probe Customer…</div><div class="cell-sub">Since Aug 2026</div></div></div></td>
                    <td>probe@customer.test</td><td class="td-mono">+1 555 0100</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-strong">0.00</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" onclick="event.stopPropagation();window.location.href='{{ route('accounting.customers.show', 6) }}'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='{{ route('accounting.customers.show', 1) }}'">
                    <td><div class="avatar-cell"><div class="avatar">WC<span class="astat on"></span></div><div><div class="cell-name">Walk-in Customer</div><div class="cell-sub">Since Jul 2026</div></div></div></td>
                    <td class="td-mono" style="color:#9DB4B4">—</td><td class="td-mono" style="color:#9DB4B4">—</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-strong">0.00</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" onclick="event.stopPropagation();window.location.href='{{ route('accounting.customers.show', 1) }}'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="table-footer"><span>Showing 1–6 of 6 customers</span><div class="pager"><button class="pager-btn"><i class="fa-solid fa-chevron-left"></i></button><button class="pager-btn active">1</button><button class="pager-btn"><i class="fa-solid fa-chevron-right"></i></button></div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- right rail -->
    <div class="side-panel">
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Views</span></div>
        <div class="side-list">
          <a class="side-link active"><i class="fa-solid fa-users"></i> All Customers <span class="cnt">6</span></a>
          <a class="side-link"><i class="fa-solid fa-circle-check"></i> Active <span class="cnt">6</span></a>
          <a class="side-link"><i class="fa-solid fa-circle-minus"></i> Inactive <span class="cnt">0</span></a>
          <a class="side-link"><i class="fa-solid fa-triangle-exclamation"></i> With Balance <span class="cnt">1</span></a>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Reports</span></div>
        <div class="side-list">
          <a class="side-link"><i class="fa-solid fa-dollar-sign"></i> Customer Balances</a>
          <a class="side-link"><i class="fa-solid fa-chart-pie"></i> Sales by Customer</a>
          <a class="side-link"><i class="fa-solid fa-hourglass-half"></i> Ageing Report</a>
          <a class="side-link"><i class="fa-solid fa-file-invoice"></i> Statement Batch</a>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- ═══════ MODALS ═══════ -->

<!-- CREATE CUSTOMER -->
<div class="modal-overlay" id="modal-create-customer">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-user-plus"></i></div>
      <div class="modal-head-text"><div class="modal-title">Create Customer</div><div class="modal-subtitle">Add a new customer record to the directory</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">General Information</div>
      <div class="form-grid">
        <div class="form-field"><label>Display Name <span class="req">*</span></label><input class="form-control" placeholder="e.g. John Banda"></div>
        <div class="form-field"><label>Legal Name <span class="req">*</span></label><input class="form-control" placeholder="Registered company name"></div>
        <div class="form-field"><label>Customer Code</label><input class="form-control" value="CUS-AUTO" disabled></div>
        <div class="form-field"><label>Category</label><select class="form-control"><option>Retail</option><option>Wholesale</option><option>Corporate</option><option>Government</option></select></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Tax ID / VAT No.</label><input class="form-control" placeholder="TPIN / VAT Registration"></div>
      </div>
      <div class="form-section-title">Contact Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Email Address</label><input class="form-control" type="email" placeholder="name@company.com"></div>
        <div class="form-field"><label>Phone Number</label><input class="form-control" placeholder="+260 ..."></div>
        <div class="form-field full"><label>Website</label><input class="form-control" placeholder="https://..."></div>
      </div>
      <div class="form-section-title">Billing & Shipping</div>
      <div class="form-grid">
        <div class="form-field full"><label>Billing Address</label><textarea class="form-control" placeholder="Street, City, Country..."></textarea></div>
        <div class="form-field full"><label>Shipping Address</label><textarea class="form-control" placeholder="Same as billing? Leave blank."></textarea></div>
      </div>
      <div class="form-section-title">Financial Settings</div>
      <div class="form-grid">
        <div class="form-field"><label>Payment Terms (Days)</label><input class="form-control" type="number" value="30"></div>
        <div class="form-field"><label>Credit Limit</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Opening Balance</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Preferred Method</label><select class="form-control"><option>Bank Transfer</option><option>Cheque</option><option>Cash</option></select></div>
        <div class="form-field full" style="display:flex;align-items:center;gap:10px">
          <label class="switch"><input type="checkbox" checked><span class="switch-slider"></span></label>
          <div><div class="switch-row-label">Active Immediately</div><div class="switch-row-sub">Enable this customer for transactions upon creation</div></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Create Customer</button>
    </div>
  </div>
</div>

<!-- EDIT CUSTOMER -->
<div class="modal-overlay" id="modal-edit-customer">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-pen-to-square"></i></div>
      <div class="modal-head-text"><div class="modal-title">Edit Customer</div><div class="modal-subtitle">Update master data for CUS-00002</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">General Information</div>
      <div class="form-grid">
        <div class="form-field"><label>Display Name <span class="req">*</span></label><input class="form-control" value="John Banda"></div>
        <div class="form-field"><label>Legal Name <span class="req">*</span></label><input class="form-control" value="John Banda Enterprises Ltd"></div>
        <div class="form-field"><label>Customer Code</label><input class="form-control" value="CUS-00002" disabled></div>
        <div class="form-field"><label>Category</label><select class="form-control"><option selected>Retail</option><option>Wholesale</option><option>Corporate</option></select></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option selected>$ — US Dollar</option><option>K — Kwacha</option></select></div>
        <div class="form-field"><label>Tax ID / VAT No.</label><input class="form-control" value="1002345678"></div>
      </div>
      <div class="form-section-title">Contact Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Email Address</label><input class="form-control" type="email" value="john@example.com"></div>
        <div class="form-field"><label>Phone Number</label><input class="form-control" value="+265 991 234 567"></div>
        <div class="form-field full"><label>Website</label><input class="form-control" value=""></div>
      </div>
      <div class="form-section-title">Billing & Shipping</div>
      <div class="form-grid">
        <div class="form-field full"><label>Billing Address</label><textarea class="form-control">Plot 123, Cairo Road, Lusaka, Zambia</textarea></div>
        <div class="form-field full"><label>Shipping Address</label><textarea class="form-control">Plot 123, Cairo Road, Lusaka, Zambia</textarea></div>
      </div>
      <div class="form-section-title">Financial Settings</div>
      <div class="form-grid">
        <div class="form-field"><label>Payment Terms (Days)</label><input class="form-control" type="number" value="30"></div>
        <div class="form-field"><label>Credit Limit</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Opening Balance</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Preferred Method</label><select class="form-control"><option selected>Bank Transfer</option><option>Cheque</option><option>Cash</option></select></div>
        <div class="form-field full" style="display:flex;align-items:center;gap:10px">
          <label class="switch"><input type="checkbox" checked><span class="switch-slider"></span></label>
          <div><div class="switch-row-label">Customer is Active</div><div class="switch-row-sub">Currently active — toggle to archive/deactivate</div></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger-ghost spacer"><i class="fa-solid fa-trash-can"></i> Delete Customer</button>
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Save Changes</button>
    </div>
  </div>
</div>

<!-- ARCHIVE CONFIRM -->
<div class="modal-overlay" id="modal-archive-confirm">
  <div class="modal" style="max-width:440px">
    <div class="modal-header" style="background:linear-gradient(135deg,#991B1B,#7F1D1D)">
      <div class="modal-icon" style="background:rgba(255,255,255,.15)"><i class="fa-solid fa-box-archive"></i></div>
      <div class="modal-head-text"><div class="modal-title">Archive Customer?</div><div class="modal-subtitle">John Banda (CUS-00002)</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;line-height:1.6;color:var(--ink-soft);margin-bottom:16px">Archiving will prevent new invoices, orders, and payments from being recorded against this customer. Existing transaction history will remain intact.</p>
      <div style="background:var(--red-tint);border:1px solid rgba(220,38,38,.2);border-radius:8px;padding:12px;font-size:12px;color:var(--red-2);font-weight:600;display:flex;gap:8px;align-items:flex-start">
        <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px"></i>
        <span>This customer currently has an outstanding balance of <strong>{{ $cs }} 8,178.30</strong>. Ensure all dues are settled before archiving.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-danger-ghost" style="background:var(--red);color:#fff;border:none"><i class="fa-solid fa-check"></i> Yes, Archive</button>
    </div>
  </div>
</div>

<script>
/* modals */
const openModal = id => { const ov = document.getElementById(id); ov.classList.remove('closing'); ov.classList.add('open'); document.body.classList.add('modal-open'); };
const closeModal = ov => { ov.classList.add('closing'); setTimeout(() => { ov.classList.remove('open','closing'); document.body.classList.remove('modal-open'); }, 180); };
document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => closeModal(b.closest('.modal-overlay'))));
document.querySelectorAll('.modal-overlay').forEach(ov => {
  ov.addEventListener('mousedown', e => { if (e.target === ov) closeModal(ov); });
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(closeModal); });

/* sidebar */
document.querySelectorAll('.sidebar-item').forEach(item=>{
  item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.sidebar-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');});
});
</script>


<script>
/* modals */
const openModal = id => { const ov = document.getElementById(id); ov.classList.remove('closing'); ov.classList.add('open'); document.body.classList.add('modal-open'); };
const closeModal = ov => { ov.classList.add('closing'); setTimeout(() => { ov.classList.remove('open','closing'); document.body.classList.remove('modal-open'); }, 180); };
document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => closeModal(b.closest('.modal-overlay'))));
document.querySelectorAll('.modal-overlay').forEach(ov => {
  ov.addEventListener('mousedown', e => { if (e.target === ov) closeModal(ov); });
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(closeModal); });

/* sidebar */
document.querySelectorAll('.sidebar-item').forEach(item=>{
  item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.sidebar-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');});
});
</script>
</x-app-layout>
