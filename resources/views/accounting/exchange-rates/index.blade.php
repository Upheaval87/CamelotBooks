<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <h1>Exchange Rates</h1>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.exchange-rates.create') }}" class="ac-btn ac-btn-cta ac-btn-sm">New Exchange Rate</a>
            </div>
        </div>

        <div class="ac-card">
            <div class="ac-card-h">
                <h2>Rates</h2>
                <div class="right">
                    <span class="ac-tchip">Base: {{ $baseCurrency ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="ac-li-wrap">
                <table class="ac-table" style="min-width:auto">
                    <thead>
                        <tr>
                            <th style="width:18%">From</th>
                            <th style="width:18%">To</th>
                            <th class="num" style="width:18%">Rate</th>
                            <th style="width:18%">Effective Date</th>
                            <th style="width:18%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rates as $rate)
                        <tr>
                            <td class="ac-mono">{{ $rate->currency_from }}</td>
                            <td class="ac-mono">{{ $rate->currency_to }}</td>
                            <td class="ac-numr bold">{{ number_format($rate->rate, 6) }}</td>
                            <td class="ac-em">{{ $rate->effective_date?->format('d M Y') ?? '—' }}</td>
                            <td class="ac-row-act">
                                <a href="{{ route('accounting.exchange-rates.edit', $rate) }}" class="ac-ibtn" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="ac-em" style="text-align:center;padding:40px">No exchange rates configured.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
