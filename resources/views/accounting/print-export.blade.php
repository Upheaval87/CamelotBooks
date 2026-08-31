<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? 'Report' }}</title>
@if(!empty($meta))
<style>
    /* ==================================================================
       Financial statement branded sheet — APPENDIX A
       Company logo on the SAME row as company name, no product branding,
       no Notes section, no signature block. Screen-only controls live in
       .screen-only and are hidden/omitted from the printed PDF.
       ================================================================== */
    :root{
        --ink:#0B3437;
        --ink-2:#12393C;
        --brand:#0E6E67;
        --brand-ink:#C9A227;
        --muted:#5F7476;
        --faint:#8AA5A7;
        --line:#DCEAEA;
        --soft:#F4F8F8;
        --red:#B91C1C;
    }
    *{margin:0;padding:0;box-sizing:border-box}
    html{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    @page{size:auto;margin:13mm 13mm 14mm 13mm}
    body{
        font-family:'Inter',system-ui,-apple-system,'Segoe UI',Arial,sans-serif;
        font-size:12px;line-height:1.35;color:var(--ink);background:#EEF2F3;
        -webkit-font-smoothing:antialiased;
    }

    /* --- screen-only control bar (hidden in print) --- */
    .screen-only{
        position:sticky;top:0;z-index:20;display:flex;align-items:center;gap:10px;
        justify-content:flex-end;max-width:1040px;margin:0 auto 14px;
        padding:10px 14px;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);
        border:1px solid var(--line);border-radius:12px;box-shadow:0 2px 10px rgba(11,52,55,.06);
    }
    .screen-only .zero-toggle{
        display:inline-flex;align-items:center;gap:8px;font-size:12px;color:var(--muted);
        cursor:pointer;margin-right:auto;user-select:none;
    }
    .screen-only .zero-toggle input{accent-color:var(--brand);width:14px;height:14px}
    .screen-only .btn{
        display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);
        background:#fff;color:var(--ink);border-radius:9px;padding:8px 14px;
        font-size:12.5px;font-weight:500;cursor:pointer;text-decoration:none;
    }
    .screen-only .btn:hover{background:var(--soft)}
    .screen-only .btn.primary{
        border:none;background:linear-gradient(180deg,#149897,#128F8E);color:#fff;box-shadow:0 6px 14px rgba(18,143,142,.25);
    }
    .screen-only .btn svg{width:15px;height:15px}

    /* --- the branded sheet --- */
    .fs-sheet{
        max-width:1040px;margin:0 auto;background:#fff;
        border:1px solid var(--line);border-radius:4px;
        padding:34px 42px 40px;min-height:100vh;
    }

    /* header lockup — logo + name on the SAME row */
    .fs-head{display:flex;align-items:center;gap:14px}
    .fs-logo{
        width:48px;height:48px;border-radius:12px;flex:0 0 auto;overflow:hidden;
        display:flex;align-items:center;justify-content:center;position:relative;
        background:linear-gradient(180deg,#0E6E67,#0A5C56);color:#fff;
    }
    .fs-logo img{width:100%;height:100%;object-fit:contain;padding:2px;background:#fff}
    .fs-logo .mono{font-size:21px;font-weight:800;line-height:1;color:#F4FBFB}
    .fs-logo::after{
        content:'';position:absolute;left:6px;right:6px;bottom:4px;height:3px;border-radius:2px;
        background:linear-gradient(90deg,#C9A227,#D9B84A);
    }
    .fs-id{min-width:0}
    .fs-company{font-size:19px;font-weight:800;letter-spacing:.01em;color:var(--ink);line-height:1.1}
    .fs-orgline{
        margin-top:4px;font-size:9.5px;font-weight:700;letter-spacing:.14em;
        text-transform:uppercase;color:var(--muted);
    }
    .fs-rule{height:2px;background:var(--ink);border:0;margin:18px 0 20px}

    /* title block */
    .fs-title{
        font-size:15px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;color:var(--ink);
    }
    .fs-period{
        margin-top:6px;font-size:11px;font-weight:600;color:var(--muted);
    }

    /* meta strip — 4-cell hairline */
    .fs-meta{
        display:grid;grid-template-columns:repeat(4,1fr);margin:18px 0 20px;
        border:1px solid var(--line);border-radius:10px;overflow:hidden;
    }
    .fs-meta-cell{padding:11px 14px;border-right:1px solid var(--line)}
    .fs-meta-cell:last-child{border-right:none}
    .fs-meta-label{
        font-size:8.5px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--faint);
    }
    .fs-meta-value{margin-top:4px;font-size:12px;font-weight:700;color:#000;font-variant-numeric:tabular-nums}
    .fs-meta-value.muted{font-weight:600;color:#000}

    /* body tables */
    .fs-table{width:100%;border-collapse:collapse;font-size:12px}
    .fs-table thead th{
        text-align:right;font-size:10px;font-weight:800;letter-spacing:.09em;
        text-transform:uppercase;color:var(--muted);
        padding:9px 8px 8px;border-bottom:1.5px solid var(--ink);
        font-variant-numeric:tabular-nums;white-space:nowrap;
    }
    .fs-table thead th.fs-lbl{text-align:left}
    .fs-table tbody td{padding:8px 8px;border-bottom:1px solid var(--line);vertical-align:middle}
    .fs-table td.fs-amt,.fs-table td.fs-cr{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
    .fs-table td.fs-cr{width:1%;padding-left:18px}
    .fs-code{
        display:inline-block;width:40px;margin-right:8px;color:var(--faint);
        font-family:'SFMono-Regular',Consolas,'Liberation Mono',ui-monospace,monospace;
        font-size:10px;font-weight:600;
    }
    .fs-name{color:var(--ink-2)}
    .fs-neg{color:var(--red)}
    .fs-zero .fs-name,.fs-zero .fs-amt,.fs-zero .fs-cr,.fs-zero .fs-code{color:#000}

    /* section / subtotal / total */
    .fs-section td{
        padding:9px 8px 7px;border-bottom:1px solid var(--ink);border-top:1px solid var(--ink);
        font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-2);
        background:var(--soft);
    }
    .fs-subtotal td{
        padding:8px 8px 9px;border-top:1.5px dashed var(--line);border-bottom:none;
        font-size:12.5px;font-weight:700;color:var(--ink-2);
    }
    .fs-subsection td{
        padding:7px 8px 6px;border-bottom:1px solid var(--line);
        font-size:9.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
        color:var(--muted);background:#FBFDFD;
    }
    .fs-total td{
        padding:10px 8px;font-size:13px;font-weight:800;color:var(--ink);
        background:rgba(14,110,103,.06);
        border-top:2px solid var(--ink);border-bottom:3px double var(--ink);
    }
    .fs-total td.fs-amt,.fs-total td.fs-cr{font-weight:800}
    .fs-stub{text-align:right}

    /* inline notes / warnings (e.g. trial balance imbalance, cash-flow mismatch) */
    .fs-warn{
        margin-top:16px;padding:10px 12px;border:1px solid rgba(185,28,28,.25);
        border-radius:8px;background:#FEF2F2;color:var(--red);font-size:10.5px;font-weight:600;
    }
    .fs-check{
        margin-top:16px;padding:10px 12px;border:1px solid var(--line);border-radius:8px;
        background:var(--soft);color:var(--ink-2);font-size:10.5px;font-weight:700;
    }

    /* footer */
    .fs-foot{
        display:flex;justify-content:space-between;gap:16px;margin-top:38px;padding-top:13px;
        border-top:1px solid var(--line);font-size:10px;color:var(--muted);
    }
    .fs-foot .fr{font-variant-numeric:tabular-nums}

    @media print{
        body{background:#fff;padding:0}
        .screen-only{display:none !important}
        .fs-sheet{max-width:none;border:none;border-radius:0;padding:0 0 8mm;min-height:0;margin:0}
        .fs-head,.fs-title,.fs-period,.fs-meta,.fs-table,.fs-warn,.fs-check{break-inside:avoid}
        .fs-table tr{break-inside:avoid}
        .fs-section td{background:var(--soft) !important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
        .fs-total td{background:rgba(14,110,103,.06) !important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
        .fs-foot{position:fixed;bottom:-13mm;left:0;right:0}
    }
</style>
@else
<style>
    /* ==================================================================
       LEGACY centred report head — used by non-statement print views
       (Cash Position, General Ledger) that render their own head.
       ================================================================== */
    :root {
        --ink: #2C2C2A; --ink-soft: #5F5E5A; --ink-muted: #96958D;
        --border: #E6E4DC; --border-strong: #B4B2A9; --accent: #B8790C;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family:'Inter',system-ui,-apple-system,'Segoe UI',Arial,sans-serif; font-size:9.5px; color:#2C2C2A; background:#FAF9F5; padding:32px 20px 80px; }
    .report-card { background:#FFF; border:1px solid #E6E4DC; border-radius:12px; padding:34px 40px; max-width:760px; margin:0 auto; }
    .report-head { text-align:center; margin-bottom:26px; }
    .report-head .company { font-size:12px; font-weight:700; letter-spacing:.02em; margin:0 0 4px; }
    .report-head .report-title { font-size:11px; font-weight:700; letter-spacing:.22em; text-transform:uppercase; margin:0 0 6px; }
    .report-head .report-range { font-size:8px; font-weight:600; color:var(--ink-soft); margin:0; }
    .report-toolbar { display:flex; justify-content:flex-end; align-items:center; gap:10px; margin-bottom:16px; }
    .report-toolbar .btn-outline { display:inline-flex; align-items:center; gap:6px; border:1px solid var(--border-strong); background:#FFF; color:#2C2C2A; border-radius:8px; padding:8px 14px; font-size:13px; cursor:pointer; text-decoration:none; }
    .report-toolbar .btn-outline:hover { background:#F1EFE8; }
    .report-toolbar .btn-primary { display:inline-flex; align-items:center; gap:6px; border:none; background:var(--accent); color:#FFF; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:500; text-decoration:none; }
    .report-toolbar .btn-primary:hover { filter:brightness(1.06); }
    .report-toolbar .zero-toggle { display:inline-flex; align-items:center; gap:8px; font-size:12.5px; color:var(--ink-soft); cursor:pointer; margin-right:auto; user-select:none; }
    .report-toolbar .zero-toggle input { accent-color:var(--accent); width:14px; height:14px; }
    .report-table { width:100%; border-collapse:collapse; font-size:9.5px; }
    .report-table thead th { text-align:left; padding:8px 8px; border-bottom:1.5px solid #2C2C2A; font-weight:700; color:#2C2C2A; }
    .report-table thead th.report-col-amt { text-align:right; }
    .report-table tbody td { padding:7px 8px; border-bottom:1px solid #E6E4DC; }
    .report-table td.report-cell-amt { text-align:right; font-variant-numeric:tabular-nums; }
    .report-cell-code { color:var(--ink-soft); font-size:8.5px; margin-right:8px; font-weight:600; }
    .report-table .zero td { color:var(--ink-muted); }
    .report-subtotal-row td { padding:9px 8px 10px; font-weight:700; border-top:1.5px dashed var(--border-strong); color:#2C2C2A; border-bottom:none; }
    .report-total-row td { padding:10px 8px; font-weight:700; border-top:2px solid #2C2C2A; color:#2C2C2A; }
    .report-total-val { font-size:12px; }
    .report-col-bar { display:flex; justify-content:space-between; align-items:center; background:#E6E4DC; padding:10px 14px; margin:0 -14px; border-radius:4px 4px 0 0; font-size:7.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
    .report-section-bar { display:flex; justify-content:space-between; align-items:center; background:#FAF9F5; padding:9px 14px; margin:0 -14px; border-bottom:1px solid #E6E4DC; font-size:8px; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
    .report-line { display:flex; justify-content:space-between; align-items:baseline; padding:9px 0; border-bottom:1px solid #E6E4DC; font-size:9.5px; }
    .report-line .code { color:var(--ink-soft); font-size:8.5px; margin-right:8px; font-weight:600; }
    .report-line .amt { font-variant-numeric:tabular-nums; }
    .report-line.zero .code, .report-line.zero .amt { color:var(--ink-muted); }
    .report-subtotal { display:flex; justify-content:space-between; font-size:10px; font-weight:600; padding:12px 0 0; margin-top:4px; margin-bottom:24px; border-top:1.5px dashed var(--border-strong); }
    .report-total { display:flex; justify-content:space-between; align-items:baseline; margin-top:30px; padding-top:18px; border-top:2px solid #2C2C2A; }
    .report-total .lbl { font-size:11px; font-weight:700; }
    .report-total .val { font-size:14px; font-weight:700; font-variant-numeric:tabular-nums; color:#2C2C2A; }
    .report-footer { display:flex; justify-content:space-between; font-size:8px; color:var(--ink-muted); margin-top:34px; padding-top:14px; border-top:1px solid #E6E4DC; }
    .report-card-wrap { max-width:760px; margin:0 auto; }
    @media print {
        body { background:#fff; padding:0; }
        .report-toolbar, .report-print-hide { display:none !important; }
        .report-card { border:none; border-radius:0; padding:0; max-width:none; }
        .report-col-bar, .report-section-bar { margin:0; border-radius:0 !important; }
    }
</style>
@endif
</head>
<body>
@if(!empty($meta))
    @php
        $m           = $meta;
        $company     = $m['company'];
        $companyName = $company->name ?? $m['companyName'] ?? 'Company';
        $branchLine  = $m['branchLine']      ?? null;
        $tpin        = $m['tpin']            ?? ($company->tax_id ?? null);
        $orgLine     = trim(implode(' · ', array_filter([
            $branchLine, $tpin ? 'TPIN '.$tpin : null,
        ])));
        $logoPath    = $company->logo ?? $m['logo'] ?? null;
        $hasLogo     = $logoPath && file_exists(public_path('storage/'.$logoPath));
        $initials    = $m['initials'] ?? strtoupper(mb_substr(trim($company->name ?? 'C'), 0, 2));
        $title       = $m['title'] ?? ($title ?? '');
        $periodLabel = $m['periodLabel'] ?? null;
        $basis       = $m['basis'] ?? 'Accrual';
        $preparedAt  = $m['preparedAt'] ?? now();
        $preparedBy  = $m['preparedBy'] ?? null;
        $currency    = $m['currency'] ?? null;
        $footerTitle = $m['footerTitle'] ?? $title;
        $pageCounter = $m['pageCounter'] ?? false;
    @endphp
    <div class="screen-only">
        <label class="zero-toggle">
            <input type="checkbox" id="reportZeroToggle" checked onchange="toggleZeroRows()">
            Show zero-balance accounts
        </label>
        <button class="btn" onclick="window.print()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <button class="btn primary" onclick="window.print()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Save PDF
        </button>
    </div>

    <div class="fs-sheet">
        <header class="fs-head">
            <div class="fs-logo">
                @if($hasLogo)
                    <img src="{{ asset('storage/'.$logoPath) }}" alt="">
                @else
                    <span class="mono">{{ $initials }}</span>
                @endif
            </div>
            <div class="fs-id">
                <div class="fs-company">{{ $companyName }}</div>
                @if($orgLine)
                    <div class="fs-orgline">{{ $orgLine }}</div>
                @endif
            </div>
        </header>
        <hr class="fs-rule">

        <div class="fs-title">{{ $title }}</div>
        @if($periodLabel)
            <div class="fs-period">{{ $periodLabel }}</div>
        @endif

        <div class="fs-meta">
            @if($currency)
            <div class="fs-meta-cell">
                <div class="fs-meta-label">Currency</div>
                <div class="fs-meta-value">{{ $currency }}</div>
            </div>
            @endif
            <div class="fs-meta-cell">
                <div class="fs-meta-label">Basis</div>
                <div class="fs-meta-value">{{ $basis }}</div>
            </div>
            <div class="fs-meta-cell">
                <div class="fs-meta-label">Prepared</div>
                <div class="fs-meta-value">{{ \Carbon\Carbon::parse($preparedAt)->format('d M Y') }}</div>
            </div>
            <div class="fs-meta-cell">
                <div class="fs-meta-label">Prepared By</div>
                <div class="fs-meta-value">{{ $preparedBy }}</div>
            </div>
        </div>

        {!! $content !!}

        @if(!empty($m['check']) || !empty($m['warn']))
            @if(!empty($m['check']))
                <div class="fs-check">{{ $m['check'] }}</div>
            @elseif(!empty($m['warn']))
                <div class="fs-warn">{{ $m['warn'] }}</div>
            @endif
        @endif

        <footer class="fs-foot">
            <span>{{ $companyName }}{{ $branchLine ? ' · '.$branchLine : '' }}</span>
            <span class="fr">{{ $footerTitle }} · <span class="fs-pageno"></span></span>
        </footer>
    </div>

    <script>
        function toggleZeroRows() {
            var show = document.getElementById('reportZeroToggle').checked;
            document.querySelectorAll('.fs-table tr.fs-zero').forEach(function (el) {
                el.style.display = show ? '' : 'none';
            });
        }
        try {
            var pn = document.querySelector('.fs-pageno');
            if (pn) pn.textContent = 'Page 1 of 1';
            var gap = 0;
        } catch (e) {}
    </script>
@else
    <div class="report-card-wrap">
        <div class="report-card">
            {!! $content !!}
        </div>
        <script>
            function toggleZeroRows() {
                var show = document.getElementById('reportZeroToggle').checked;
                document.querySelectorAll('.report-line.zero, .report-table tbody tr.zero').forEach(function (el) {
                    el.style.display = show ? '' : 'none';
                });
            }
        </script>
    </div>
@endif
</body>
</html>
