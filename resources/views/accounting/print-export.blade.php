<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? 'Report' }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10pt; color: #222; padding: 20mm 15mm; }
    .header { margin-bottom: 12px; }
    .header .company { font-size: 16pt; font-weight: 700; }
    .header .report-title { font-size: 12pt; font-weight: 700; margin-top: 2px; }
    .header .period { font-size: 10pt; color: #555; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { padding: 4px 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #444; color: #fff; font-weight: 600; font-size: 9pt; }
    td { font-size: 9pt; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .row-bold { font-weight: 700; }
    .row-subtotal { font-weight: 700; border-top: 2px solid #222; }
    .row-grand { font-weight: 700; border-top: 3px double #222; font-size: 10pt; }
    .indent { padding-left: 24px; }
    .section-header { font-weight: 700; background: #f0f0f0; }
    .negative { color: #c0392b; }
    .footer { margin-top: 16px; font-size: 8pt; color: #888; text-align: center; }
    @media print {
        body { padding: 10mm 15mm; }
        .no-print { display: none !important; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; }
    }
</style>
</head>
<body>
    <div class="no-print" style="margin-bottom:12px; text-align:right;">
        <button onclick="window.print()" style="padding:6px 16px; font-size:10pt; cursor:pointer; background:#444; color:#fff; border:none; border-radius:4px;">Print / Save as PDF</button>
    </div>
    {!! $content !!}
    <div class="footer">Generated {{ now()->format('M d, Y H:i') }} &mdash; CamelotBooks</div>
</body>
</html>
