{{--
    JOURNAL VOUCHER — standalone print page.

    Opened in a NEW TAB from the journal entry detail toolbar. This page IS the
    on-screen A4 preview: the sheet is rendered live at 794x1123 and only the
    toolbar is screen-only (hidden by @media print in journal-voucher.css).

    Three affordances:
      Back        -> the entry this voucher came from
      Download PDF -> server-rendered DomPDF file (accounting.journal-entries.print-pdf).
                     A real application/pdf attachment — NOT window.print()'s
                     "Save as PDF", which needs the user to drive the print dialog.
      Print       -> window.print(), i.e. the browser's own print PREVIEW, from
                     which the user confirms, changes destination or copies.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $journalEntry->journal_number }} — Journal Voucher</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/journal-voucher.css'])
</head>
{{-- `.jv` carries the print-family custom properties the sheet reads.
     `glj-print-page` is a stable marker for this document, should this page ever
     load the shared app.js bundle (journal-detail.js bails without #glj-app, so
     its Ctrl+P hijack must never reach a tab that is already the preview). --}}
<body class="jv-page jv glj-print-page">
    {{-- screen-only toolbar: never printed (journal-voucher.css @media print) --}}
    <div class="jv-tbar">
        <a href="{{ route('accounting.journal-entries.show', $journalEntry) }}" class="jv-back">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            Back to entry
        </a>
        <span class="jv-title">
            Journal Voucher
            <span class="chip">{{ $journalEntry->journal_number }}</span>
        </span>
        <span class="jv-spacer"></span>
        <span class="jv-note">A4 · Portrait · preview below</span>
        <a href="{{ route('accounting.journal-entries.print-pdf', $journalEntry) }}"
           class="jv-btn"
           download="{{ $pdfFilename }}"
           id="jv-download-pdf">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
            Download PDF
        </a>
        <button type="button" class="jv-btn primary" id="jv-print">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
            Print
        </button>
    </div>

    {{-- The A4 document. Identical markup to the sheet verified in phase 61. --}}
    <div class="glj-sheetwrap">
        @include('accounting.journal-entries._voucher-sheet')
    </div>

    <script>
        (function () {
            var btn = document.getElementById('jv-print');
            if (!btn) return;
            btn.addEventListener('click', function () {
                /* Opens the browser's print preview (Chrome/Edge show a full
                   preview + destination picker; Safari shows the sheet then the
                   dialog). Nothing is printed until the user confirms there. */
                window.print();
            });
        })();
    </script>
</body>
</html>