<x-app-layout>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<style>
/* VENDOR INDEX MODULE CSS */

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
  @keyframes sheen{0%{transform:translateX(-130%) skewX(-12deg)}55%,100%{transform:translateX(320%) skewX(-12deg)}}
  @keyframes pulseMint{0%{box-shadow:0 0 0 0 rgba(127,209,192,.6)}70%{box-shadow:0 0 0 7px rgba(127,209,192,0)}100%{box-shadow:0 0 0 0 rgba(127,209,192,0)}}
  @keyframes barIn{from{transform:scaleY(.2);opacity:0}to{transform:scaleY(1);opacity:1}}
  @keyframes slideDown{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
  @keyframes overlayIn{from{opacity:0}to{opacity:1}}
  @keyframes overlayOut{from{opacity:1}to{opacity:0}}
  @keyframes modalIn{from{opacity:0;transform:translateY(24px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
  @keyframes modalOut{from{opacity:1}to{opacity:0;transform:translateY(12px) scale(.97)}}
  @keyframes toastIn{from{opacity:0;transform:translateY(12px) scale(.96)}to{opacity:1;transform:none}}
  .anim-1{animation:fadeUp .4s ease both;animation-delay:.05s}
  .anim-2{animation:fadeUp .4s ease both;animation-delay:.12s}
  .anim-3{animation:fadeUp .4s ease both;animation-delay:.19s}

  /* ═ CHROME  */
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
  .btn-deep{background:linear-gradient(180deg,var(--deep-1) 0%,var(--deep-2) 55%,var(--deep-3) 100%);color:#fff;box-shadow:var(--cta-btn-shadow)}
  .btn-deep:hover{filter:brightness(1.12);transform:translateY(-1px)}
  .btn-danger-ghost{background:var(--red-tint);border:1px solid rgba(220,38,38,.25);color:var(--red-2)}
  .btn-danger-ghost:hover{background:rgba(220,38,38,.16)}

  /* cards / hairlines — BOTTOM, FAINT */
  .card{border:1px solid var(--border);border-radius:var(--r-xl);box-shadow:var(--shadow-card);overflow:hidden;position:relative;transition:.2s}
  .card:hover{box-shadow:var(--shadow-card-hov)}
  .card::before{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:6px 6px 0 0;background:linear-gradient(90deg,transparent 0%,rgba(127,209,192,.35) 22%,rgba(16,124,123,.45) 50%,rgba(127,209,192,.35) 78%,transparent 100%);box-shadow:0 -1px 6px rgba(16,124,123,.12);pointer-events:none;z-index:1}
  .card-head{display:flex;align-items:center;gap:10px;padding:16px 22px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.5)}
  .card-head-icon{width:30px;height:30px;border-radius:8px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;box-shadow:var(--teal-btn-shadow);flex-shrink:0}
  .card-title{font-size:13px;font-weight:800;color:var(--deep-2);letter-spacing:.2px}
  .card-sub{font-size:11px;color:var(--ink-muted);margin-top:1px}
  .card-head-right{margin-left:auto;display:flex;align-items:center;gap:8px}
  .card-body{padding:22px}

  /* pills / chips */
  .pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:20px;font-size:10.5px;font-weight:700;white-space:nowrap}
  .pill .pdot{width:6px;height:6px;border-radius:50%}
  .pill-active{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .pill-active .pdot{background:var(--green)}
  .pill-inactive{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-inactive .pdot{background:#9DB4B4}
  .chip-count{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;background:var(--tint);border:1px solid rgba(16,124,123,.25);color:var(--sec)}

  /* ═ KPI TILES ═ */
  .kpi-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:18px}
  .kpi{border:1px solid var(--border);border-radius:var(--r-md);padding:12px 16px;background:rgba(255,255,255,.72);box-shadow:var(--shadow-card);position:relative;overflow:hidden;transition:.22s;cursor:pointer;display:flex;align-items:center;gap:12px}
  .kpi:hover{transform:translateY(-2px);box-shadow:var(--shadow-card-hov);border-color:var(--mint)}
  .kpi.active{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring),var(--shadow-card)}
  .kpi.hl{background:linear-gradient(135deg,var(--sec) 0%,var(--sec-2) 55%,var(--deep-1) 135%);border-color:rgba(16,124,123,.6)}
  .kpi.hl::before{content:'';position:absolute;top:0;bottom:0;left:0;width:38%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.20),transparent);animation:sheen 4.2s ease-in-out infinite}
  .kpi.hl .kpi-label,.kpi.hl .kpi-sub{color:rgba(255,255,255,.72)}
  .kpi.hl .kpi-value{color:#fff}
  .kpi.hl .kpi-icon{background:rgba(255,255,255,.16);color:#fff}
  .kpi-icon{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;background:var(--mist-b);color:var(--ink-soft);flex-shrink:0}
  .kpi-icon.teal{background:var(--tint);color:var(--sec)}
  .kpi-icon.red{background:var(--red-tint);color:var(--red)}
  .kpi-text{display:flex;flex-direction:column;gap:2px;min-width:0}
  .kpi-label{font-size:9.5px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-muted)}
  .kpi-value{font-size:16px;font-weight:800;letter-spacing:-.5px;font-variant-numeric:tabular-nums;line-height:1}
  .kpi-sub{font-size:9px;color:var(--ink-soft);font-weight:500;margin-top:1px}

  /* ═ AGEING ═ */
  .ageing{display:grid;grid-template-columns:repeat(5,1fr);gap:0;border:1px solid var(--border);border-radius:var(--r-md);overflow:hidden;background:rgba(255,255,255,.7);box-shadow:var(--shadow-card);margin-bottom:18px}
  .ag-cell{padding:13px 16px;border-right:1px solid var(--line);position:relative;cursor:pointer;transition:.18s}
  .ag-cell:last-child{border-right:none}
  .ag-cell:hover{background:var(--tint)}
  .ag-cell.hot{background:linear-gradient(180deg,var(--red-tint),rgba(255,255,255,.4))}
  .ag-cell.sel{box-shadow:inset 0 0 0 2px var(--sec)}
  .ag-label{font-size:9.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:5px;display:flex;align-items:center;gap:6px}
  .ag-label .agdot{width:7px;height:7px;border-radius:50%}
  .ag-val{font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:800;color:var(--deep-2)}
  .ag-cell.hot .ag-val{color:var(--red-2)}
  .ag-bar{height:4px;border-radius:2px;background:var(--mist-b);margin-top:8px;overflow:hidden}
  .ag-fill{height:100%;border-radius:2px;transform-origin:left;animation:barIn .6s ease both}
  .ageing-clear{display:none;align-items:center;gap:6px;padding:5px 12px;border-radius:8px;border:1px solid rgba(220,38,38,.3);background:var(--red-tint);color:var(--red-2);font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:.15s}
  .ageing-clear:hover{background:rgba(220,38,38,.18)}
  .ageing-clear.show{display:inline-flex}
  .active-filters{display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap}
  .active-filters:empty{display:none;margin:0}
  .filter-chip{display:inline-flex;align-items:center;gap:8px;padding:5px 8px 5px 12px;border-radius:20px;background:var(--warn-tint);border:1px solid rgba(217,119,6,.35);color:var(--warn);font-size:11.5px;font-weight:700}
  .filter-chip b{font-weight:800}
  .filter-chip button{width:18px;height:18px;border-radius:50%;border:none;background:rgba(217,119,6,.2);color:var(--warn);cursor:pointer;font-size:9px;display:flex;align-items:center;justify-content:center;transition:.15s}
  .filter-chip button:hover{background:var(--warn);color:#fff}
  .filter-chip-label{font-size:11px;font-weight:700;color:var(--ink-muted);text-transform:uppercase;letter-spacing:.5px}

  /* ═ TOOLBAR ═ */
  .toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
  .toolbar-search{display:flex;align-items:center;gap:8px;padding:4px 8px;background:rgba(255,255,255,.9);border:1.5px solid var(--border);border-radius:9px;flex:1;min-width:240px;color:var(--ink-muted);font-size:12.5px;transition:.18s;height:32px;box-sizing:border-box}
  .toolbar-search:focus-within{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .toolbar-search input{border:none;outline:none;font-family:inherit;font-size:12.5px;color:var(--ink);width:100%;background:transparent;height:22px;padding:0}
  .seg{display:inline-flex;background:var(--mist-b);border:1px solid var(--border);border-radius:9px;padding:3px;gap:2px}
  .seg-btn{padding:6px 13px;border:none;border-radius:7px;background:transparent;font-family:inherit;font-size:11.5px;font-weight:700;color:var(--ink-soft);cursor:pointer;transition:.18s;display:flex;align-items:center;gap:5px}
  .seg-btn:hover{color:var(--ink)}
  .seg-btn.active{background:#fff;color:var(--deep-2);box-shadow:0 1px 3px rgba(10,42,46,.12)}
  .seg-btn .n{font-size:10px;background:var(--mist-b);padding:0 6px;border-radius:8px;color:var(--ink-soft)}
  .seg-btn.active .n{background:var(--tint);color:var(--sec)}

  /* ═ TABLE ═ */
  .table-wrap{border:1px solid var(--border);border-radius:14px;overflow:hidden;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card)}
  .table-scroll{overflow-x:auto}
  .data-table{width:100%;border-collapse:collapse;font-size:13px}
  .data-table thead th{background:linear-gradient(180deg,var(--mist-a),var(--mist-b));text-align:left;padding:11px 14px;font-size:9.5px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-soft);border-bottom:1px solid var(--line);white-space:nowrap;cursor:pointer;user-select:none;transition:.15s}
  .data-table thead th:hover{color:var(--deep-2)}
  .data-table thead th .sort{opacity:.35;margin-left:4px;font-size:9px}
  .data-table thead th.sorted .sort{opacity:1;color:var(--sec)}
  .data-table tbody td{padding:13px 14px;border-bottom:1px solid var(--line);vertical-align:middle}
  .data-table tbody tr:nth-child(even) td{background:rgba(244,248,248,.5)}
  .data-table tbody tr:last-child td{border-bottom:none}
  .data-table tbody tr{transition:background .15s}
  .data-table tbody tr.clickable-row{cursor:pointer}
  .data-table tbody tr.clickable-row:active td{background:rgba(16,124,123,.14)}
  .data-table tbody tr.clickable-row:focus-visible{outline:2px solid var(--sec);outline-offset:-2px}
  .data-table tbody tr.selected td{background:rgba(16,124,123,.10)!important}
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
  .avatar .astat.off{background:#9DB4B4}
  .terms-pill{display:inline-flex;padding:3px 10px;border-radius:8px;font-size:10.5px;font-weight:700;background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .star-btn{background:none;border:none;color:var(--border);cursor:pointer;font-size:13px;transition:.15s;padding:2px}
  .star-btn:hover{color:var(--warn);transform:scale(1.15)}
  .star-btn.on{color:var(--warn)}
  .row-check{width:16px;height:16px;accent-color:var(--sec);cursor:pointer}
  .row-icon-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);cursor:pointer;font-size:11px;transition:.15s}
  .row-icon-btn:hover{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:transparent;color:#fff;transform:scale(1.1);box-shadow:var(--teal-btn-shadow)}
  .table-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--line);font-size:12px;color:var(--ink-soft);background:rgba(255,255,255,.6)}
  .pager{display:flex;gap:4px}
  .pager-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);font-size:11px;cursor:pointer;transition:.15s}
  .pager-btn.active{background:linear-gradient(180deg,var(--deep-1),var(--deep-2));color:#fff;border-color:transparent}
  .pager-btn:hover:not(.active){background:var(--tint)}
  .empty-rows{padding:48px 20px;text-align:center;color:var(--ink-muted);font-size:13px}

  /* ═ BULK BAR ═ */
  .bulk-bar{position:sticky;top:116px;z-index:50;display:none;align-items:center;gap:14px;padding:11px 18px;border-radius:12px;background:linear-gradient(135deg,var(--deep-2),var(--deep-3));color:#fff;box-shadow:var(--cta-btn-shadow);margin-bottom:14px;animation:slideDown .25s ease both}
  .bulk-bar.show{display:flex}
  .bulk-count{font-size:12.5px;font-weight:700;display:flex;align-items:center;gap:8px}
  .bulk-count b{background:var(--sec);color:#fff;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px}
  .bulk-actions{display:flex;gap:8px;margin-left:auto}
  .bulk-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:8px;font-size:11.5px;font-weight:700;cursor:pointer;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08);color:#fff;font-family:inherit;transition:.15s}
  .bulk-btn:hover{background:rgba(255,255,255,.16)}
  .bulk-btn.primary{background:var(--sec);border-color:transparent}
  .bulk-clear{background:none;border:none;color:rgba(255,255,255,.6);cursor:pointer;font-size:11px;font-weight:600;font-family:inherit}
  .bulk-clear:hover{color:#fff}

  /* ═ LAYOUT ═ */
  .idx-grid{display:grid;grid-template-columns:1fr;gap:20px;align-items:start}
  .side-panel{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .side-panel .side-card{animation:slideRight .45s ease both}
  .side-panel .side-card:nth-child(2){animation-delay:.1s}
  .side-card{border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--shadow-card);overflow:hidden;transition:.2s}
  .side-card:hover{box-shadow:var(--shadow-card-hov)}
  .side-card-header{padding:15px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.5)}
  .side-card-title{font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--deep-2)}
  .side-list{display:flex;flex-direction:column;gap:2px;padding:10px}
  .side-link{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;font-size:12.5px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;text-decoration:none}
  .side-link:hover{background:var(--tint);color:var(--deep-2)}
  .side-link.active{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff;font-weight:700;box-shadow:var(--cta-btn-shadow)}
  .side-link.active i{color:var(--mint)}
  .side-link i{width:18px;text-align:center;font-size:12px;opacity:.85}
  .side-link .cnt{margin-left:auto;font-size:10px;font-weight:800;background:var(--mist-b);color:var(--ink-soft);padding:1px 7px;border-radius:10px}
  .side-link.active .cnt{background:rgba(255,255,255,.16);color:#fff}

  /* ═ MODALS  */
  .modal-overlay{position:fixed;inset:0;background:rgba(10,46,50,.55);backdrop-filter:blur(5px);z-index:300;display:none;align-items:flex-start;justify-content:center;padding:48px 20px;overflow-y:auto}
  .modal-overlay.open{display:flex;animation:overlayIn .22s ease both}
  .modal-overlay.closing{animation:overlayOut .18s ease both}
  .modal{background:#fff;border-radius:16px;width:100%;max-width:560px;box-shadow:0 24px 70px -18px rgba(10,46,50,.5);animation:modalIn .3s cubic-bezier(.2,.9,.3,1.15) both;overflow:hidden}
  .modal-overlay.closing .modal{animation:modalOut .18s ease both}
  .modal.wide{max-width:820px}
  .modal-header{display:flex;align-items:center;gap:14px;padding:18px 24px;position:relative;overflow:hidden;background:radial-gradient(120% 150% at 88% -30%, rgba(127,209,192,.28), transparent 55%),linear-gradient(135deg,var(--deep-2) 0%,var(--deep-3) 100%)}
  .modal-header.danger{background:radial-gradient(120% 150% at 88% -30%, rgba(255,120,120,.25), transparent 55%),linear-gradient(135deg,#991B1B,#7F1D1D)}
  .modal-header::after{content:'';position:absolute;left:0;right:0;bottom:0;height:1px;background:linear-gradient(90deg,rgba(127,209,192,.4),transparent 60%)}
  .modal-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;box-shadow:var(--teal-btn-shadow)}
  .modal-header.danger .modal-icon{background:rgba(255,255,255,.15)}
  .modal-head-text{flex:1}
  .modal-title{font-size:16px;font-weight:800;color:#fff}
  .modal-subtitle{font-size:12px;color:rgba(255,255,255,.62);margin-top:2px}
  .modal-close{width:32px;height:32px;border-radius:8px;border:none;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;font-size:13px;transition:.18s;flex-shrink:0}
  .modal-close:hover{background:rgba(255,255,255,.22);transform:rotate(90deg)}
  .modal-body{padding:24px;max-height:calc(100vh - 280px);overflow-y:auto}
  .modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--line);background:linear-gradient(180deg,var(--mist-a),var(--mist-b))}
  .modal-footer .spacer{margin-right:auto}
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 16px}
  .form-grid .full{grid-column:1/-1}
  .form-field label{display:block;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:6px}
  .form-field label .req{color:var(--red)}
  .form-control{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px;color:var(--ink);background:#fff;outline:none;transition:.15s}
  .form-control:focus{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .form-control:disabled{background:var(--mist-a);color:var(--ink-muted)}
  textarea.form-control{resize:vertical;min-height:80px}
  .form-section-title{font-size:10.5px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--deep-2);margin:22px 0 12px;display:flex;align-items:center;gap:8px}
  .form-section-title:first-child{margin-top:0}
  .form-section-title::after{content:'';flex:1;height:1px;background:var(--line)}
  .switch{position:relative;width:40px;height:22px;display:inline-block;flex-shrink:0}
  .switch input{opacity:0;width:0;height:0}
  .switch-slider{position:absolute;inset:0;background:var(--border);border-radius:20px;cursor:pointer;transition:.2s}
  .switch-slider::before{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .switch input:checked + .switch-slider{background:linear-gradient(90deg,var(--sec),var(--sec-2))}
  .switch input:checked + .switch-slider::before{transform:translateX(18px)}
  .switch-row{display:flex;align-items:center;gap:10px}
  .switch-row-label{font-size:12.5px;font-weight:600}
  .switch-row-sub{font-size:11px;color:var(--ink-muted)}

  /* toast */
  .toast-wrap{position:fixed;bottom:24px;right:24px;z-index:400;display:flex;flex-direction:column;gap:10px}
  .toast{display:flex;align-items:flex-start;gap:10px;min-width:300px;max-width:400px;padding:13px 16px;border-radius:12px;background:linear-gradient(135deg,var(--deep-2),var(--deep-3));color:#fff;box-shadow:var(--cta-btn-shadow);animation:toastIn .3s cubic-bezier(.2,.9,.3,1.2) both}
  .toast i{color:var(--mint);margin-top:2px}
  .toast .t-title{font-size:12.5px;font-weight:800}
  .toast .t-sub{font-size:11px;color:rgba(255,255,255,.65);margin-top:3px;line-height:1.5}

  [data-tip]{position:relative}
  [data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--deep-2);color:#fff;font-size:11px;font-weight:500;padding:4px 9px;border-radius:6px;white-space:nowrap;pointer-events:none;z-index:200;animation:fadeIn .15s ease both;box-shadow:var(--cta-btn-shadow)}
  ::-webkit-scrollbar{width:5px;height:5px}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
  ::-webkit-scrollbar-thumb:hover{background:var(--sec)}

</style>
<main class="main" style="margin-top:0 !important;margin-left:0 !important;padding:20px 24px 40px !important;max-width:100% !important;width:100% !important;">

  <div class="breadcrumb anim-1"><a href="#">Vendor Centre</a><i class="fa-solid fa-chevron-right sep"></i><span class="current">Vendors</span></div>
  <div class="page-header anim-1">
    <div><h1 class="page-title">Vendors</h1><div class="page-subtitle">Vendor directory — records, terms, balances and contact details.</div></div>
    <div class="page-header-actions">
      <button class="btn btn-ghost" id="export-csv"><i class="fa-solid fa-download"></i> Export CSV</button>
      <button class="btn btn-deep" onclick="window.location.href='/accounting/vendors/create'"><i class="fa-solid fa-plus"></i> Create Vendor</button>
    </div>
  </div>

  <!-- KPI TILES -->
  <div class="kpi-strip anim-2">
    <div class="kpi hl active" data-filter="all"><div class="kpi-icon"><i class="fa-solid fa-handshake"></i></div><div class="kpi-text"><span class="kpi-label">All Vendors</span><span class="kpi-value" id="k-all">2</span><span class="kpi-sub">Total in directory</span></div></div>
    <div class="kpi" data-filter="active"><div class="kpi-icon teal"><i class="fa-solid fa-circle-check"></i></div><div class="kpi-text"><span class="kpi-label">Active</span><span class="kpi-value" id="k-active">1</span><span class="kpi-sub">Available for POs</span></div></div>
    <div class="kpi" data-filter="inactive"><div class="kpi-icon"><i class="fa-solid fa-circle-pause"></i></div><div class="kpi-text"><span class="kpi-label">Inactive</span><span class="kpi-value" id="k-inactive">1</span><span class="kpi-sub">On hold</span></div></div>
    <div class="kpi" data-filter="overdue"><div class="kpi-icon red"><i class="fa-solid fa-clock"></i></div><div class="kpi-text"><span class="kpi-label">Overdue</span><span class="kpi-value" id="k-overdue">0</span><span class="kpi-sub">Past due bills</span></div></div>
    <div class="kpi" data-filter="zero"><div class="kpi-icon teal"><i class="fa-solid fa-bullseye"></i></div><div class="kpi-text"><span class="kpi-label">Zero Balance</span><span class="kpi-value" id="k-zero">2</span><span class="kpi-sub">Nothing owed</span></div></div>
  </div>

  <!-- AGEING -->
  <div class="card glass anim-2" style="margin-bottom:18px">
    <div class="card-head">
      <div class="card-head-icon" style="background:linear-gradient(180deg,#E8930C,var(--warn))"><i class="fa-solid fa-hourglass-half"></i></div>
      <div><div class="card-title">Payables Ageing</div><div class="card-sub">Outstanding vendor bills by bucket · click a bucket to filter</div></div>
      <div class="card-head-right">
        <button class="ageing-clear" id="ageing-clear"><i class="fa-solid fa-xmark"></i> Clear ageing filter</button>
        <span class="chip-count"><i class="fa-solid fa-circle-check" style="font-size:9px"></i> K <span id="ageing-total">0.00</span> total outstanding</span>
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <div class="ageing" style="border:none;border-radius:0;box-shadow:none;margin:0" id="ageing">
        <div class="ag-cell" data-bucket="current"><div class="ag-label"><span class="agdot" style="background:var(--green)"></span> Current</div><div class="ag-val">0.00</div><div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--green)"></div></div></div>
        <div class="ag-cell" data-bucket="1-30"><div class="ag-label"><span class="agdot" style="background:var(--sec)"></span> 1–30 Days</div><div class="ag-val">0.00</div><div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--sec)"></div></div></div>
        <div class="ag-cell" data-bucket="31-60"><div class="ag-label"><span class="agdot" style="background:var(--warn)"></span> 31–60 Days</div><div class="ag-val">0.00</div><div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--warn)"></div></div></div>
        <div class="ag-cell" data-bucket="61-90"><div class="ag-label"><span class="agdot" style="background:#EA580C"></span> 61–90 Days</div><div class="ag-val">0.00</div><div class="ag-bar"><div class="ag-fill" style="width:0%;background:#EA580C"></div></div></div>
        <div class="ag-cell" data-bucket="90+"><div class="ag-label"><span class="agdot" style="background:var(--red)"></span> 90+ Days</div><div class="ag-val">0.00</div><div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--red)"></div></div></div>
      </div>
    </div>
  </div>

  <div class="idx-grid anim-3">
    <div>
      <div class="bulk-bar" id="bulk-bar">
        <span class="bulk-count"><b id="bulk-num">0</b> vendor(s) selected</span>
        <div class="bulk-actions">
          <button class="bulk-btn" data-bulk="activate"><i class="fa-solid fa-bolt"></i> Activate</button>
          <button class="bulk-btn" data-bulk="archive"><i class="fa-solid fa-box-archive"></i> Archive</button>
          <button class="bulk-btn" data-bulk="message"><i class="fa-solid fa-envelope"></i> Message</button>
          <button class="bulk-btn primary" data-bulk="export"><i class="fa-solid fa-download"></i> Export</button>
        </div>
        <button class="bulk-clear" id="bulk-clear">Clear</button>
      </div>
      <div class="active-filters" id="active-filters"></div>
      <div class="toolbar">
        <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input id="search-input" placeholder="Name, email, phone…"></div>
        <div class="seg" id="view-seg">
          <button class="seg-btn active" data-view="all">All <span class="n" id="s-all">2</span></button>
          <button class="seg-btn" data-view="active">Active <span class="n" id="s-active">1</span></button>
          <button class="seg-btn" data-view="inactive">Inactive <span class="n" id="s-inactive">1</span></button>
          <button class="seg-btn" data-view="fav"><i class="fa-solid fa-star" style="color:var(--warn)"></i> Fav <span class="n" id="s-fav">0</span></button>
        </div>
        <select class="btn btn-ghost btn-sm" id="terms-filter" style="appearance:auto;-webkit-appearance:auto"><option value="">All Terms</option><option value="Due on receipt">Due on receipt</option><option value="Net 15">Net 15</option><option value="Net 30">Net 30</option></select>
        <button class="btn btn-ghost btn-sm" id="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
        <span class="chip-count" style="margin-left:auto"><span id="visible-count">2</span> vendors</span>
      </div>
      <div class="table-wrap">
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr>
              <th style="width:34px"><input type="checkbox" class="row-check" id="check-all"></th>
              <th style="width:34px"></th>
              <th data-sort="name" class="sorted">Vendor <i class="fa-solid fa-sort-down sort"></i></th>
              <th data-sort="contact">Contact Person <i class="fa-solid fa-sort sort"></i></th>
              <th data-sort="phone">Phone <i class="fa-solid fa-sort sort"></i></th>
              <th data-sort="email">Email <i class="fa-solid fa-sort sort"></i></th>
              <th data-sort="terms">Terms <i class="fa-solid fa-sort sort"></i></th>
              <th data-sort="bal" class="td-right">Outstanding (K) <i class="fa-solid fa-sort sort"></i></th>
              <th data-sort="status">Status <i class="fa-solid fa-sort sort"></i></th>
              <th>Actions</th>
            </tr></thead>
            <tbody id="vendor-rows"></tbody>
          </table>
          <div class="empty-rows" id="empty-rows" style="display:none"><i class="fa-solid fa-magnifying-glass" style="opacity:.4;font-size:20px;display:block;margin-bottom:8px"></i>No vendors match your filters.</div>
        </div>
        <div class="table-footer"><span>Showing <span id="showing">1–2</span> of <span id="total-count">2</span> vendors</span><div class="pager"><button class="pager-btn"><i class="fa-solid fa-chevron-left"></i></button><button class="pager-btn active">1</button><button class="pager-btn"><i class="fa-solid fa-chevron-right"></i></button></div></div>
      </div>
    </div>
    <div class="side-panel">
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Views</span></div>
        <div class="side-list" id="views-rail">
          <a class="side-link active" data-view="all"><i class="fa-solid fa-handshake"></i> All Vendors <span class="cnt" id="v-all">2</span></a>
          <a class="side-link" data-view="active"><i class="fa-solid fa-circle-check"></i> Active <span class="cnt" id="v-active">1</span></a>
          <a class="side-link" data-view="inactive"><i class="fa-solid fa-circle-pause"></i> Inactive <span class="cnt" id="v-inactive">1</span></a>
          <a class="side-link" data-view="overdue"><i class="fa-solid fa-clock"></i> Overdue <span class="cnt" id="v-overdue">0</span></a>
          <a class="side-link" data-view="zero"><i class="fa-solid fa-bullseye"></i> Zero Balance <span class="cnt" id="v-zero">2</span></a>
          <a class="side-link" data-view="fav"><i class="fa-solid fa-star"></i> Favourites <span class="cnt" id="v-fav">0</span></a>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Reports</span></div>
        <div class="side-list" id="reports-rail">
          <a class="side-link" href="{{ route('accounting.reports.vendor-statement') }}"><i class="fa-solid fa-dollar-sign"></i> Vendor Balances</a>
          <a class="side-link" href="{{ route('accounting.reports.purchases-by-vendor') }}"><i class="fa-solid fa-chart-pie"></i> Purchases by Vendor</a>
          <a class="side-link" href="{{ route('accounting.aging.ap-summary') }}"><i class="fa-solid fa-hourglass-half"></i> Ageing Report</a>
          <a class="side-link" href="#"><i class="fa-solid fa-file-invoice-dollar"></i> 1099 / Tax Batch</a>
        </div>
      </div>
    </div>
  </div>

</main>


<!-- DELETE CONFIRM -->
<div class="modal-overlay" id="modal-delete">
  <div class="modal" style="max-width:440px">
    <div class="modal-header danger"><div class="modal-icon"><i class="fa-solid fa-trash-can"></i></div><div class="modal-head-text"><div class="modal-title">Delete Vendor?</div><div class="modal-subtitle" id="del-name">—</div></div><button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <p style="font-size:13px;line-height:1.6;color:var(--ink-soft);margin-bottom:16px">This will permanently remove the vendor record. Historical transactions are retained but the vendor will no longer be selectable for new purchase orders or bills.</p>
      <div style="background:var(--red-tint);border:1px solid rgba(220,38,38,.2);border-radius:8px;padding:12px;font-size:12px;color:var(--red-2);font-weight:600;display:flex;gap:8px;align-items:flex-start"><i class="fa-solid fa-triangle-exclamation" style="margin-top:2px"></i><span>This action cannot be undone.</span></div>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-danger-ghost" style="background:var(--red);color:#fff;border:none" id="confirm-delete"><i class="fa-solid fa-trash-can"></i> Yes, Delete</button></div>
  </div>
</div>

<!-- MESSAGE -->
<div class="modal-overlay" id="modal-message">
  <div class="modal">
    <div class="modal-header"><div class="modal-icon"><i class="fa-solid fa-envelope"></i></div><div class="modal-head-text"><div class="modal-title">Message Vendor</div><div class="modal-subtitle" id="msg-to">—</div></div><button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field full"><label>Subject <span class="req">*</span></label><input class="form-control" value="Statement & Outstanding Balance"></div>
        <div class="form-field full"><label>Message <span class="req">*</span></label><textarea class="form-control" style="min-height:120px">Dear Partner,

Please find attached your latest statement of account. Kindly confirm receipt and advise on settlement timing.

Regards,
Acme Corporation — Accounts Payable</textarea></div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-deep" id="send-message"><i class="fa-solid fa-paper-plane"></i> Send Message</button></div>
  </div>
</div>

<div class="toast-wrap" id="toasts"></div>

<script>
let vendors = [
  {id:1, code:'VEN-00001', name:'AHL Group', legal:'AHL Group Ltd', contact:'AHL Group', phone:'0999602605', email:'ahl@ahl.ww', terms:'Due on receipt', bal:0, status:'active', since:'Jul 2026', cat:'Office Supplies', cur:'K — Kwacha', tax:'', fav:false},
  {id:2, code:'VEN-00002', name:'Gracian Seyama', legal:'Gracian Seyama', contact:'Yeah', phone:'', email:'', terms:'Net 15', bal:0, status:'inactive', since:'Aug 2026', cat:'Office Supplies', cur:'K — Kwacha', tax:'', fav:false}
];
let nextId = 3;
let state = { filter:'all', view:'all', search:'', terms:'', sortKey:'name', sortDir:1, deletingId:null, messagingIds:[], ageingBucket:'' };

const fmt = n => Number(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
const initials = n => n.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
const esc = s => String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

function passes(v){
  if(state.view==='active'&&v.status!=='active')return false;if(state.view==='inactive'&&v.status!=='inactive')return false;if(state.view==='fav'&&!v.fav)return false;if(state.view==='overdue'&&!(v.bal>0))return false;if(state.view==='zero'&&v.bal!==0)return false;
  if(state.filter!=='all'){if(state.filter==='active'&&v.status!=='active')return false;if(state.filter==='inactive'&&v.status!=='inactive')return false;if(state.filter==='overdue'&&!(v.bal>0))return false;if(state.filter==='zero'&&v.bal!==0)return false;}
  if(state.terms&&v.terms!==state.terms)return false;
  if(state.search){const q=state.search.toLowerCase();if(![v.name,v.contact,v.phone,v.email,v.code].some(f=>String(f).toLowerCase().includes(q)))return false;}
  return true;
}
function sorted(list){const k=state.sortKey,d=state.sortDir;return[...list].sort((a,b)=>{let x=a[k],y=b[k];if(k==='bal'){x=Number(x);y=Number(y);}else{x=String(x??'').toLowerCase();y=String(y??'').toLowerCase();}if(x<y)return-1*d;if(x>y)return 1*d;return 0;});}

function render(){
  const list=sorted(vendors.filter(passes));
  const tb=document.getElementById('vendor-rows');
  tb.innerHTML=list.map(v=>`<tr class="clickable-row" tabindex="0" data-id="${v.id}">
    <td><input type="checkbox" class="row-check row-sel" data-id="${v.id}"></td>
    <td><button class="star-btn ${v.fav?'on':''}" data-star="${v.id}" data-tip="Favourite"><i class="fa-solid fa-star"></i></button></td>
    <td><div class="avatar-cell"><div class="avatar">${initials(v.name)}<span class="astat ${v.status==='active'?'on':'off'}"></span></div><div><div class="cell-name">${esc(v.name)}</div><div class="cell-sub">Since ${esc(v.since)} · ${esc(v.code)}</div></div></div></td>
    <td>${esc(v.contact)||'<span style="color:#9DB4B4">—</span>'}</td>
    <td class="td-mono">${v.phone?esc(v.phone):'<span style="color:#9DB4B4">—</span>'}</td>
    <td>${v.email?esc(v.email):'<span style="color:#9DB4B4">—</span>'}</td>
    <td><span class="terms-pill">${esc(v.terms)}</span></td>
    <td class="td-right td-mono ${v.bal>0?'td-red':'td-strong'}">${fmt(v.bal)}</td>
    <td>${v.status==='active'?'<span class="pill pill-active"><span class="pdot"></span> Active</span>':'<span class="pill pill-inactive"><span class="pdot"></span> Inactive</span>'}</td>
    <td>
      <button class="row-icon-btn" data-view="${v.id}" data-tip="View"><i class="fa-solid fa-eye"></i></button>
      <button class="row-icon-btn" data-edit="${v.id}" data-tip="Edit"><i class="fa-solid fa-pen"></i></button>
      <button class="row-icon-btn" data-del="${v.id}" data-tip="Delete"><i class="fa-solid fa-trash-can"></i></button>
    </td>
  </tr>`).join('');
  document.getElementById('empty-rows').style.display=list.length?'none':'block';
  document.getElementById('visible-count').textContent=list.length;
  document.getElementById('showing').textContent=list.length?`1–${list.length}`:'0';
  document.getElementById('total-count').textContent=vendors.length;
  updateCounts();syncBulk();
  document.getElementById('ageing-clear').classList.toggle('show',!!state.ageingBucket);
  const af=document.getElementById('active-filters');
  if(state.ageingBucket){af.innerHTML=`<span class="filter-chip-label">Active filter:</span><span class="filter-chip"><i class="fa-solid fa-hourglass-half"></i> Payables: <b>${BUCKET_LABELS[state.ageingBucket]}</b><button id="chip-clear" data-tip="Clear filter"><i class="fa-solid fa-xmark"></i></button></span>`;document.getElementById('chip-clear').addEventListener('click',clearAgeing);}else{af.innerHTML='';}
}
function updateCounts(){const all=vendors.length,act=vendors.filter(v=>v.status==='active').length,ina=vendors.filter(v=>v.status==='inactive').length,od=vendors.filter(v=>v.bal>0).length,zero=vendors.filter(v=>v.bal===0).length,fav=vendors.filter(v=>v.fav).length,tot=vendors.reduce((s,v)=>s+Number(v.bal||0),0);[['k-all',all],['k-active',act],['k-inactive',ina],['k-overdue',od],['k-zero',zero],['s-all',all],['s-active',act],['s-inactive',ina],['s-fav',fav],['v-all',all],['v-active',act],['v-inactive',ina],['v-overdue',od],['v-zero',zero],['v-fav',fav]].forEach(([id,n])=>{const e=document.getElementById(id);if(e)e.textContent=n;});document.getElementById('ageing-total').textContent=fmt(tot);}

const openModal=id=>{const ov=document.getElementById(id);ov.classList.remove('closing');ov.classList.add('open');document.body.classList.add('modal-open');};
const closeModal=ov=>{ov.classList.add('closing');setTimeout(()=>{ov.classList.remove('open','closing');document.body.classList.remove('modal-open');},180);};
document.querySelectorAll('[data-close-modal]').forEach(b=>b.addEventListener('click',()=>closeModal(b.closest('.modal-overlay'))));
document.querySelectorAll('.modal-overlay').forEach(ov=>ov.addEventListener('mousedown',e=>{if(e.target===ov)closeModal(ov);}));
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal-overlay.open').forEach(closeModal);});

function toast(title,sub){const t=document.createElement('div');t.className='toast';t.innerHTML=`<i class="fa-solid fa-circle-check"></i><div><div class="t-title">${title}</div><div class="t-sub">${sub}</div></div>`;document.getElementById('toasts').appendChild(t);setTimeout(()=>{t.style.transition='opacity .4s, transform .4s';t.style.opacity='0';t.style.transform='translateY(8px)';setTimeout(()=>t.remove(),400);},3200);}

document.querySelectorAll('.kpi').forEach(k=>k.addEventListener('click',()=>{state.ageingBucket='';document.querySelectorAll('#ageing .ag-cell').forEach(x=>x.classList.remove('sel'));document.querySelectorAll('.kpi').forEach(x=>x.classList.remove('active'));k.classList.add('active');state.filter=k.dataset.filter;render();}));

const BUCKET_LABELS={'current':'Current','1-30':'1–30 Days','31-60':'31–60 Days','61-90':'61–90 Days','90+':'90+ Days'};
function applyAgeing(bucket){document.querySelectorAll('#ageing .ag-cell').forEach(x=>x.classList.toggle('sel',x.dataset.bucket===bucket));state.ageingBucket=bucket;state.filter=bucket?'overdue':'all';syncKpiActive(bucket?'overdue':'all');render();}
function clearAgeing(){applyAgeing('');toast('Ageing filter cleared','Showing all vendors again.');}
document.querySelectorAll('#ageing .ag-cell').forEach(c=>c.addEventListener('click',()=>{const bucket=c.dataset.bucket;const already=c.classList.contains('sel');applyAgeing(already?'':bucket);if(!already)toast('Ageing bucket selected','Filtered to '+BUCKET_LABELS[bucket]+' outstanding balances.');}));
document.getElementById('ageing-clear').addEventListener('click',clearAgeing);
function syncKpiActive(f){document.querySelectorAll('.kpi').forEach(x=>x.classList.toggle('active',x.dataset.filter===f));}

function setView(v){state.ageingBucket='';document.querySelectorAll('#ageing .ag-cell').forEach(x=>x.classList.remove('sel'));state.view=v;state.filter='all';document.querySelectorAll('#view-seg .seg-btn').forEach(b=>b.classList.toggle('active',b.dataset.view===v));document.querySelectorAll('#views-rail .side-link').forEach(l=>l.classList.toggle('active',l.dataset.view===v));syncKpiActive(v==='all'?'all':v);render();}
document.querySelectorAll('#view-seg .seg-btn').forEach(b=>b.addEventListener('click',()=>setView(b.dataset.view)));
document.querySelectorAll('#views-rail .side-link').forEach(l=>l.addEventListener('click',e=>{e.preventDefault();setView(l.dataset.view);}));
document.querySelectorAll('#reports-rail .side-link').forEach(l=>l.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('#reports-rail .side-link').forEach(x=>x.classList.remove('active'));l.classList.add('active');toast('Report opened',l.dataset.report+' — generating preview…');}));
document.getElementById('search-input').addEventListener('input',e=>{state.search=e.target.value;render();});
document.getElementById('terms-filter').addEventListener('change',e=>{state.terms=e.target.value;render();});
document.getElementById('filter-btn').addEventListener('click',()=>toast('Filters applied','Status + terms + search combined'));
document.querySelectorAll('th[data-sort]').forEach(th=>th.addEventListener('click',()=>{const k=th.dataset.sort;if(state.sortKey===k)state.sortDir*=-1;else{state.sortKey=k;state.sortDir=1;}document.querySelectorAll('th[data-sort]').forEach(h=>{h.classList.remove('sorted');h.querySelector('.sort').className='fa-solid fa-sort sort';});th.classList.add('sorted');th.querySelector('.sort').className='fa-solid fa-sort-'+(state.sortDir===1?'down':'up')+' sort';render();}));

/* ROW ACTIONS — VIEW NAVIGATES TO DETAIL PAGE */
document.getElementById('vendor-rows').addEventListener('click',e=>{
  const star=e.target.closest('[data-star]');if(star){e.stopPropagation();const v=vendors.find(x=>x.id==star.dataset.star);v.fav=!v.fav;render();toast(v.fav?'Added to favourites':'Removed from favourites',v.name);return;}
  const view=e.target.closest('[data-view]');if(view){e.stopPropagation();window.location.href='/accounting/vendors/'+view.dataset.view;return;}
  const edit=e.target.closest('[data-edit]');if(edit){e.stopPropagation();window.location.href='/accounting/vendors/'+edit.dataset.edit+'/edit';return;}
  const del=e.target.closest('[data-del]');if(del){e.stopPropagation();state.deletingId=+del.dataset.del;const v=vendors.find(x=>x.id==state.deletingId);document.getElementById('del-name').textContent=v.name+' ('+v.code+')';openModal('modal-delete');return;}
  const sel=e.target.closest('.row-sel');if(sel){e.stopPropagation();syncBulk();return;}
  const row=e.target.closest('tr[data-id]');if(row) window.location.href='/accounting/vendors/'+row.dataset.id;
});
document.getElementById('vendor-rows').addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){const row=e.target.closest('tr[data-id]');if(row){e.preventDefault();window.location.href='/accounting/vendors/'+row.dataset.id;}}});

