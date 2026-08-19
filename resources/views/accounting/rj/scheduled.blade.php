<x-app-layout>
    <div class="rj-wrap rj-rebuild">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <h1>Scheduled Journals</h1>
                    <div class="sub">Active templates due to generate in the next 30 days.</div>
                </div>
                <div class="mono-chip">{{ $scheduled->count() }} runs next 30 days</div>
            </div>

            <section class="card">
                <div class="card-h">
                    <h2>Scheduled Journals — next 30 days</h2>
                    <span class="fmt">{{ $scheduled->count() }}</span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th>Next Run</th>
                                <th>Journal</th>
                                <th>Frequency</th>
                                <th class="num">Amount</th>
                                <th>Mode</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scheduled as $t)
                                <tr>
                                    <td class="em">{{ $t->next_run_date?->format('d M Y') ?? '—' }}</td>
                                    <td style="font-weight:700;color:var(--ink)">
                                        <a href="{{ route('accounting.rj.show', $t) }}" style="color:inherit;text-decoration:none">{{ $t->name }}</a>
                                    </td>
                                    <td class="em">{{ ucfirst(str_replace('_', ' ', $t->frequency)) }}</td>
                                    <td class="numr bold">{{ number_format($t->total_amount, 2) }}</td>
                                    <td><span class="tchip @if($t->generation_mode === 'auto_post') tchip-green @endif">{{ str_replace('_', ' ', $t->generation_mode) }}</span></td>
                                    <td>
                                        @if($t->status === 'paused')
                                            <span class="badge b-pend"><span class="bdot"></span>Paused</span>
                                        @else
                                            <span class="badge {{ $t->statusBadgeClass() }}"><span class="bdot"></span>{{ ucfirst($t->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="row-act">
                                            @if($t->status !== 'paused')
                                                <form method="POST" action="{{ route('accounting.rj.run-now', $t) }}" style="display:inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sec btn-xs">Run Now</button>
                                                </form>
                                            @endif
                                            <div class="more">
                                                <button class="ibtn" onclick="this.parentElement.classList.toggle('open')">⋯</button>
                                                <div class="more-menu">
                                                    @if($t->status === 'paused')
                                                        <form method="POST" action="{{ route('accounting.rj.toggle', $t) }}" style="display:inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="more-item">▶ Resume</button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('accounting.rj.toggle', $t) }}" style="display:inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="more-item">⏸ Pause</button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('accounting.rj.edit', $t) }}" class="more-item">✎ Edit</a>
                                                    <a href="{{ route('accounting.rj.show', $t) }}" class="more-item">👁 View Schedule</a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="em" style="text-align:center;padding:40px 20px">No journals scheduled in the next 30 days.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if($pausedCount > 0)
                <div style="margin-top:12px;font-size:0.857rem;color:var(--ink-muted)">
                    {{ $pausedCount }} {{ Str::plural('journal', $pausedCount) }} paused — not included above.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
