@php($ok = session('quotation_success'))
@if (!empty($ok))
    <div class="q2-scrim q2-scrim--ok" id="quot-ok-scrim" role="alertdialog" aria-modal="true" aria-labelledby="quot-ok-title">
        <div class="q2-cbox" role="document">
            <div class="q2-cbhead">
                <span class="q2-cbic q2-cbic--ok" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 4 12 14.01l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <b class="q2-cbtitle" id="quot-ok-title">{{ $ok['title'] }}</b>
                <button type="button" class="q2-mclose" aria-label="{{ __('Close') }}" data-close="quot-ok-scrim">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M6 18 18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="q2-cbbody">
                {{ __('Quotation') }} <span class="q2-okref">{{ $ok['ref'] }}</span>
                {{ __('for') }} <b>{{ $ok['name'] }}</b> ({{ $ok['total'] }})
                @if (($ok['status'] ?? null) === 'draft')
                    {{ __('was saved as a draft — nothing has been issued yet.') }}
                @else
                    {{ __('was finalized and issued.') }}
                @endif
                @if (!empty($ok['send']))
                    <br>{{ __('The quotation PDF has been emailed to') }} <b>{{ $ok['email'] }}</b>.
                @endif
                @if (!empty($ok['walkin']))
                    <br>{{ __('New customer saved to the registry as') }} <span class="q2-okref">{{ $ok['name'] }}</span>.
                @endif
            </div>
            <div class="q2-cbfoot">
                <button type="button" class="q2-btn q2-btn--ghost q2-btn--sm" data-close="quot-ok-scrim">{{ __('Close') }}</button>
                <a class="q2-btn q2-btn--ghost q2-btn--sm" href="{{ $ok['view_url'] }}">{{ __('View quotation') }}</a>
                @if (!empty($ok['print']))
                    <a class="q2-btn q2-btn--cta q2-btn--sm" href="{{ $ok['print_url'] }}">{{ __('Open print preview') }}</a>
                @endif
            </div>
        </div>
    </div>
    <script>
        /* R2 · dismissal runs through the host page's single document-level
           [data-close] delegation; §9.1 Escape precedence is owned there too.
           This only exposes the close primitive for that chain. */
        window.quotOkClose = function () {
            const scrim = document.getElementById('quot-ok-scrim');
            if (scrim) { scrim.hidden = true; document.body.classList.remove('q2-modal-open'); }
        };
    </script>
@endif
