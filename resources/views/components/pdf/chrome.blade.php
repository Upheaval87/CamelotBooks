{{-- ==================================================================
     components/pdf/chrome.blade.php — shared §6.1 PDF header/footer chrome
     Renders the deep-teal brand band (30px "C" tile, CamelotBooks 12/700,
     tagline 7.5px grey, 26×3px teal accent bar) and the 8px footer.

     Usage (standalone HTML contexts — DomPDF pdf/document + browser-print):
       @include('components.pdf.chrome', ['part' => 'header',
           'title' => 'Invoice', 'number' => '№ INV-001', 'titleSmall' => false])
       … document body …
       @include('components.pdf.chrome', ['part' => 'footer',
           'contact' => '… contact line …', 'pageLabel' => 'Invoice · PAGE 1 OF 1',
           'fixed' => ($pdfMode ?? false)])

     The <style> block renders only on the header include so both parts
     share one CSS source with no duplicate style tags.
================================================================== --}}
@php
    $companyName = $companyName ?? config('app.company_name', 'CamelotBooks');
    $tagline     = $tagline     ?? config('app.company_tagline', 'Enterprise Accounting');
    $title       = $title       ?? '';
    $number      = $number      ?? '';
    $titleSmall  = $titleSmall  ?? false;
    $contact     = $contact     ?? $companyName . ' Ltd · accounts@' . strtolower(preg_replace('/[^A-Za-z]/', '', $companyName)) . '.com';
    $pageLabel   = $pageLabel   ?? $title;
    $fixed       = $fixed       ?? false;
@endphp
@if(($part ?? '') === 'header')
<style>
  /* §6.1 PDF chrome — shared brand band + footer */
  .cbp-head{position:relative;display:flex;justify-content:space-between;align-items:flex-start;gap:24px;
    padding:28px 40px 24px;color:#fff;
    background:linear-gradient(180deg,#11454b 0%,#0c3539 55%,#0a2e32 100%);
    box-shadow:inset 0 -1px 0 rgba(255,255,255,.06)}
  .cbp-brand{display:flex;gap:12px;align-items:center}
  .cbp-tile{width:30px;height:30px;border-radius:9px;display:block;text-align:center;line-height:30px;
    color:#0e3a3e;font-weight:800;font-size:13px;background:linear-gradient(180deg,#f4fbfb,#d9edee);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.6)}
  .cbp-name{font-size:12px;font-weight:700;letter-spacing:.02em;color:#fff}
  .cbp-tag{font-size:7.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;
    color:rgba(226,244,244,.55);margin-top:2px}
  .cbp-head-right{text-align:right;margin-left:auto}
  .cbp-title{font-size:11px;font-weight:800;letter-spacing:.22em;text-transform:uppercase;color:#e8fbfa}
  .cbp-title.sm{font-size:9.5px;letter-spacing:.16em}
  .cbp-num{margin-top:5px;font-size:10px;color:rgba(226,244,244,.75);font-variant-numeric:tabular-nums}
  .cbp-accent{position:absolute;left:40px;bottom:0;width:26px;height:3px;border-radius:2px;
    background:linear-gradient(90deg,#149897,#128f8e)}
  .cbp-foot{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-top:auto;
    padding:13px 40px;font-size:8px;color:#5f7476;border-top:1px solid #e2ecec;background:#fff}
  .cbp-foot .cbp-fr{font-weight:700;letter-spacing:.08em;color:#128f8e;white-space:nowrap;font-variant-numeric:tabular-nums}
  .cbp-foot.cbp-fixed{position:fixed;bottom:0;left:0;right:0;margin:0}
</style>
<header class="cbp-head">
  <div class="cbp-brand">
    <span class="cbp-tile">C</span>
    <div>
      <div class="cbp-name">{{ $companyName }}</div>
      <div class="cbp-tag">{{ $tagline }}</div>
    </div>
  </div>
  @if($title || $number)
  <div class="cbp-head-right">
    @if($title)<div class="cbp-title{{ $titleSmall ? ' sm' : '' }}">{{ $title }}</div>@endif
    @if($number)<div class="cbp-num">{{ $number }}</div>@endif
  </div>
  @endif
  <span class="cbp-accent"></span>
</header>
@else
<footer class="cbp-foot{{ $fixed ? ' cbp-fixed' : '' }}">
  <span>{{ $contact }}</span>
  <span class="cbp-fr">{{ $pageLabel }}</span>
</footer>
@endif
