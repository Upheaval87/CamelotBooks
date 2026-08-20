<x-app-layout>
    <div class="ac-wrap">
        <div class="page-head">
            <div>
                <h1>Exchange Rates</h1>
                <div class="sub">Manage currency conversion rates against the base currency.</div>
            </div>
            <a href="{{ route('accounting.exchange-rates.create') }}" class="ac-btn ac-btn-cta">&#43; New Exchange Rate</a>
        </div>

        <div class="ac-card">
            <div class="ac-card-h">
                <span class="ac-ic">&#128176;</span>
                <h2>Rates</h2>
                <div class="right">
                    <span class="ac-tchip ac-chip-brand">Base: {{ $baseCurrency ?? 'N/A' }}</span>
                    <button class="ac-btn ac-btn-ghost ac-btn-sm" onclick="window.location.href='{{ route('accounting.exchange-rates.index') }}?export=csv'">Export CSV</button>
                </div>
            </div>
            <div class="ac-li-wrap">
                <table class="ac-table">
                    <thead class="ac-thead">
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th class="num">Rate</th>
                            <th>Effective Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="ac-tbody">
                        @forelse($rates as $rate)
                        <tr>
                            <td class="name">{{ $rate->currency_from }}</td>
                            <td class="ac-em">{{ $rate->currency_to }}</td>
                            <td class="num">{{ number_format($rate->rate, 6) }}</td>
                            <td class="ac-em">{{ $rate->effective_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if($rate->is_current ?? true)
                                <span class="ac-badge b-post"><span class="bdot"></span>Current</span>
                                @else
                                <span class="ac-badge b-off"><span class="bdot"></span>Historic</span>
                                @endif
                            </td>
                            <td class="ac-row-act">
                                <a href="{{ route('accounting.exchange-rates.edit', $rate) }}" class="ac-ibtn" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('accounting.exchange-rates.destroy', $rate) }}" style="display:inline" onsubmit="return confirm('Delete this exchange rate?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ac-ibtn" title="Delete">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <div class="ac-empty">
                                    <div class="ac-empty-ic">&#128176;</div>
                                    No exchange rates configured.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
