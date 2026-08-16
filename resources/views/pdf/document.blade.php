{{-- ==================================================================
     pdf/document.blade.php — ONE template for ALL 9 PDF documents
     Payload (from controller / view composer):
       $type, $title, $titleSmall, $number, $pdfMode
       $partyLabel, $partyName, $partyLines[]
       $detailsLabel, $details[]  = ['l'=>…, 'v'=>…, 'chip'=>?(bool)]
       $cols[]       = ['label'=>…, 'width'=>…, 'num'=>?(bool), 'amt'=>?(bool)]
       $lines[]      = array of cells aligned with $cols
       $totals[]     = ['label'=>…, 'value'=>float, 'hideZero'=>?(bool)]
       $grandLabel, $grand, $words
       $notes[]      = ['label'=>…, 'body'=>…, 'list'=>?(bool)]
       $sigs[]       = ['name'=>…, 'role'=>…]
================================================================== --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $title }} {{ $number }} — {{ config('app.name', 'CamelotBooks') }}</title>
<style>
  @page { size: A4 portrait; margin: 0; }
  @font-face {
    font-family: 'Inter';
    font-style: normal; font-weight: 400;
    src: url('{{ $fontDir }}/inter/Inter-Regular.ttf') format('truetype');
  }
  @font-face {
    font-family: 'Inter';
    font-style: normal; font-weight: 500;
    src: url('{{ $fontDir }}/inter/Inter-Medium.ttf') format('truetype');
  }
  @font-face {
    font-family: 'Inter';
    font-style: normal; font-weight: 600;
    src: url('{{ $fontDir }}/inter/Inter-SemiBold.ttf') format('truetype');
  }
  @font-face {
    font-family: 'Inter';
    font-style: normal; font-weight: 700;
    src: url('{{ $fontDir }}/inter/Inter-Bold.ttf') format('truetype');
  }
  @font-face {
    font-family: 'Inter';
    font-style: normal; font-weight: 800;
    src: url('{{ $fontDir }}/inter/Inter-ExtraBold.ttf') format('truetype');
  }
  @font-face {
    font-family: 'Inter';
    font-style: normal; font-weight: 900;
    src: url('{{ $fontDir }}/inter/Inter-Black.ttf') format('truetype');
  }
  @font-face {
    font-family: 'Inter';
    font-style: italic; font-weight: 400;
    src: url('{{ $fontDir }}/inter/Inter-Italic.ttf') format('truetype');
  }
  @font-face {
    font-family: 'Inter';
    font-style: italic; font-weight: 700;
    src: url('{{ $fontDir }}/inter/Inter-BoldItalic.ttf') format('truetype');
  }
  :root{
    --navy-700:#24384f;--navy-800:#182a3e;--navy-900:#132234;--navy-200:#cdd7e2;
    --gold-500:#b6913f;--gold-600:#96742c;--gold-700:#8f6f2a;
    --ink:#1f2937;--mut:#6b7280;--line:#e5e9f0;--card:#F8FAFC;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:Inter,'Segoe UI',system-ui,sans-serif;color:var(--ink);font-size:9.5px;
    -webkit-print-color-adjust:exact;print-color-adjust:exact}
  body:not(.pdf){background:#e8ecf3;padding:40px 16px}
  /* PDF type scale — spec §4: tabular-nums on every figure */
  .num,.amt,.trow .v,.gt .v,.doc-num,.mgrid .v{font-variant-numeric:tabular-nums}

  /* sheet + footer lock */
  .sheet{position:relative;width:800px;margin:0 auto;background:#fff;overflow:hidden;
    display:flex;flex-direction:column;min-height:296mm}
  body:not(.pdf) .sheet{box-shadow:0 1px 2px rgba(16,24,40,.08),0 24px 64px -16px rgba(8,15,26,.35)}
  body.pdf .sheet{width:100%;display:block;min-height:auto;padding-bottom:14mm}

  /* header + footer chrome live in components/pdf/chrome.blade.php (.cbp-*) */

  /* meta */
  .meta{display:grid;grid-template-columns:.8fr 1.45fr;gap:16px;padding:24px 40px 4px}
  .mbox{border:1px solid var(--line);border-radius:10px;padding:14px 16px;background:var(--card)}
  .ml{font-size:7.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--gold-700);margin-bottom:7px}
  .mbox .cn{font-size:12px;font-weight:700;color:var(--navy-800)}
  .mbox .cl{margin-top:5px;font-size:9.5px;line-height:1.6;color:var(--mut)}
  .mgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:9px 14px}
  .mgrid .l{font-size:7.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#94a3b8}
  .mgrid .v{margin-top:2px;font-size:9px;font-weight:600;color:var(--ink);white-space:nowrap}
  .chip{display:inline-flex;margin-top:2px;padding:3px 10px;border-radius:999px;background:rgba(220,38,38,.07);
    border:1px solid rgba(220,38,38,.3);color:#dc2626;font-size:8px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}

  /* ledger */
  .ledger{margin:18px 40px 0;border:1px solid var(--line);border-radius:10px;overflow:hidden}
  table{width:100%;border-collapse:collapse;font-size:9.5px}
  thead th{background:linear-gradient(180deg,var(--navy-700),var(--navy-800) 60%,var(--navy-900));color:var(--navy-200);
    text-align:left;font-size:7.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;padding:9px 11px;
    box-shadow:inset 0 -1px 0 rgba(190,152,72,.5)}
  thead th.num{text-align:right}
  tbody td{padding:9px 11px;border-bottom:1px solid var(--line);vertical-align:top}
  tbody tr:nth-child(even) td{background:#f2f5f9}
  tbody tr:last-child td{border-bottom:none}
  td.num{text-align:right}
  .ic-code{font-size:9px;color:var(--navy-700);font-weight:700}
  .ic-name{font-weight:700;color:var(--ink)}
  .ic-desc{color:var(--mut);font-size:9px;margin-top:2px}
  td .amt{font-weight:700;color:var(--ink)}

  /* totals */
  .tot-wrap{display:flex;justify-content:space-between;gap:24px;padding:16px 40px 0}
  .words{max-width:380px}
  .words .l{font-size:7.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#94a3b8}
  .words .v{margin-top:5px;font-size:8.5px;font-style:italic;color:var(--mut);line-height:1.6}
  .totals{width:280px}
  .trow{display:flex;justify-content:space-between;padding:6px 2px;border-bottom:1px solid var(--line);font-size:9.5px;color:var(--mut)}
  .trow .v{font-weight:600;color:var(--ink)}
  .gt{margin-top:9px;display:flex;justify-content:space-between;align-items:center;padding:11px 15px;border-radius:9px;
    background:linear-gradient(90deg,var(--navy-700),var(--navy-800) 60%,var(--navy-900));
    box-shadow:inset 0 1px 0 rgba(255,255,255,.08),inset 0 -1px 0 rgba(190,152,72,.5)}
  .gt .l{font-size:8px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--navy-200)}
  .gt .v{font-size:14px;font-weight:700;color:#f2f6fa}

  /* notes + sigs */
  .notes{display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:20px 40px 0}
  .notes.one{grid-template-columns:1fr}
  .nblk .l{font-size:7.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--gold-700);margin-bottom:6px}
  .nblk .v{font-size:9px;line-height:1.7;color:var(--mut)}
  .nblk .v li{margin-left:14px}
  .sigs{display:grid;grid-template-columns:1fr 1fr;gap:56px;padding:40px 40px 6px}
  .sigs.c3{grid-template-columns:repeat(3,1fr);gap:28px}
  .sigs.c4{grid-template-columns:repeat(4,1fr);gap:20px}
  .sig{border-top:1.5px solid var(--navy-700);padding-top:7px}
  .sig .n{font-size:10px;font-weight:800;color:var(--ink)}
  .sig .r{font-size:7.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--mut);margin-top:2px}

  /* footer chrome lives in components/pdf/chrome.blade.php (.cbp-foot) */
</style>
</head>
<body class="{{ ($pdfMode ?? false) ? 'pdf' : '' }}">
<div class="sheet">

  @include('components.pdf.chrome', [
      'part' => 'header',
      'title' => $title,
      'number' => $number,
      'titleSmall' => ($titleSmall ?? false),
  ])

  <div class="meta">
    <div class="mbox">
      <div class="ml">{{ $partyLabel }}</div>
      <div class="cn">{{ $partyName }}</div>
      @if(!empty($partyLines))<div class="cl">{!! implode('<br>', $partyLines) !!}</div>@endif
    </div>
    <div class="mbox">
      <div class="ml">{{ $detailsLabel }}</div>
      <div class="mgrid">
        @foreach($details as $d)
          <div><div class="l">{{ $d['l'] }}</div>
            @if(!empty($d['chip']))<div class="v"><span class="chip">{{ $d['v'] }}</span></div>
            @else<div class="v">{{ $d['v'] }}</div>@endif
          </div>
        @endforeach
      </div>
    </div>
  </div>

  @if(!empty($cols))
  <div class="ledger">
    <table>
      <thead><tr>
        @foreach($cols as $c)
          <th class="{{ ($c['num'] ?? false) ? 'num' : '' }}" style="width:{{ $c['width'] }}">{{ $c['label'] }}</th>
        @endforeach
      </tr></thead>
      <tbody>
        @forelse($lines as $line)
        <tr>
          @foreach($cols as $i => $c)
            @if($i == 0)<td class="ic-code">{{ $line[$i] }}</td>
            @elseif($i == 1)<td class="ic-name">{{ $line[$i] }}</td>
            @elseif($i == 2)<td class="ic-desc">{{ $line[$i] }}</td>
            @elseif($c['amt'] ?? false)<td class="num"><span class="amt">{{ $line[$i] }}</span></td>
            @else<td class="{{ ($c['num'] ?? false) ? 'num' : '' }}">{{ $line[$i] }}</td>@endif
          @endforeach
        </tr>
        @empty
        <tr><td colspan="{{ count($cols) }}" style="text-align:center;color:#94a3b8">No line items.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @endif

  @if(!empty($totals) || $grand)
  <div class="tot-wrap">
    <div class="words"><div class="l">{{ $wordsLabel ?? 'Amount In Words' }}</div><div class="v">{{ $words }}</div></div>
    <div class="totals">
      @foreach(($totals ?? []) as $t)
        @if(!(($t['hideZero'] ?? false) && $t['value'] == 0))
        <div class="trow"><span>{{ $t['label'] }}</span><span class="v">{{ number_format($t['value'], 2) }}</span></div>
        @endif
      @endforeach
      <div class="gt"><span class="l">{{ $grandLabel }}</span><span class="v">K{{ number_format($grand, 2) }}</span></div>
    </div>
  </div>
  @endif

  @if(!empty($notes))
  <div class="notes{{ count($notes) == 1 ? ' one' : '' }}">
    @foreach($notes as $n)
      <div class="nblk"><div class="l">{{ $n['label'] }}</div>
        @if($n['list'] ?? false)<div class="v"><ul>@foreach($n['body'] as $li)<li>{{ $li }}</li>@endforeach</ul></div>
        @else<div class="v">{{ $n['body'] }}</div>@endif
      </div>
    @endforeach
  </div>
  @endif

  <div class="sigs{{ count($sigs) == 3 ? ' c3' : (count($sigs) == 4 ? ' c4' : '') }}">
    @foreach($sigs as $s)
      <div class="sig"><div class="n">{{ $s['name'] ?: '&nbsp;' }}</div><div class="r">{{ $s['role'] }}</div></div>
    @endforeach
  </div>

  @include('components.pdf.chrome', [
      'part' => 'footer',
      'contact' => config('app.company_name', 'CamelotBooks') . ' Ltd · PO Box 123, Lilongwe, Malawi · +265 1 777 222 · accounts@camelotbooks.mw · Reg CS/149593',
      'pageLabel' => ($pdfMode ?? false) ? '' : ($title . ' · PAGE 1 OF 1'),
      'fixed' => ($pdfMode ?? false),
  ])

</div>

@if(($pdfMode ?? false))
<script type="text/php">
    $font = $fontMetrics->getFont('inter', 'bold');
    $size = 6;
    $label = '{{ addslashes($title) }} · PAGE {PAGE_NUM} OF {PAGE_COUNT}';
    $width = $fontMetrics->getTextWidth($label, $font, $size);
    $x = $pdf->get_width() - 30 - $width;
    $y = $pdf->get_height() - 11 - $pdf->get_font_height($font, $size);
    $pdf->page_text($x, $y, $label, $font, $size, [16, 124, 123]);
</script>
@endif
</body>
</html>