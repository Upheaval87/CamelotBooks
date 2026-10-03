{{--
    JOURNAL VOUCHER — DomPDF template.

    Same anatomy as _voucher-sheet.blade.php (header block, 8-cell meta grid,
    lines table + 2 blank ruled rows + totals + amount in words, narration,
    3 signatures, audit footer), rendered with TABLES instead of flexbox/grid
    because DomPDF supports neither. That is why this file cannot simply
    @include the sheet partial: the markup is shared through the payload
    (JournalEntryController::voucherPayload), not the template.

    Expects the same variables as the sheet partial, plus $pdfFilename.
--}}
@php
    /* DomPDF cannot resolve CSS custom properties or calc(), and its font
       metrics come from these files (see resources/css/journal-voucher.css for
       the screen-side colour tokens). */
    $fontDir = 'file://' . str_replace('\\', '/', storage_path('fonts')) . '/inter';
    $isRev = $isReversed;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $journalEntry->journal_number }} — Journal Voucher</title>
<style>
  @page { size: A4 portrait; margin: 0; }
  @font-face { font-family: 'Inter'; font-style: normal; font-weight: 400; src: url('{{ $fontDir }}/Inter-Regular.ttf') format('truetype'); }
  @font-face { font-family: 'Inter'; font-style: normal; font-weight: 500; src: url('{{ $fontDir }}/Inter-Medium.ttf') format('truetype'); }
  @font-face { font-family: 'Inter'; font-style: normal; font-weight: 600; src: url('{{ $fontDir }}/Inter-SemiBold.ttf') format('truetype'); }
  @font-face { font-family: 'Inter'; font-style: normal; font-weight: 700; src: url('{{ $fontDir }}/Inter-Bold.ttf') format('truetype'); }
  @font-face { font-family: 'Inter'; font-style: normal; font-weight: 800; src: url('{{ $fontDir }}/Inter-ExtraBold.ttf') format('truetype'); }
  @font-face { font-family: 'Inter'; font-style: normal; font-weight: 900; src: url('{{ $fontDir }}/Inter-Black.ttf') format('truetype'); }

  * { margin: 0; padding: 0; }
  body {
    font-family: 'Inter', sans-serif;
    font-size: 9.5px;
    color: #3e5658;
    padding: 12mm 14mm 10mm;
  }
  .mono { font-family: 'DejaVu Sans Mono', monospace; }

  /* top accent rule (replaces .glj-sheet::before) */
  .accent { height: 4px; background-color: #0e7473; }

  /* header */
  .head { background-color: #f0f5f5; border-bottom: 1px solid #dce7e5; padding: 10mm 6mm 6mm; }
  .head td { vertical-align: top; }
  .mark {
    width: 13mm; height: 13mm; background-color: #0e7473; color: #ffffff;
    font-size: 15px; font-weight: 900; text-align: center;
  }
  .bn { font-size: 13px; font-weight: 900; color: #13292c; }
  .bl { font-size: 8px; font-weight: 600; color: #5f7476; line-height: 14px; }
  .doc { text-align: right; }
  .doc .dt { font-size: 8px; font-weight: 800; letter-spacing: 3px; color: #0e7473; }
  .doc .dn { font-size: 16px; font-weight: 800; color: #13292c; margin-top: 3px; }
  .doc .dp { font-size: 8px; font-weight: 600; color: #8aa3a2; margin-top: 4px; }
  .stamp {
    margin-top: 4px; padding: 2px 8px; background-color: #eaf3f3;
    border: 1px solid #9dc3c1; font-size: 8px; font-weight: 800;
    letter-spacing: 2px; color: #0e7473;
  }
  .stamp.rev { background-color: #fdf1e3; border-color: #e3b478; color: #b45309; }

  /* meta grid (4 x 2) */
  table.meta { width: 100%; border-collapse: collapse; }
  table.meta td {
    width: 25%; padding: 7px 10px; vertical-align: top;
    border-right: 1px solid #e7efed; border-bottom: 1px solid #e7efed;
  }
  table.meta td.r { border-right: none; }
  table.meta td.l0 { padding-left: 6mm; }
  table.meta .l { font-size: 7px; font-weight: 800; letter-spacing: 2px; color: #8aa3a2; }
  table.meta .v { font-size: 10px; font-weight: 700; color: #13292c; padding-top: 2px; }
  table.meta .v.acc { color: #0e7473; }

  /* lines */
  .lbl { font-size: 8px; font-weight: 800; letter-spacing: 2px; color: #5f7476; padding: 8mm 6mm 3mm; }
  table.lines { width: 100%; border-collapse: collapse; }
  table.lines thead th {
    background-color: #0e7473; color: #ffffff; font-size: 7.5px; font-weight: 800;
    letter-spacing: 2px; padding: 6px 8px; text-align: left;
  }
  table.lines thead th.r { text-align: right; }
  table.lines tbody td { padding: 7px 8px; border-bottom: 1px solid #e7efed; vertical-align: top; }
  table.lines td.no { width: 8mm; color: #8aa3a2; font-weight: 700; }
  table.lines td.num { text-align: right; font-weight: 700; color: #13292c; white-space: nowrap; }
  table.lines td.code { font-size: 10px; font-weight: 800; color: #13292c; }
  table.lines td.aname { font-size: 8.5px; font-weight: 600; color: #5f7476; padding-top: 1px; }
  table.lines td.dash { color: #c6d6d4; font-weight: 600; }
  table.lines tr.blank td { height: 26px; }
  table.lines tr.totals td { background-color: #f0f5f5; border-top: 3px double #13292c; border-bottom: none; padding: 7px 8px; }
  table.lines tr.totals .tl { font-size: 8px; font-weight: 900; letter-spacing: 2px; color: #13292c; }
  table.lines tr.totals td.num { font-size: 11px; font-weight: 900; }
  table.lines tr.words td { background-color: #fbfdfc; border-bottom: 1px solid #dce7e5; padding: 6px 8px; }
  .bal { float: right; font-size: 8.5px; font-weight: 800; color: #0e7473; }
  .bal.bad { color: #b91c1c; }
  .wl { font-size: 7.5px; font-weight: 800; letter-spacing: 2px; color: #8aa3a2; }
  .wv { font-size: 9.5px; font-weight: 700; font-style: italic; color: #3e5658; }

  /* narration */
  .narr { margin: 6mm 6mm 0; border: 1px solid #dce7e5; }
  .narr .nh { background-color: #f0f5f5; border-bottom: 1px solid #e7efed; padding: 5px 8px; font-size: 7.5px; font-weight: 800; letter-spacing: 2px; color: #5f7476; }
  .narr .nb { padding: 7px 8px; font-size: 9.5px; line-height: 15px; }

  /* signatures */
  table.sigs { width: 100%; border-collapse: collapse; margin-top: 10mm; }
  table.sigs td { width: 33.33%; text-align: center; padding: 0 5mm; }
  table.sigs .rule { border-bottom: 1px solid #13292c; height: 30px; }
  table.sigs .sl { padding-top: 5px; font-size: 7.5px; font-weight: 800; letter-spacing: 2px; color: #5f7476; }
  table.sigs .sd { padding-top: 2px; font-size: 8.5px; font-weight: 600; color: #8aa3a2; }

  /* audit footer
     DomPDF resolves position:fixed against the PAGE box, not the body content
     box, so `left:0; width:100%` would run the rule flush to both paper edges
     while every other block sits inside the body padding. Pin it to the same
     content column the browser preview prints at (@page margin 10mm, body
     padding 14mm sides). Horizontal padding lives on the cells, not the
     container, so the 182mm width is exact whichever box model is applied. */
  .foot {
    position: fixed; bottom: 10mm; left: 14mm; width: 182mm;
    border-top: 1px solid #dce7e5; background-color: #fbfdfc;
  }
  .foot table { width: 100%; border-collapse: collapse; }
  .foot td {
    font-size: 7.5px; font-weight: 600; color: #8aa3a2;
    vertical-align: middle; padding: 5px 6mm;
  }
  .foot td.mid { text-align: center; }
  .foot td.end { text-align: right; font-weight: 800; color: #5f7476; white-space: nowrap; }
</style>
</head>
<body>
<div class="accent"></div>

<table class="head" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:18mm"><div class="mark">CB</div></td>
        <td>
            <div class="bn">{{ $companyName ?: 'Company' }}</div>
            @if ($addressBits)
                <div class="bl">{{ implode(' · ', $addressBits) }}</div>
            @endif
            @if ($taxId || $companyPhone)
                <div class="bl">
                    @if ($taxId)Tax ID: {{ $taxId }}@endif
                    @if ($taxId && $companyPhone) · @endif
                    @if ($companyPhone){{ $companyPhone }}@endif
                </div>
            @endif
        </td>
        <td class="doc" style="width:62mm">
            <div class="dt">JOURNAL VOUCHER</div>
            <div class="dn">{{ $journalEntry->journal_number }}</div>
            <div class="stamp {{ $isRev ? 'rev' : '' }}">{{ $statusLabel }}</div>
            <div class="dp">Printed {{ $printedStamp }}</div>
        </td>
    </tr>
</table>

<table class="meta" cellpadding="0" cellspacing="0">
    <tr>
        <td class="l0"><div class="l">JOURNAL DATE</div><div class="v">{{ $fmtDate($journalEntry->date) }}</div></td>
        <td><div class="l">PERIOD</div><div class="v">{{ $periodLabel ?: '—' }}</div></td>
        <td><div class="l">TYPE</div><div class="v">{{ $typeLabel }}</div></td>
        <td class="r"><div class="l">SOURCE</div><div class="v acc">{{ $sourceLabel }}</div></td>
    </tr>
    <tr>
        <td class="l0"><div class="l">BRANCH</div><div class="v">{{ $journalEntry->branch?->name ?: '—' }}</div></td>
        <td><div class="l">CURRENCY</div><div class="v">{{ $currencyCode ? $currencyCode . ' (' . $cs . ')' : $cs }}</div></td>
        <td><div class="l">CREATED</div><div class="v">{{ $journalEntry->created_at ? $fmtDate($journalEntry->created_at) : '—' }}</div></td>
        <td class="r"><div class="l">POSTED</div><div class="v">{{ $journalEntry->posted_at ? $fmtDate($journalEntry->posted_at) : '—' }}</div></td>
    </tr>
</table>

<div class="lbl">JOURNAL LINES</div>
<table class="lines" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th style="width:6%">#</th>
            <th style="width:30%">ACCOUNT</th>
            <th style="width:30%">DESCRIPTION</th>
            <th class="r" style="width:17%">DEBIT ({{ $cs }})</th>
            <th class="r" style="width:17%">CREDIT ({{ $cs }})</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($journalEntry->lines as $i => $line)
            <tr>
                <td class="no">{{ $i + 1 }}</td>
                <td>
                    <div class="code">{{ $line->account?->code ?: '—' }}</div>
                    <div class="aname">{{ $line->account?->name ?: '' }}</div>
                </td>
                <td>{{ $line->memo ?: '—' }}</td>
                <td class="num">{!! $line->debit > 0 ? $fmtMoney($line->debit) : '<span class="dash">—</span>' !!}</td>
                <td class="num">{!! $line->credit > 0 ? $fmtMoney($line->credit) : '<span class="dash">—</span>' !!}</td>
            </tr>
        @empty
            <tr class="blank">
                <td class="no"></td>
                <td><div class="aname">No lines</div></td>
                <td></td>
                <td class="num"></td>
                <td class="num"></td>
            </tr>
        @endforelse

        {{-- R4: two unnumbered blank ruled rows, same as the screen sheet --}}
        <tr class="blank"><td class="no"></td><td></td><td></td><td class="num"></td><td class="num"></td></tr>
        <tr class="blank"><td class="no"></td><td></td><td></td><td class="num"></td><td class="num"></td></tr>

        <tr class="totals">
            <td colspan="3"><span class="tl">TOTALS</span></td>
            <td class="num">{{ $fmtMoney($totalDebit) }}</td>
            <td class="num">{{ $fmtMoney($totalCredit) }}</td>
        </tr>
        <tr class="words">
            <td colspan="5">
                <span class="bal {{ $isBalanced ? '' : 'bad' }}">{{ $isBalanced ? '✓ Balanced' : 'Out of balance by ' . $fmtMoney($variance) }}</span>
                <span class="wl">AMOUNT IN WORDS</span>
                <span class="wv">{{ $amountWords }}</span>
            </td>
        </tr>
    </tbody>
</table>

<div class="narr">
    <div class="nh">NARRATION / DESCRIPTION</div>
    <div class="nb">{{ $journalEntry->memo ?: 'No description provided for this journal entry.' }}</div>
</div>

<table class="sigs" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <div class="rule"></div>
            <div class="sl">PREPARED BY</div>
            <div class="sd">{{ $journalEntry->created_at?->format('d M Y · H:i') }}</div>
        </td>
        <td>
            <div class="rule"></div>
            <div class="sl">AUTHORISED BY</div>
            <div class="sd">Name · Date</div>
        </td>
        <td>
            <div class="rule"></div>
            <div class="sl">POSTED BY</div>
            <div class="sd">{{ $journalEntry->posted_at?->format('d M Y · H:i') ?: 'Name · Date' }}</div>
        </td>
    </tr>
</table>

<div class="foot">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:30%">AUDIT · created {{ $journalEntry->created_at ? $fmtDate($journalEntry->created_at) : '—' }}{{ $journalEntry->posted_at ? ' · posted ' . $fmtDate($journalEntry->posted_at) : '' }}</td>
            <td class="mid" style="width:45%">Computer-generated journal voucher — valid without signature while status is POSTED</td>
            <td class="end" style="width:25%">{{ $journalEntry->journal_number }} · {{ $typeLabel }}</td>
        </tr>
    </table>
</div>
</body>
</html>