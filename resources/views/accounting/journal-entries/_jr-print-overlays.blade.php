{{--
    JOURNALS REGISTER — in-page voucher previews.

    §5.1: one server-rendered A4 voucher sheet per printable row. The print
    action opens this overlay (z-200); the browser's own print dialog then
    isolates `.jr-print.on` via the @media print rules in app.css.
--}}
@foreach($vouchers as $voucherId => $voucher)
    <div class="jr-print" id="jr-print-{{ $voucherId }}"
         :class="{ on: printId === {{ $voucherId }} }"
         @click.self="closePrint()">
        <div class="jr-print-tbar">
            <span class="jr-print-t">Journal Voucher · {{ $voucher['journalEntry']->journal_number }}</span>
            <a class="jr-btn jr-btn-g" href="{{ route('accounting.journal-entries.print', $voucherId) }}"
               target="_blank" rel="noopener">Open standalone</a>
            <button type="button" class="jr-btn jr-btn-p" @click="doPrint()">🖨 Print</button>
            <button type="button" class="jr-btn jr-btn-g" @click="closePrint()">Close</button>
        </div>
        <div class="jr-print-sheet">
            @include('accounting.journal-entries._voucher-sheet', $voucher)
        </div>
    </div>
@endforeach
