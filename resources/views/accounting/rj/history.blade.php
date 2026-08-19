<x-app-layout>
    <div class="rj-wrap rj-rebuild">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <h1>Journal History / Audit Trail</h1>
                    <div class="sub">Full audit trail of the automation engine.</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('accounting.rj.export') }}" class="btn btn-ghost btn-sm">⇩ Export History</a>
                    <a href="{{ route('accounting.rj.reports') }}" class="btn btn-ghost btn-sm">📊 View Audit Log</a>
                </div>
            </div>

            <section class="card">
                <div class="card-h" style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid var(--line,#E2ECEC)">
                    <h2 style="font-size:14px;font-weight:800;color:var(--ink);margin:0">Journal History / Audit Trail</h2>
                    <span class="fmt">immutable</span>
                </div>
                <div class="card-sec">
                    <form method="GET" action="{{ route('accounting.rj.history') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px">
                        <div class="search">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input class="input" name="search" placeholder="Search history…" value="{{ request('search') }}">
                        </div>
                        <select class="input" name="action" style="width:auto">
                            <option value="">All Actions</option>
                            <option value="created" @if(request('action') === 'created') selected @endif>Created</option>
                            <option value="modified" @if(request('action') === 'modified') selected @endif>Modified</option>
                            <option value="generated" @if(request('action') === 'generated') selected @endif>Generated</option>
                            <option value="auto_posted" @if(request('action') === 'auto_posted') selected @endif>Auto Posted</option>
                            <option value="failed" @if(request('action') === 'failed') selected @endif>Failed</option>
                            <option value="reversed" @if(request('action') === 'reversed') selected @endif>Reversed</option>
                            <option value="approved" @if(request('action') === 'approved') selected @endif>Approved</option>
                            <option value="rejected" @if(request('action') === 'rejected') selected @endif>Rejected</option>
                            <option value="schedule_changed" @if(request('action') === 'schedule_changed') selected @endif>Schedule Changed</option>
                        </select>
                        <input class="input" type="date" name="date_from" value="{{ request('date_from') }}" style="width:auto" placeholder="From">
                        <input class="input" type="date" name="date_to" value="{{ request('date_to') }}" style="width:auto" placeholder="To">
                        <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
                        @if(request('search') || request('action') || request('date_from') || request('date_to'))
                            <a href="{{ route('accounting.rj.history') }}" class="btn btn-ghost btn-sm">Clear</a>
                        @endif
                    </form>

                    <div class="audit">
                        @forelse($history as $entry)
                            <div class="arow">
                                <span class="when">{{ $entry->created_at?->format('d M Y H:i') ?? '—' }}</span>
                                <span class="who">{{ $entry->actor?->name ?? 'Engine' }}</span>
                                <span class="what">{!! $entry->description ?? '—' !!}</span>
                            </div>
                        @empty
                            <div class="arow" style="justify-content:center;padding:32px 24px">
                                <span class="what em">No history entries found.</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                @if($history->hasPages())
                    <div class="pagi">
                        <span class="t">Showing {{ $history->firstItem() ?? 0 }}–{{ $history->lastItem() ?? 0 }} of {{ $history->total() }} entries</span>
                        <div style="display:flex;gap:8px">{{ $history->appends(request()->query())->links() }}</div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
