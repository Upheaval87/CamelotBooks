<x-app-layout>
    <div class="rj-wrap rj-rebuild">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <h1>Recurring Journals</h1>
                    <div class="sub">All journal templates with frequency, next run and generation history.</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('accounting.rj.export') }}" class="btn btn-ghost btn-sm">⇩ Export</a>
                    <a href="{{ route('accounting.rj.create') }}" class="btn btn-cta btn-sm">➕ New Recurring Journal</a>
                </div>
            </div>

            <section class="card">
                <div class="card-sec">
                    <div class="statgrid">
                        <a href="{{ route('accounting.rj.index') }}" class="fbox @if(!request('status')) on @endif"><span class="t t-ink">📋</span><span><span class="l">All</span><span class="v" style="display:block">{{ $counts['all'] }}</span></span></a>
                        <a href="{{ route('accounting.rj.index', ['status' => 'active']) }}" class="fbox @if(request('status') === 'active') on @endif"><span class="t t-mint">✓</span><span><span class="l">Active</span><span class="v" style="display:block">{{ $counts['active'] }}</span></span></a>
                        <a href="{{ route('accounting.rj.index', ['status' => 'paused']) }}" class="fbox @if(request('status') === 'paused') on @endif"><span class="t t-amber">⏸</span><span><span class="l">Paused</span><span class="v" style="display:block">{{ $counts['paused'] }}</span></span></a>
                        <a href="{{ route('accounting.rj.index', ['status' => 'expired']) }}" class="fbox @if(request('status') === 'expired') on @endif"><span class="t t-red">⏰</span><span><span class="l">Expired</span><span class="v" style="display:block">{{ $counts['expired'] }}</span></span></a>
                    </div>
                    <div class="controls">
                        <form method="GET" action="{{ route('accounting.rj.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;width:100%">
                            <div class="search">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input class="input" name="search" placeholder="Search by journal name…" value="{{ request('search') }}">
                            </div>
                            <select class="input" name="status" style="width:auto">
                                <option value="">All Statuses</option>
                                <option value="active" @if(request('status') === 'active') selected @endif>Active</option>
                                <option value="paused" @if(request('status') === 'paused') selected @endif>Paused</option>
                                <option value="expired" @if(request('status') === 'expired') selected @endif>Expired</option>
                            </select>
                            <select class="input" name="frequency" style="width:auto">
                                <option value="">All Frequencies</option>
                                <option value="daily" @if(request('frequency') === 'daily') selected @endif>Daily</option>
                                <option value="weekly" @if(request('frequency') === 'weekly') selected @endif>Weekly</option>
                                <option value="monthly" @if(request('frequency') === 'monthly') selected @endif>Monthly</option>
                                <option value="quarterly" @if(request('frequency') === 'quarterly') selected @endif>Quarterly</option>
                                <option value="yearly" @if(request('frequency') === 'yearly') selected @endif>Yearly</option>
                            </select>
                            <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
                            @if(request('search') || request('status') || request('frequency'))
                                <a href="{{ route('accounting.rj.index') }}" class="btn btn-ghost btn-sm">Clear</a>
                            @endif
                        </form>
                    </div>
                </div>
                <div class="card-sec" style="padding-top:6px">
                    <div class="li-wrap" style="margin-top:0">
                        <table>
                            <thead><tr><th>Journal Name</th><th>Reference</th><th>Type</th><th>Frequency</th><th>Next Run</th><th>Last Generated</th><th class="num">Amount</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @forelse($templates as $t)
                                <tr>
                                    <td style="font-weight:700;color:var(--ink)"><a href="{{ route('accounting.rj.show', $t) }}" style="color:inherit;text-decoration:none">{{ $t->name }}</a></td>
                                    <td class="mono">{{ $t->reference ?? '—' }}</td>
                                    <td><span class="tchip {{ $t->typeChipClass() }}">{{ $t->journal_type }}</span></td>
                                    <td class="em">{{ ucfirst(str_replace('_', ' ', $t->frequency)) }}</td>
                                    <td class="em">{{ $t->next_run_date?->format('d M Y') ?? '—' }}</td>
                                    <td class="em">{{ $t->last_generated_at?->format('d M Y') ?? 'Never' }}</td>
                                    <td class="numr bold">{{ number_format($t->total_amount, 2) }}</td>
                                    <td><span class="badge {{ $t->statusBadgeClass() }}"><span class="bdot"></span>{{ ucfirst($t->status) }}</span></td>
                                    <td>
                                        <div class="row-act">
                                            @if($t->status === 'active')
                                            <form method="POST" action="{{ route('accounting.rj.run-now', $t) }}" style="display:inline">@csrf<button type="submit" class="ibtn" title="Run Now">▶</button></form>
                                            @endif
                                            <a href="{{ route('accounting.rj.show', $t) }}" class="ibtn" title="View">👁</a>
                                            <div class="more">
                                                <button class="ibtn" onclick="this.parentElement.classList.toggle('open')">⋯</button>
                                                <div class="more-menu">
                                                    <a href="{{ route('accounting.rj.edit', $t) }}" class="more-item">✎ Edit</a>
                                                    <form method="POST" action="{{ route('accounting.rj.duplicate', $t) }}" style="display:inline">@csrf<button type="submit" class="more-item">⧉ Duplicate</button></form>
                                                    @if($t->status === 'active')
                                                        <form method="POST" action="{{ route('accounting.rj.toggle', $t) }}" style="display:inline">@csrf@method('PATCH')<button type="submit" class="more-item">⏸ Pause</button></form>
                                                    @elseif($t->status === 'paused')
                                                        <form method="POST" action="{{ route('accounting.rj.toggle', $t) }}" style="display:inline">@csrf@method('PATCH')<button type="submit" class="more-item">▶ Resume</button></form>
                                                    @endif
                                                    <a href="{{ route('accounting.rj.history', ['search' => $t->name]) }}" class="more-item">🕘 View History</a>
                                                    @if($t->status === 'expired')
                                                        <form method="POST" action="{{ route('accounting.rj.toggle', $t) }}" style="display:inline">@csrf@method('PATCH')<button type="submit" class="more-item">🔄 Renew</button></form>
                                                    @endif
                                                    @if($t->runs()->where('is_test', false)->count() === 0)
                                                        <form method="POST" action="{{ route('accounting.rj.destroy', $t) }}" style="display:inline">@csrf@method('DELETE')<button type="submit" class="more-item danger" onclick="return confirm('Delete this recurring journal?')">🗑 Delete</button></form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="em" style="text-align:center;padding:24px">No recurring journals found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="pagi">
                    <span class="t">Showing {{ $templates->firstItem() ?? 0 }}–{{ $templates->lastItem() ?? 0 }} of {{ $templates->total() }} journals</span>
                    <div style="display:flex;gap:8px">{{ $templates->links() }}</div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
