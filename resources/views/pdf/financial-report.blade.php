{{-- ==================================================================
     pdf/financial-report.blade.php — §9 shared editorial PDF template
     Renders all 5 financial reports (IS / SFP / CF / AR Aging / AP Aging)
     in a clean, consistent A4 layout.

     DATA CONTRACT (passed by the controller):
       $title       — report title (e.g. "Income Statement")
       $periodLabel — period description (e.g. "01 Jan – 31 Aug 2026")
       $currency    — currency code (e.g. "MWK")
       $columns     — array of column labels (e.g. ["2026","2025","Variance","%"])
                      or for aging: ["Current","1-30","31-60","61-90","90+","Total"]
       $sections    — array of sections, each with:
                        'label'   — section heading
                        'items'   — array of rows, each with:
                          'label'     — row description
                          'code'      — optional account code
                          'values'    — array of formatted values (one per column)
                          'isSubtotal'— bool (600 weight)
                          'isTotal'   — bool (700 weight, double-rule)
                          'isSection' — bool (section heading style)
                          'isItalic'  — bool (e.g. GP margin row)
                          'isNegRed'  — bool (red text, e.g. 90+ aging)
       $totals     — optional grand-total row (same shape as item)
       $balanceCheck — optional ['text' => '...', 'balanced' => true]
       $signOff    — bool, show sign-off lines (default true)
       $pdfMode    — bool, for DomPDF rendering

     §9.9 PAGINATION: dompdf automatically repeats <thead> on page breaks.
     We use a wrapper table with <thead> for the column headers so they
     repeat automatically. The header/footer chrome is applied via
     components/pdf/chrome included from the generating controller.

     RULES: no meta block; actual-year column headers; footer matches
     Invoice PDF; negatives grey parentheses; red only for 90+; sign-off;
     one shared template for all five reports.
================================================================= --}}
@php
    $title       = $title       ?? 'Financial Report';
    $periodLabel = $periodLabel ?? '';
    $currency    = $currency    ?? 'MWK';
    $columns     = $columns     ?? [];
    $sections    = $sections    ?? [];
    $totals      = $totals      ?? null;
    $balanceCheck = $balanceCheck ?? null;
    $signOff     = $signOff     ?? true;
    $pdfMode     = $pdfMode     ?? false;
@endphp

