{{--
    APPENDIX A · Fulfil / delivery modal — lives on the show page (and is
    auto-opened when the register's fulfil action lands on `?fulfil=1`).

    Server-rendered from the controller's `fulfilmentRows()` payload so the
    quantities and the remaining-max affordance can never disagree with the
    backend. The index route navigates to `show?fulfil=1` instead of sharing
    this partial, so the line-row integration lives in exactly one place.

    Reuses the `.so2` fv scaffolding (scrim/wrap/card/head/meta) plus the
    form primitives from the `.so2` supplement block.
--}}
@php
    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $order?->company_id ?: session('current_company_id'), '$');
    $progress = $order->fulfilmentProgress();
@endphp

<div class="so2">
<div class="fv-scrim" id="fulScrim" @if(request()->boolean('fulfil') && ($can['fulfil'] ?? false)) on @endif>
    <div class="fv-wrap">
        <div class="fvcard" id="so-fulcard">

            <div class="fv-head">
                <div class="fv-title">
                    <span class="fv-label">{{ __('Record fulfilment / delivery') }}</span>
                    <span class="fv-ref">{{ $order->sales_order_number }}</span>
                </div>
                <div class="fv-actions">
                    <button type="button" class="btn btn-ghost" data-close="fulScrim">{{ __('Close') }}</button>
                </div>
            </div>

            <div class="fv-meta">
                <span class="mchip b">{{ $order->customer?->name ?? $order->customer_name ?? '—' }}</span>
                <span class="mchip">{{ __('Delivery') }} {{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</span>
                <span class="mchip mono">{{ number_format($progress['qty_fulfilled']) }} / {{ number_format($progress['qty_ordered']) }} {{ __('fulfilled') }}</span>
                <span class="mchip b">{{ __('Total') }} {{ $cs }}{{ format_number($order->total) }}</span>
            </div>

            <form method="POST" action="{{ route('accounting.sales-orders.fulfilments', $order) }}" id="fulfil-form">@csrf
                <div class="fv-body">
                    <div class="fg3">
                        <div class="fld">
                            <label for="fuDate">{{ __('Delivery / fulfilment date') }}</label>
                            <input class="in" type="date" id="fuDate" name="date" required value="{{ old('date', $order->delivered_on?->toDateString() ?? now()->toDateString()) }}">
                        </div>
                        <div class="fld">
                            <label for="fuNote">{{ __('Delivery note №') }}</label>
                            <input class="in" id="fuNote" name="dn_no" maxlength="30" placeholder="Auto — e.g. DN-2026-0031" value="{{ old('dn_no') }}">
                        </div>
                        <div class="fld">
                            <label for="fuBy">{{ __('Received by') }}</label>
                            <input class="in" id="fuBy" name="received_by" maxlength="150" placeholder="{{ __('Customer contact') }}" value="{{ old('received_by') }}">
                        </div>
                    </div>

                    <div class="vsec">{{ __('Quantities to fulfil now (remaining shown as max)') }}</div>
                    <div class="etable-wrap">
                        <table class="etable">
                            <thead>
                                <tr>
                                    <th>{{ __('Line') }}</th>
                                    <th class="r">{{ __('Ordered') }}</th>
                                    <th class="r">{{ __('Done') }}</th>
                                    <th class="r">{{ __('Fulfil now') }}</th>
                                </tr>
                            </thead>
                            <tbody id="fuLines">
                                @forelse($fulfilmentLines as $i => $line)
                                    <tr>
                                        <td>
                                            {{ $line['label'] }}
                                            @if($line['sku'])
                                                <span class="code">{{ $line['sku'] }}</span>
                                            @endif
                                        </td>
                                        <td class="r">{{ number_format($line['ordered'], 2) }}</td>
                                        <td class="r">{{ number_format($line['fulfilled'], 2) }}</td>
                                        <td class="r">
                                            @if($line['max'] > 0)
                                                <input type="hidden" name="lines[{{ $i }}][line_id]" value="{{ $line['id'] }}">
                                                <input class="in in--num" type="number" name="lines[{{ $i }}][qty]"
                                                       min="0.01" step="0.01" max="{{ $line['max'] }}"
                                                       value="{{ old('lines.' . $i . '.qty', $line['max']) }}"
                                                       placeholder="{{ number_format($line['max'], 2) }}">
                                                @if($errors->has('lines.' . $i . '.qty'))
                                                    <div class="field-err">{{ $errors->first('lines.' . $i . '.qty') }}</div>
                                                @endif
                                            @else
                                                <span class="em">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="fv-empty">{{ __('No line items to fulfil.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="hint">{{ __('Fulfil less than the remaining quantity to record a partial delivery — the order stays Confirmed with a Partial chip. Completing every line moves the order to Fulfilled.') }}</div>
                </div>

                <div class="fv-foot">
                    <span class="mnote">{{ __('Stock is relieved from the selected branch on save.') }}</span>
                    <button type="submit" class="btn btn-cta" id="fuSave">{{ __('Save fulfilment') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
    (function () {
        var scrim = document.getElementById('fulScrim');
        if (! scrim) return;
        document.querySelectorAll('[data-close="fulScrim"]').forEach(function (b) {
            b.addEventListener('click', function () { scrim.classList.remove('on'); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && scrim.classList.contains('on')) scrim.classList.remove('on');
        });

        var form = document.getElementById('fulfil-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                var any = false, bad = false;
                form.querySelectorAll('input[name$="[qty]"]').forEach(function (inp) {
                    var v = parseFloat(inp.value);
                    if (! v || isNaN(v) || v <= 0) return;
                    any = true;
                    var max = parseFloat(inp.max);
                    if (v > max) {
                        bad = true;
                        inp.focus();
                        window.CB && CB.toast('error', '{{ __('Quantity exceeds the remaining amount.') }}');
                        e.preventDefault();
                    }
                });
                if (! any) {
                    e.preventDefault();
                    window.CB && CB.toast('error', '{{ __('Fulfil at least one line.') }}');
                }
                if (bad) return;
            });
        }
    })();
</script>