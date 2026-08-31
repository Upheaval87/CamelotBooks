<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? 'Report' }}</title>
<style>
    :root {
        --ink: #2C2C2A;
        --ink-soft: #5F5E5A;
        --ink-muted: #96958D;
        --border: #E6E4DC;
        --border-strong: #B4B2A9;
        --accent: #B8790C;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
        font-size: 9.5px; color: #2C2C2A;
        background: #FAF9F5; padding: 32px 20px 80px;
    }
    .report-card {
        background: #FFF; border: 1px solid #E6E4DC; border-radius: 12px;
        padding: 34px 40px; max-width: 760px; margin: 0 auto;
    }
    .report-head { text-align: center; margin-bottom: 26px; }
    .report-head .company { font-size: 12px; font-weight: 700; letter-spacing: .02em; margin: 0 0 4px; }
    .report-head .report-title { font-size: 11px; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; margin: 0 0 6px; }
    .report-head .report-range { font-size: 8px; font-weight: 600; color: var(--ink-soft); margin: 0; }
    .report-toolbar {
        display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-bottom: 16px;
    }
    .report-toolbar .btn-outline {
        display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--border-strong); background: #FFF;
        color: #2C2C2A; border-radius: 8px; padding: 8px 14px; font-size: 13px; cursor: pointer; text-decoration: none;
    }
    .report-toolbar .btn-outline:hover { background: #F1EFE8; }
    .report-toolbar .btn-primary {
        display: inline-flex; align-items: center; gap: 6px; border: none; background: var(--accent);
        color: #FFF; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 500; text-decoration: none;
    }
    .report-toolbar .btn-primary:hover { filter: brightness(1.06); }
    .report-toolbar .zero-toggle {
        display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px;
        color: var(--ink-soft); cursor: pointer; margin-right: auto; user-select: none;
    }
    .report-toolbar .zero-toggle input { accent-color: var(--accent); width: 14px; height: 14px; }
    .report-col-bar {
        display: flex; justify-content: space-between; align-items: center;
        background: #E6E4DC; padding: 10px 14px;
        margin: 0 -14px; border-radius: 4px 4px 0 0;
        font-size: 7.5px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase;
    }
    .report-section-bar {
        display: flex; justify-content: space-between; align-items: center;
        background: #FAF9F5; padding: 9px 14px;
        margin: 0 -14px; border-bottom: 1px solid #E6E4DC;
        font-size: 8px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase;
    }
    .report-line {
        display: flex; justify-content: space-between; align-items: baseline;
        padding: 9px 0; border-bottom: 1px solid #E6E4DC; font-size: 9.5px;
    }
    .report-line .code {
        color: var(--ink-soft); font-size: 8.5px; margin-right: 8px; font-weight: 600;
    }
    .report-line .amt { font-variant-numeric: tabular-nums; }
    .report-line.zero .code, .report-line.zero .amt { color: var(--ink-muted); }
    .report-subtotal {
        display: flex; justify-content: space-between;
        font-size: 10px; font-weight: 600; padding: 12px 0 0;
        margin-top: 4px; margin-bottom: 24px;
        border-top: 1.5px dashed var(--border-strong);
    }
    .report-total {
        display: flex; justify-content: space-between; align-items: baseline;
        margin-top: 30px; padding-top: 18px;
        border-top: 2px solid #2C2C2A;
    }
    .report-total .lbl { font-size: 11px; font-weight: 700; }
    .report-total .val { font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; color: #2C2C2A; }
    .report-footer {
        display: flex; justify-content: space-between;
        font-size: 8px; color: var(--ink-muted); margin-top: 34px;
        padding-top: 14px; border-top: 1px solid #E6E4DC;
    }
    /* §6.1 shared chrome is brand-only here — the report fragments own
       their own .report-head (company / title / range). */
    .report-card-wrap { max-width: 760px; margin: 0 auto; }
    @media print {
        body { background: #fff; padding: 0; }
        .report-toolbar, .report-print-hide { display: none !important; }
        .report-card { border: none; border-radius: 0; padding: 0; max-width: none; }
        .report-col-bar, .report-section-bar { margin: 0; border-radius: 0 !important; }
    }
</style>
</head>
<body>
    <div class="report-card-wrap">
        <div class="report-card">
            {!! $content !!}
        </div>
        @include('components.pdf.chrome', ['part' => 'footer', 'pageLabel' => $title ?? config('app.name', 'CamelotBooks')])
    <script>
        function toggleZeroRows() {
            const show = document.getElementById('reportZeroToggle').checked;
            document.querySelectorAll('.report-line.zero, .report-table tbody tr.zero').forEach(function(el) {
                el.style.display = show ? '' : 'none';
            });
        }
    </script>
</body>
</html>
