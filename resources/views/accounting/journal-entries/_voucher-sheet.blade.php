{{--
    JOURNAL VOUCHER SHEET — the A4 document itself.

    Shared by the standalone print page (print.blade.php) and, structurally, by
    pdf.blade.php (same anatomy, table-based CSS because DomPDF has no
    flexbox/grid). Extracted verbatim from the old in-page preview overlay so the
    printed document is byte-identical to the sheet that shipped in phase 61.

    Expects (all from JournalEntryController::voucherPayload(), which also feeds
    the DomPDF view — one payload, so the preview and the PDF can never drift):
      $journalEntry  $companyName  $addressBits  $taxId  $companyPhone
      $statusLabel   $isReversed   $printedStamp
      $fmtDate  $fmtMoney  $periodLabel  $typeLabel  $sourceLabel
      $currencyCode  $cs  $totalDebit  $totalCredit  $isBalanced  $variance
      $amountWords
--}}
<div id="glj-sheet" class="glj-sheet">
    {{-- A§4.3 / A§5.1: rotated status watermark on EVERY voucher --}}
    <div class="glj-wm" aria-hidden="true"><span>{{ strtoupper($statusLabel) }}</span></div>

    {{-- 5.1 voucher header: company letterhead + document block --}}
    <div class="glj-vhead">
        <div class="glj-brand">
            <span class="mark">CB</span>
            <span>
                <span class="bn">{{ $companyName ?: 'Company' }}</span>
                @if ($addressBits)
                    <span class="bl">{{ implode(' · ', $addressBits) }}</span>
                @endif
                @if ($taxId || $companyPhone)
                    <span class="bl">
                        @if ($taxId)Tax ID: {{ $taxId }}@endif
                        @if ($taxId && $companyPhone) · @endif
                        @if ($companyPhone){{ $companyPhone }}@endif
                    </span>
                @endif
            </span>
        </div>
        <div class="glj-docblock">
            <div class="dt">Journal Voucher</div>
            <div class="dn">{{ $journalEntry->journal_number }}</div>
            <span class="glj-dstamp {{ $isReversed ? 'rev' : '' }}">
                <i></i>{{ $statusLabel }}
            </span>
            <div class="dp">Printed {{ $printedStamp }}</div>
        </div>
    </div>

    <div class="glj-sheetbody">
        {{-- 5.2 meta grid --}}
        <div class="glj-meta">
            <div class="cell">
                <div class="l">Journal Date</div>
                <div class="v">{{ $fmtDate($journalEntry->date) }}</div>
            </div>
            <div class="cell">
                <div class="l">Period</div>
                <div class="v">{{ $periodLabel ?: '—' }}</div>
            </div>
            <div class="cell">
                <div class="l">Type</div>
                <div class="v">{{ $typeLabel }}</div>
            </div>
            <div class="cell">
                <div class="l">Source</div>
                <div class="v mono">{{ $sourceLabel }}</div>
            </div>
            <div class="cell">
                <div class="l">Branch</div>
                <div class="v">{{ $journalEntry->branch?->name ?: '—' }}</div>
            </div>
            <div class="cell">
                <div class="l">Currency</div>
                <div class="v">{{ $currencyCode ? $currencyCode . ' (' . $cs . ')' : $cs }}</div>
            </div>
            <div class="cell">
                <div class="l">Created</div>
                <div class="v">{{ $journalEntry->created_at ? $fmtDate($journalEntry->created_at) : '—' }}</div>
            </div>
            <div class="cell">
                <div class="l">Posted</div>
                <div class="v">{{ $journalEntry->posted_at ? $fmtDate($journalEntry->posted_at) : '—' }}</div>
            </div>
        </div>

        {{-- 5.3 lines table --}}
        <div class="glj-lines">
            <div class="lt">Journal Lines</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:6%">#</th>
                        <th style="width:30%">Account</th>
                        <th style="width:30%">Description</th>
                        <th class="r" style="width:17%">Debit ({{ $cs }})</th>
                        <th class="r" style="width:17%">Credit ({{ $cs }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($journalEntry->lines as $i => $line)
                        <tr>
                            <td class="no">{{ $i + 1 }}</td>
                            <td class="acct">
                                <span class="code">{{ $line->account?->code ?: '—' }}</span>
                                <span class="aname">{{ $line->account?->name ?: '' }}</span>
                            </td>
                            <td class="dcell">{{ $line->memo ?: '—' }}</td>
                            <td class="num">{!! $line->debit > 0 ? $fmtMoney($line->debit) : '<span class="dash">—</span>' !!}</td>
                            <td class="num">{!! $line->credit > 0 ? $fmtMoney($line->credit) : '<span class="dash">—</span>' !!}</td>
                        </tr>
                    @empty
                        <tr class="blank">
                            <td class="no"></td>
                            <td class="acct"><span class="aname">No lines</span></td>
                            <td class="dcell"></td>
                            <td class="num"></td>
                            <td class="num"></td>
                        </tr>
                    @endforelse

                    {{-- R4: the two blank ruled rows are a fixed part of the voucher anatomy --}}
                    @for ($b = 0; $b < 2; $b++)
                        <tr class="blank">
                            <td class="no"></td>
                            <td class="acct"></td>
                            <td class="dcell"></td>
                            <td class="num"></td>
                            <td class="num"></td>
                        </tr>
                    @endfor

                    <tr class="totals">
                        <td colspan="3"><span class="tl">Totals</span></td>
                        <td class="num">{{ $fmtMoney($totalDebit) }}</td>
                        <td class="num">{{ $fmtMoney($totalCredit) }}</td>
                    </tr>
                    <tr class="words">
                        <td colspan="5">
                            <span class="glj-balnote {{ $isBalanced ? '' : 'bad' }}">
                                {{ $isBalanced ? '✓ Balanced' : 'Out of balance by ' . $fmtMoney($variance) }}
                            </span>
                            <span class="glj-words-l">Amount in words</span>
                            <span class="glj-words-v">{{ $amountWords }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- 5.4 narration --}}
        <div class="glj-narr">
            <div class="nh">Narration / Description</div>
            <div class="nb">{{ $journalEntry->memo ?: 'No description provided for this journal entry.' }}</div>
        </div>

        {{-- 5.5 signatures (R3: timestamp only, no typed name) --}}
        <div class="glj-sigs">
            <div class="glj-sig">
                <div class="rule"></div>
                <div class="sl">Prepared by</div>
                <div class="sd">{{ $journalEntry->created_at?->format('d M Y · H:i') }}</div>
            </div>
            <div class="glj-sig">
                <div class="rule"></div>
                <div class="sl">Authorised by</div>
                <div class="sd">Name · Date</div>
            </div>
            <div class="glj-sig">
                <div class="rule"></div>
                <div class="sl">Posted by</div>
                <div class="sd">{{ $journalEntry->posted_at?->format('d M Y · H:i') ?: 'Name · Date' }}</div>
            </div>
        </div>
    </div>

    {{-- 5.6 footer: margin-top:auto pins it to the sheet bottom. The voucher is a
         single A4 sheet, so the spec's page cell is the fixed literal "PAGE 1 OF 1". --}}
    <div class="glj-vfoot">
        <span class="fa">AUDIT · created {{ $journalEntry->created_at ? $fmtDate($journalEntry->created_at) : '—' }}{{ $journalEntry->posted_at ? ' · posted ' . $fmtDate($journalEntry->posted_at) : '' }}</span>
        {{-- Fixed legal wording: it is the definition of a valid voucher, not a
             statement about this particular entry, so it must NOT interpolate the
             current status (a REVERSED entry would otherwise read "...while status
             is REVERSED"). --}}
        <span class="fb">Computer-generated journal voucher — valid without signature while status is POSTED</span>
        <span class="fc">PAGE 1 OF 1</span>
    </div>
</div>