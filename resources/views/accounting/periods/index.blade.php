<x-app-layout>
    <div class="ac-wrap">
        <div class="page-head">
            <div>
                <h1>Accounting Periods</h1>
                <div class="sub">Open and lock accounting periods to control posting.</div>
            </div>
            <form method="POST" action="{{ route('accounting.periods.store') }}" style="display:inline">
                @csrf
                <button type="submit" class="ac-btn ac-btn-ghost">&#43; Generate Periods</button>
            </form>
        </div>

        <div class="ac-card">
            <div class="ac-card-h">
                <span class="ac-ic">&#128197;</span>
                <h2>Periods</h2>
                <div class="right">
                    @php
                        $fyLabel = $periods->first()?->fiscalYear?->label ?? '';
                    @endphp
                    <span class="ac-tchip">{{ $periods->count() }} periods{{ $fyLabel ? ' &middot; ' . $fyLabel : '' }}</span>
                </div>
            </div>
            <div class="ac-li-wrap">
                <table class="ac-table">
                    <thead class="ac-thead">
                        <tr>
                            <th>Label</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th class="num">Journal Entries</th>
                            <th class="num">Locked Entries</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="ac-tbody">
                        @forelse($periods as $period)
                        @php
                            $statusLabel = $period->isLocked() ? 'Locked' : ($period->isOpen() ? 'Open' : 'Closed');
                            $statusClass = $period->isLocked() ? 'b-rev' : ($period->isOpen() ? 'b-ok' : 'b-off');
                        @endphp
                        <tr>
                            <td class="name">{{ $period->label }}</td>
                            <td class="ac-em">{{ $period->start_date->format('d M Y') }}</td>
                            <td class="ac-em">{{ $period->end_date->format('d M Y') }}</td>
                            <td><span class="ac-badge {{ $statusClass }}"><span class="bdot"></span>{{ $statusLabel }}</span></td>
                            <td class="num">{{ $period->journal_entries_count ?? 0 }}</td>
                            <td class="num">{{ $period->locked_entries_count ?? 0 }}</td>
                            <td class="ac-row-act">
                                @if($period->isOpen())
                                <form method="POST" action="{{ route('accounting.periods.lock', $period) }}" style="display:inline" onsubmit="return confirm('Lock {{ $period->label }}? No new entries will be accepted.')">
                                    @csrf
                                    <button type="submit" class="ac-btn ac-btn-ghost ac-btn-sm">Lock</button>
                                </form>
                                @elseif($period->isLocked())
                                <form method="POST" action="{{ route('accounting.periods.reopen', $period) }}" style="display:inline" onsubmit="return confirm('Reopen {{ $period->label }}?')">
                                    @csrf
                                    <button type="submit" class="ac-btn ac-btn-ghost ac-btn-sm">Unlock</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="ac-empty">
                                    <div class="ac-empty-ic">&#128197;</div>
                                    No periods found.
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
