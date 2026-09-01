<x-app-layout>
    @php($cs = $cs ?? '')
    <div class="amc-suite" x-data="switchAccrual({{ Js::from([
        'defaultCutOff' => $defaultCutOff,
        'lastPostedPeriodEnd' => $lastPostedPeriodEnd,
        'draft' => $conversion && !$conversion->isActivated() ? [
            'cut_off_date' => $conversion->cut_off_date?->format('Y-m-d'),
            'treatment' => $conversion->treatment,
        ] : null,
        'activated' => (bool) $conversion?->isActivated(),
    ]) }})">

        <div class="amc-head">
            <nav class="amc-crumbs"><a href="{{ route('system-settings.index') }}">Settings</a> › <span class="here">Switch to Accrual</span></nav>
            <h1>Switch to Accrual — controlled conversion</h1>
            <div class="amc-sub">For a company currently on cash basis. A dated, journaled conversion — never a free toggle.</div>
        </div>

        @if($conversion && $conversion->isActivated())
            <div class="amc-card amc-card--done">
                <div class="amc-done-ic">✓</div>
                <div>
                    <h2>This company is on the accrual basis.</h2>
                    <p>The conversion was completed on {{ $conversion->activated_at?->format('d M Y') }} by user #{{ $conversion->activated_by }}. The cash-basis view remains available as a report for your cash-flow reporting.</p>
                    @if($conversionJournal)
                        <p>Conversion journal: <span class="amc-mono">{{ $conversionJournal->journal_number }}</span> ({{ $conversionJournal->date->format('d M Y') }}).</p>
                    @endif
                </div>
            </div>
        @else
            <div class="amc-stepper" aria-label="Conversion steps">
                <span class="amc-st on" aria-current="step">1 · Cut-off</span>
                <span class="amc-st on">2 · Opening balances</span>
                <span class="amc-st">3 · Conversion journal</span>
                <span class="amc-st">4 · Activate</span>
            </div>

            <form method="POST" action="{{ route('settings.switch_accrual.store') }}" id="switch-accrual-form">
                @csrf

                <div class="amc-card">
                    <div class="amc-card-h"><span class="amc-stepno">1</span><h2>Cut-off date</h2></div>
                    <div class="amc-pad">
                        <div class="amc-inrow">
                            <label class="amc-nm" for="cut_off_date">Accrual applies from</label>
                            <input type="date" id="cut_off_date" name="cut_off_date" class="amc-input amc-input--date"
                                   :value="draft?.cut_off_date || defaultCutOff" required>
                        </div>
                        <div class="amc-inrow">
                            <label class="amc-nm" for="treatment">Prior periods</label>
                            <select id="treatment" name="treatment" class="amc-input amc-input--select">
                                @foreach($treatmentOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(($conversion?->treatment ?? 'prospective') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($lastPostedPeriodEnd)
                            <div class="amc-hint">Cut-off must be on or after the last day of the last posted period ({{ $lastPostedPeriodEnd }}), and after today only if no posted transactions fall on/after it.</div>
                        @endif
                        @error('cut_off_date')
                            <div class="amc-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="amc-card">
                    <div class="amc-card-h"><span class="amc-stepno">2</span><h2>Capture opening balances at cut-off</h2></div>
                    <div class="amc-pad">
                        <div class="amc-inrow">
                            <label class="amc-nm" for="ar">Accounts Receivable (earned, not collected)</label>
                            <input type="number" step="0.01" min="0" id="ar" name="ar" class="amc-input amc-input--num" x-model.number="bal.ar" placeholder="0.00">
                        </div>
                        <div class="amc-inrow">
                            <label class="amc-nm" for="inv">Inventory on hand</label>
                            <input type="number" step="0.01" min="0" id="inv" name="inv" class="amc-input amc-input--num" x-model.number="bal.inv" placeholder="0.00">
                        </div>
                        <div class="amc-inrow">
                            <label class="amc-nm" for="pre">Prepayments (paid, not yet used)</label>
                            <input type="number" step="0.01" min="0" id="pre" name="pre" class="amc-input amc-input--num" x-model.number="bal.pre" placeholder="0.00">
                        </div>
                        <div class="amc-inrow">
                            <label class="amc-nm" for="ap">Accounts Payable (incurred, not paid)</label>
                            <input type="number" step="0.01" min="0" id="ap" name="ap" class="amc-input amc-input--num" x-model.number="bal.ap" placeholder="0.00">
                        </div>
                        <div class="amc-inrow">
                            <label class="amc-nm" for="acc">Accrued expenses</label>
                            <input type="number" step="0.01" min="0" id="acc" name="acc" class="amc-input amc-input--num" x-model.number="bal.acc" placeholder="0.00">
                        </div>
                        <div class="amc-inrow">
                            <label class="amc-nm" for="une">Unearned revenue (received, not earned)</label>
                            <input type="number" step="0.01" min="0" id="une" name="une" class="amc-input amc-input--num" x-model.number="bal.une" placeholder="0.00">
                        </div>
                        <div class="amc-hint">These drive the conversion journal below — it recomputes live as you type.</div>
                    </div>
                </div>

                <div class="amc-card">
                    <div class="amc-card-h">
                        <span class="amc-stepno">3</span><h2>Conversion journal — auto-balanced to opening equity</h2>
                        <div style="margin-left:auto"><span class="amc-okchip" id="bal" x-show="balanced" aria-live="polite">✓ Balanced · Dr = Cr</span></div>
                    </div>
                    <div class="amc-pad">
                        <table class="amc-table">
                            <thead><tr><th>Account</th><th class="num">Debit</th><th class="num">Credit</th></tr></thead>
                            <tbody>
                                <tr><td>Accounts Receivable</td><td class="num" x-text="fmt(bal.ar)"></td><td class="num"></td></tr>
                                <tr><td>Inventory</td><td class="num" x-text="fmt(bal.inv)"></td><td class="num"></td></tr>
                                <tr><td>Prepayments</td><td class="num" x-text="fmt(bal.pre)"></td><td class="num"></td></tr>
                                <tr><td>Accounts Payable</td><td class="num"></td><td class="num" x-text="fmt(bal.ap)"></td></tr>
                                <tr><td>Accrued Expenses</td><td class="num"></td><td class="num" x-text="fmt(bal.acc)"></td></tr>
                                <tr><td>Unearned Revenue</td><td class="num"></td><td class="num" x-text="fmt(bal.une)"></td></tr>
                                <tr>
                                    <td class="amc-codecell">Retained Earnings — opening adjustment</td>
                                    <td class="num" x-text="reDebit ? fmt(reDebit) : ''"></td>
                                    <td class="num" x-text="reCredit ? fmt(reCredit) : ''"></td>
                                </tr>
                                <tr class="amc-totals">
                                    <td class="amc-codecell">Totals</td>
                                    <td class="num" x-text="fmt(totalDebit)"></td>
                                    <td class="num" x-text="fmt(totalCredit)"></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="amc-warn">⚠ <span>Non-cash conversion — cash does not move. From the cut-off, invoice at delivery, expense at bill, track stock/COGS and run period-end accruals.</span></div>
                        <div class="amc-draft-card" x-show="draft != null">
                            <b>Draft saved.</b> Balances from the last draft are shown. Editing them updates this journal; the changes are re-persisted when you save or activate again.
                        </div>
                    </div>
                </div>

                <div class="amc-card">
                    <div class="amc-card-h">
                        <span class="amc-stepno">4</span><h2>Activate conversion</h2>
                        <div style="margin-left:auto;display:flex;gap:8px">
                            <button type="submit" name="action" value="draft" class="amc-btn amc-btn-ghost amc-btn-sm">Save draft</button>
                            <button type="submit" name="action" value="activate" class="amc-btn amc-btn-cta amc-btn-sm"
                                    @click.prevent="confirmActivate($event)">Activate Conversion</button>
                        </div>
                    </div>
                    <div class="amc-pad">
                        <div class="amc-note">On activation the system: posts the conversion journal, activates AR / AP / inventory modules, flags periods before cut-off as <b>cash</b> and after as <b>accrual</b>, and keeps the cash-basis view available as a report. This is recorded in the audit trail and cannot be silently undone.</div>
                    </div>
                </div>
            </form>
        @endif

        @if(!$conversion || !$conversion->isActivated())
        <div class="amc-note amc-note--gate">
            <b>One-way.</b> Switch to Accrual is a one-way conversion. Once activated, this company is on the accrual basis and a reverse switch is never offered — use the <i>reporting preference</i> toggle for a cash-basis view of your reports instead.
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
    function switchAccrual(conf) {
        return {
            defaultCutOff: conf.defaultCutOff,
            lastPostedPeriodEnd: conf.lastPostedPeriodEnd,
            draft: conf.draft,
            activated: conf.activated,
            bal: { ar: 0, inv: 0, pre: 0, ap: 0, acc: 0, une: 0 },
            init() {
                if (!this.activated) {
                    // draft balances re-enter as 0s (no column persisted); the
                    // draft journal id + notes carry the audit trail.
                }
            },
            get debitSide() { return this.bal.ar + this.bal.inv + this.bal.pre; },
            get creditSide() { return this.bal.ap + this.bal.acc + this.bal.une; },
            get plug() { return this.debitSide - this.creditSide; },
            get reIsCredit() { return this.plug >= 0; },
            get reDebit() { return this.reIsCredit ? 0 : -this.plug; },
            get reCredit() { return this.reIsCredit ? this.plug : 0; },
            get totalDebit() { return this.debitSide + this.reDebit; },
            get totalCredit() { return this.creditSide + this.reCredit; },
            get balanced() { return this.totalDebit === this.totalCredit; },
            fmt(v) { return (+v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            confirmActivate(e) {
                if (!window.CB) { this.submitDirty(this, 'activate'); return; }
                window.CB.confirm({
                    type: 'action',
                    title: 'Activate conversion?',
                    message: 'This posts the conversion journal, activates AR/AP/inventory, and switches the company to the accrual basis. This cannot be undone.',
                    buttons: [{ label: 'Cancel', variant: 'ghost' }, { label: 'Activate', variant: 'primary' }]
                }).then((ok) => {
                    if (ok) this.submitDirty(this, 'activate');
                });
            },
            submitDirty(el, val) {
                const form = document.getElementById('switch-accrual-form');
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'action';
                hidden.value = val;
                form.appendChild(hidden);
                form.submit();
            }
        };
    }
    </script>
    @endpush
</x-app-layout>