document.getElementById('confirm-delete').addEventListener('click',()=>{const v=vendors.find(x=>x.id===state.deletingId);vendors=vendors.filter(x=>x.id!==state.deletingId);closeModal(document.getElementById('modal-delete'));toast('Vendor deleted',(v?v.name:'Record')+' removed from the directory.');render();});
function openMessage(ids){state.messagingIds=ids;const names=ids.map(i=>vendors.find(v=>v.id===i)?.name).filter(Boolean);document.getElementById('msg-to').textContent='To: '+names.join(', ');openModal('modal-message');}
document.getElementById('send-message').addEventListener('click',()=>{closeModal(document.getElementById('modal-message'));toast('Message sent','Delivered to '+state.messagingIds.length+' vendor(s).');});

function syncBulk(){const checked=[...document.querySelectorAll('.row-sel:checked')];document.getElementById('bulk-num').textContent=checked.length;document.getElementById('bulk-bar').classList.toggle('show',checked.length>0);document.querySelectorAll('#vendor-rows tr').forEach(r=>{const c=r.querySelector('.row-sel');r.classList.toggle('selected',!!(c&&c.checked));});const all=document.querySelectorAll('.row-sel');document.getElementById('check-all').checked=all.length>0&&checked.length===all.length;}
document.getElementById('check-all').addEventListener('change',e=>{document.querySelectorAll('.row-sel').forEach(c=>c.checked=e.target.checked);syncBulk();});
document.getElementById('bulk-clear').addEventListener('click',()=>{document.querySelectorAll('.row-check').forEach(c=>c.checked=false);syncBulk();});
document.querySelectorAll('[data-bulk]').forEach(b=>b.addEventListener('click',()=>{const ids=[...document.querySelectorAll('.row-sel:checked')].map(c=>+c.dataset.id);const act=b.dataset.bulk;if(act==='activate'){ids.forEach(i=>{const v=vendors.find(x=>x.id===i);if(v)v.status='active';});toast('Activated',ids.length+' vendor(s) activated.');}if(act==='archive'){ids.forEach(i=>{const v=vendors.find(x=>x.id===i);if(v)v.status='inactive';});toast('Archived',ids.length+' vendor(s) archived.');}if(act==='message'){openMessage(ids);return;}if(act==='export'){exportCSV(ids);return;}document.querySelectorAll('.row-check').forEach(c=>c.checked=false);render();}));
function exportCSV(ids){const rows=(ids&&ids.length?vendors.filter(v=>ids.includes(v.id)):vendors.filter(passes));const head=['Code','Name','Legal Name','Contact','Phone','Email','Terms','Outstanding','Status','Category','Currency'];const csv=[head.join(','),...rows.map(v=>[v.code,v.name,v.legal,v.contact,v.phone,v.email,v.terms,v.bal,v.status,v.cat,v.cur].map(f=>`"${String(f??'').replace(/"/g,'""')}"`).join(','))].join('\n');const blob=new Blob([csv],{type:'text/csv'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='vendors.csv';a.click();URL.revokeObjectURL(url);toast('Exported',rows.length+' vendor(s) downloaded as CSV.');}
document.getElementById('export-csv').addEventListener('click',()=>exportCSV());
document.querySelectorAll('.sidebar-item').forEach(item=>item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.sidebar-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');}));
document.querySelectorAll('.module-item').forEach(item=>item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.module-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');}));
render();
</script>

</x-app-layout>