<style>
  /* §9 PDF ENGINE — editorial financial report template */
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
    font-size: 9.5px; color: #111827; line-height: 1.4;
    background: #fff; padding: 0;
  }

  /* ── §9.3 TABLES ───────────────────────────────────────────── */
  .frp-table { width: 100%; border-collapse: collapse; margin: 0; }
  .frp-table thead th {
    font-size: 7.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; color: #6b7280;
    padding: 6px 8px; border-bottom: 1px solid #e5e7eb;
    text-align: left; vertical-align: bottom;
  }
  .frp-table thead th.frp-col-num {
    text-align: right; font-variant-numeric: tabular-nums;
  }
  .frp-table tbody td {
    font-size: 9.5px; padding: 5px 8px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
  }
  .frp-table tbody td.frp-col-num {
    text-align: right; font-variant-numeric: tabular-nums;
    white-space: nowrap;
  }
  .frp-table tbody td.frp-col-code {
    color: #6b7280; font-size: 8.5px; font-weight: 600;
    padding-right: 4px; width: 42px;
  }

  /* ── §9.3 SECTION HEADINGS (teal left tick) ────────────────── */
  .frp-row-section td {
    font-size: 9px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .06em; color: #111827;
    padding: 10px 8px 4px;
    border-bottom: none;
    border-left: 2px solid #128F8E;
    padding-left: 6px;
  }

  /* ── SUBTOTALS (600 weight, 1px top rule) ──────────────────── */
  .frp-row-subtotal td {
    font-weight: 600; font-size: 9.5px;
    padding: 6px 8px; border-top: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
  }

  /* ── GRAND TOTALS (700, 2px top rule, deep figures) ────────── */
  .frp-row-total td {
    font-weight: 700; font-size: 10px;
    padding: 8px 8px;
    border-top: 2px solid #111827;
    border-bottom: 2px solid #111827;
    color: #111827;
  }
  .frp-row-total td.frp-col-num { color: #0c3539; }

  /* ── DOUBLE-RULE row (final grand total) ───────────────────── */
  .frp-row-double td {
    border-top: 2px solid #111827;
    border-bottom: 3px double #111827;
  }

  /* ── ITALIC row (e.g. GP margin) ───────────────────────────── */
  .frp-row-italic td { font-style: italic; color: #374151; }

  /* ── NEGATIVE VALUES: grey parentheses ─────────────────────── */
  .frp-neg { color: #6b7280; }

  /* ── RED for 90+ aging only ─────────────────────────────────── */
  .frp-red { color: #b91c1c; }

  /* ── §9.4 BALANCE CHECK line ────────────────────────────────── */
  .frp-balance {
    margin: 12px 0 0; padding: 8px 12px;
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px;
    font-size: 9px; font-weight: 600; color: #15803d;
  }
  .frp-balance.frp-unbalanced {
    background: #fef2f2; border-color: #fecaca; color: #b91c1c;
  }
  .frp-balance .frp-check { font-weight: 800; margin-right: 4px; }

  /* ── §9.5 SIGN-OFF ─────────────────────────────────────────── */
  .frp-signoff {
    display: flex; gap: 40px; margin-top: 24px; padding-top: 16px;
  }
  .frp-sig-line {
    flex: 1; border-top: 1px solid #e5e7eb; padding-top: 6px;
    font-size: 7.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; color: #9ca3af;
  }

  /* ── PAGE BREAK CONTROL ─────────────────────────────────────── */
  .frp-page-break { page-break-before: always; }
  .frp-no-break { page-break-inside: avoid; }

  /* ── §9.6 FOOTER (dompdf fixed) ─────────────────────────────── */
  @if($pdfMode)
  .frp-footer {
    position: fixed; bottom: 0; left: 0; right: 0;
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 40px; font-size: 8px; color: #9ca3af;
    border-top: 1px solid #e5e7eb; background: #fff;
  }
  .frp-footer .frp-page-num {
    font-weight: 700; color: #128F8E; white-space: nowrap;
    font-variant-numeric: tabular-nums;
  }
  @endif
</style>

@if($pdfMode)
  {{-- ── §9.1 HEADER (repeated on every page via page_text callback) ── --}}
  @include('components.pdf.chrome', ['part' => 'header',
      'title' => strtoupper($title),
      'number' => $periodLabel,
      'titleSmall' => false,
  ])
@else
  {{-- ── HTML preview title ── --}}
  <h1 class="frp-preview-title" style="font-size:18px;font-weight:800;margin:0 0 4px;color:#0c3539;">{{ strtoupper($title) }}</h1>
  <p style="font-size:11px;color:#6b7280;margin:0 0 16px;">{{ $periodLabel }} | {{ $currency }}</p>
@endif

<table class="frp-table">
  @if(count($columns) > 0)
  <thead>
    <tr>
      <th style="width:42%">Description</th>
      @foreach($columns as $col)
        <th class="frp-col-num" style="{{ $loop->last ? 'width:14%' : '' }}">{{ $col }}</th>
      @endforeach
    </tr>
  </thead>
  @endif

  <tbody>
    @foreach($sections as $section)
      {{-- ── Section heading ── --}}
      @if($section['label'] ?? null)
        <tr class="frp-row-section">
          <td colspan="{{ count($columns) + 1 }}">{{ $section['label'] }}</td>
        </tr>
      @endif

      {{-- ── Section items ── --}}
      @foreach($section['items'] ?? [] as $item)
        @php
            $rowClass = '';
            if ($item['isSubtotal'] ?? false)  $rowClass = 'frp-row-subtotal';
            if ($item['isTotal'] ?? false)      $rowClass = 'frp-row-total';
            if (($item['isTotal'] ?? false) && ($item['isDoubleRule'] ?? false)) $rowClass .= ' frp-row-double';
            if ($item['isItalic'] ?? false)     $rowClass = 'frp-row-italic';
        @endphp
        <tr class="{{ $rowClass }}">
          <td class="{{ ($item['code'] ?? null) ? '' : 'frp-col-code' }}">
            @if($item['code'] ?? null)
              <span class="frp-col-code">{{ $item['code'] }}</span>
            @endif
            {{ $item['label'] }}
          </td>
          @foreach($item['values'] ?? [] as $val)
            @php
                $isNeg = is_string($val) && str_starts_with($val, '(') && str_ends_with($val, ')');
                $isRed = $item['isNegRed'] ?? false;
            @endphp
            <td class="frp-col-num {{ $isNeg ? 'frp-neg' : '' }} {{ $isRed ? 'frp-red' : '' }}">
              {{ $val }}
            </td>
          @endforeach
        </tr>
      @endforeach
    @endforeach

    {{-- ── Optional grand-total row ── --}}
    @if($totals)
      <tr class="frp-row-total frp-row-double">
        <td>{{ $totals['label'] ?? 'Total' }}</td>
        @foreach($totals['values'] ?? [] as $val)
          @php $isNeg = is_string($val) && str_starts_with($val, '(') && str_ends_with($val, ')'); @endphp
          <td class="frp-col-num {{ $isNeg ? 'frp-neg' : '' }}">{{ $val }}</td>
        @endforeach
      </tr>
    @endif
  </tbody>
</table>

{{-- ── §9.4 BALANCE CHECK (SFP / CF) ── --}}
@if($balanceCheck)
  <div class="frp-balance {{ ($balanceCheck['balanced'] ?? false) ? '' : 'frp-unbalanced' }}">
    <span class="frp-check">{{ ($balanceCheck['balanced'] ?? false) ? '✓' : '✗' }}</span>
    {{ $balanceCheck['text'] ?? '' }}
  </div>
@endif

{{-- ── §9.5 SIGN-OFF ── --}}
@if($signOff)
  <div class="frp-signoff">
    <div class="frp-sig-line">Prepared By</div>
    <div class="frp-sig-line">Authorised By — Signature &amp; Date</div>
  </div>
@endif

@if($pdfMode)
  @include('components.pdf.chrome', ['part' => 'footer',
      'contact' => 'www.camelotbooks.com · info@camelotbooks.com · +265 1 234 567',
      'pageLabel' => 'Page {PAGE_NUM} of {PAGE_COUNT}',
      'fixed' => true,
  ])
@endif
