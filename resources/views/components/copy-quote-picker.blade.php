@props([
    'quotes' => collect(),
    'mode' => 'form',
    'copyQuoteUrl' => route('accounting.invoices.copy-quote'),
    'createUrl' => route('accounting.invoices.create'),
])

@php
    $quotesArray = $quotes->map(fn ($q) => [
        'id' => $q->id,
        'number' => $q->quotation_number,
        'date' => $q->quotation_date?->format('Y-m-d'),
        'customer' => $q->customer?->name ?? '',
        'total' => (float) $q->total,
        'currency' => $q->currency ?? '',
    ])->values()->all();
@endphp

<div id="copy-quote-modal"
     class="cq-overlay"
     data-mode="{{ $mode }}"
     data-copy-quote-url="{{ $copyQuoteUrl }}"
     data-create-url="{{ $createUrl }}"
     style="display:none"
     role="dialog"
     aria-modal="true"
     aria-labelledby="copy-quote-title">
    <div class="cq-backdrop" onclick="CopyQuote.close()"></div>
    <div class="cq-panel">
        <div class="cq-head">
            <span class="cq-head-ic">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 16H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2m-6 12h8a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <div class="cq-head-text">
                <h2 id="copy-quote-title">Copy from Quotation</h2>
                <p>Choose a recent quotation to copy into a new invoice.</p>
            </div>
            <button type="button" class="cq-close" onclick="CopyQuote.close()" aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div id="cq-list" class="cq-list"></div>
        <p class="cq-foot">Only sent or accepted quotations are shown. Prices are copied exactly as quoted.</p>
    </div>
    <script type="application/json" id="cq-quotes-json">@json($quotesArray)</script>
</div>

<script>
(function () {
    if (window.CopyQuote) return;

    const esc = s => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const fmtDate = v => {
        if (!v) return '—';
        const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return v;
        const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };
    const fmtNum = n => Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    window.CopyQuote = {
        open(config) {
            const root = document.getElementById('copy-quote-modal');
            if (!root) return;
            this._config = config || {};

            let quotes = this._config.quotes;
            if (quotes == null) {
                try { quotes = JSON.parse(document.getElementById('cq-quotes-json')?.textContent || '[]'); }
                catch (e) { quotes = []; }
            }

            const list = document.getElementById('cq-list');
            if (!quotes.length) {
                list.innerHTML = '<div class="cq-empty">No sent or accepted quotations yet.</div>';
            } else {
                list.innerHTML = quotes.map(q => `
                    <button type="button" class="cq-item" data-id="${esc(q.id)}">
                        <span class="cq-item-main">
                            <span class="cq-item-num">${esc(q.number)}</span>
                            <span class="cq-item-sub">${fmtDate(q.date)} · ${esc(q.customer)}</span>
                        </span>
                        <span class="cq-item-total">${fmtNum(q.total)} ${esc(q.currency)}</span>
                    </button>`).join('');
                list.querySelectorAll('.cq-item').forEach(el => {
                    el.addEventListener('click', () => this._select(el.dataset.id));
                });
            }

            root.style.display = 'block';
            document.body.classList.add('overflow-y-hidden');
            const first = list.querySelector('.cq-item');
            if (first) first.focus();

            if (!this._keyHandler) {
                this._keyHandler = e => {
                    const m = document.getElementById('copy-quote-modal');
                    if (e.key === 'Escape' && m && m.style.display === 'block') this.close();
                };
                document.addEventListener('keydown', this._keyHandler);
            }
        },

        close() {
            const root = document.getElementById('copy-quote-modal');
            if (!root) return;
            root.style.display = 'none';
            document.body.classList.remove('overflow-y-hidden');
        },

        _select(id) {
            const cfg = this._config || {};
            const root = document.getElementById('copy-quote-modal');
            if (!root) return;
            const mode = cfg.mode || root.dataset.mode || 'form';

            if (mode === 'navigate') {
                const base = cfg.createUrl || root.dataset.createUrl || '';
                const sep = base.includes('?') ? '&' : '?';
                window.location = base + sep + 'copy_quote=' + encodeURIComponent(id);
                return;
            }

            const url = (cfg.copyQuoteUrl || root.dataset.copyQuoteUrl || '') + '?quotation=' + encodeURIComponent(id);
            fetch(url, { headers: { Accept: 'application/json' } })
                .then(r => { if (!r.ok) throw new Error('copy_quote_failed'); return r.json(); })
                .then(payload => this._renderBreakdown(payload))
                .catch(() => {
                    if (window.CB && typeof window.CB.toast === 'function') {
                        window.CB.toast('error', 'Could not copy quotation', 'That quotation could not be loaded.');
                    }
                });
        },

        _renderBreakdown(payload) {
            const list = document.getElementById('cq-list');
            const rows = (payload.lines || []).map(l => {
                const meta = [l.quantity + ' × ' + fmtNum(l.unit_price)];
                if (parseFloat(l.discount)) meta.push('disc ' + fmtNum(l.discount));
                if (parseFloat(l.tax_rate)) meta.push('tax ' + l.tax_rate + '%');
                return `
                    <div class="cq-brow">
                        <span class="cq-bname">${esc(l.label || l.description || 'Item')}</span>
                        <span class="cq-bmeta">${esc(meta.join(' · '))}</span>
                        <span class="cq-bamt">${fmtNum(l.line_total)}</span>
                    </div>`;
            }).join('');

            list.innerHTML = `
                <div class="cq-break">
                    ${rows || '<div class="cq-empty">This quotation has no line items.</div>'}
                </div>
                <div class="cq-total">
                    <span>${esc(payload.quotation_number || '')} · Grand Total</span>
                    <span>${fmtNum(payload.total)} ${esc(payload.currency || '')}</span>
                </div>
                <div class="cq-actions">
                    <button type="button" class="cq-btn cq-btn-ghost" id="cq-back">Back</button>
                    <button type="button" class="cq-btn cq-btn-gold" id="cq-use">Use these lines</button>
                </div>`;

            document.getElementById('cq-back').addEventListener('click', () => this.open(this._config));
            document.getElementById('cq-use').addEventListener('click', () => {
                const cfg = this._config || {};
                if (typeof cfg.onApply === 'function') cfg.onApply(payload);
                else if (typeof window.invApplyQuote === 'function') window.invApplyQuote(payload);
                this.close();
                if (window.CB && typeof window.CB.toast === 'function') {
                    window.CB.toast('success', 'Quotation copied', 'Lines from ' + payload.quotation_number + ' added to the invoice.');
                }
            });
        },
    };
})();
</script>
