<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <h1>Accounting Periods</h1>
            <div class="ac-sub">Open and lock accounting periods to control posting.</div>
        </div>

        <div class="ac-card">
            <div class="ac-card-h">
                <h2>Periods</h2>
                <div class="right">
                    <span class="ac-tchip">{{ $periods->count() }} periods</span>
                </div>
            </div>
            <div class="ac-li-wrap">
                <table class="ac-table" style="min-width:auto">
                    <thead>
                        <tr>
                            <th style="width:20%">Label</th>
                            <th style="width:15%">Start Date</th>
                            <th style="width:15%">End Date</th>
                            <th style="width:12%">Status</th>
                            <th style="width:12%">Journal Entries</th>
                            <th style="width:12%">Locked Entries</th>
                            <th style="width:10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periods as $period)
                        @php
                            $statusLabel = $period->isLocked() ? 'Locked' : ($period->isOpen() ? 'Open' : 'Closed');
                            $statusClass = $period->isLocked() ? 'ac-b-locked' : ($period->isOpen() ? 'ac-b-open' : 'ac-b-closed');
                        @endphp
                        <tr>
                            <td style="font-weight:600">{{ $period->label }}</td>
                            <td class="ac-em">{{ $period->start_date->format('d M Y') }}</td>
                            <td class="ac-em">{{ $period->end_date->format('d M Y') }}</td>
                            <td><span class="ac-badge {{ $statusClass }}"><span class="bdot"></span>{{ $statusLabel }}</span></td>
                            <td class="ac-numr">{{ $period->journal_entries_count ?? 0 }}</td>
                            <td class="ac-numr">{{ $period->locked_entries_count ?? 0 }}</td>
                            <td class="ac-row-act">
                                @if($period->isOpen())
                                <form method="POST" action="{{ route('accounting.periods.lock', $period) }}" onsubmit="return confirm('Lock {{ $period->label }}? No new entries will be accepted.')" style="display:inline">
                                    @csrf
                                    <button type="submit" class="ac-btn ac-btn-ghost ac-btn-xs">Lock</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="ac-em" style="text-align:center;padding:40px">No periods found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
