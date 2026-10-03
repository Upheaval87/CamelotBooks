<x-app-layout>
    <div class="py-6">
        <div class="tc"
             x-data="transactionControls({{ Js::from($workspaceConfig) }})"
             @keydown.escape.window="closeAll()">

            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

                {{-- ── Clean light header (R2) ── --}}
                <header class="page-header">
                    <div class="header-title-row">
                        <span class="header-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 4v6h6M20 20v-6h-6"/>
                                <path d="M4.5 10a8 8 0 0 1 13.9-3.2M19.5 14a8 8 0 0 1-13.9 3.2"/>
                            </svg>
                        </span>
                        <h1>{{ __('Transaction Controls') }}</h1>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="{{ route('accounting.reversals.index') }}">
                        <svg viewBox="0 0 24 24"><path d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4"/></svg>
                        {{ __('Reversal register') }}
                    </a>
                </header>

                {{-- ── Tabs ── --}}
                <div class="mtabs" role="tablist" aria-label="{{ __('Transaction control views') }}">
                    @foreach ([
                        'reversal' => [__('Capture Reversal'), $counts['reversal']],
                        'unposted' => [__('Unposted Transactions'), $counts['unposted']],
                        'authorization' => [__('Authorization'), $counts['authorization']],
                    ] as $key => [$label, $count])
                        <button type="button"
                                role="tab"
                                :class="tab === '{{ $key }}' ? 'on' : ''"
                                :aria-selected="(tab === '{{ $key }}').toString()"
                                aria-controls="tc-pane-{{ $key }}"
                                @click="switchTab('{{ $key }}')">
                            {{ $label }}
                            <span class="cnt">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- ── Panes ── --}}
                <div id="tc-pane-reversal" class="pane" role="tabpanel" x-show="tab === 'reversal'" x-cloak>
                    @include('accounting.transaction-controls._pane-reversal')
                </div>
                <div id="tc-pane-unposted" class="pane" role="tabpanel" x-show="tab === 'unposted'" x-cloak>
                    @include('accounting.transaction-controls._pane-unposted')
                </div>
                <div id="tc-pane-authorization" class="pane" role="tabpanel" x-show="tab === 'authorization'" x-cloak>
                    @include('accounting.transaction-controls._pane-authorization')
                </div>
            </div>

            @include('accounting.transaction-controls._modals')
        </div>
    </div>
</x-app-layout>
