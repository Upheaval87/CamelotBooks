<x-app-layout>
    <div class="rj-wrap rj-rebuild">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <h1>Approval Queue</h1>
                    <div class="sub">Review and approve generated journal entries before posting.</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('accounting.rj.history') }}" class="btn btn-ghost btn-sm">🕘 View History</a>
                    <a href="{{ route('accounting.rj.dashboard') }}" class="btn btn-ghost btn-sm">← Back to Dashboard</a>
                </div>
            </div>

            @forelse($pendingRuns as $run)
                <section class="card" style="margin-bottom:14px">
                    <div class="card-h" style="display:flex;align-items:center;gap:12px;padding:16px 24px;border-bottom:1px solid var(--line,#E2ECEC)">
                        <div style="flex:1;min-width:0">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                                <span class="mono">{{ $run->template->reference ?? '—' }}</span>
                                <span class="em" style="font-size:13px;font-weight:600;color:var(--ink)">{{ $run->template->name ?? '—' }}</span>
                                <span class="tchip" style="background:rgba(217,119,6,.10);border-color:rgba(217,119,6,.4);color:#8A5A15">⏳ Pending Approval</span>
                            </div>
                            <div style="display:flex;gap:14px;margin-top:6px;flex-wrap:wrap">
                                <span class="em">Amount: <b style="color:var(--ink)">{{ number_format($run->total_amount ?? 0, 2) }}</b></span>
                                <span class="em">Generated: {{ $run->created_at?->format('d M Y H:i') ?? '—' }}</span>
                                <span class="em">By: {{ $run->createdBy?->name ?? 'System' }}</span>
                                @if($run->journalEntry)
                                    <span class="em">JE: <a class="open-l" href="{{ route('accounting.journal-entries.show', $run->journalEntry) }}">{{ $run->journalEntry->entry_number ?? '—' }}</a></span>
                                @endif
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;flex-shrink:0">
                            <form method="POST" action="{{ route('accounting.rj.approve-run', $run) }}" style="display:inline" onsubmit="return window.CB && window.CB.confirm({type:'action',title:'Approve Journal Entry',message:'Post this journal entry to the general ledger?',confirmLabel:'Approve &amp; Post'}) || confirm('Approve this journal entry?')">
                                @csrf
                                <button type="submit" class="btn btn-sec btn-sm">✓ Approve</button>
                            </form>
                            <form method="POST" action="{{ route('accounting.rj.reject-run', $run) }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="reason" id="reject-reason-{{ $run->id }}" value="">
                                <button type="button" class="btn btn-danger-o btn-sm" onclick="var r=prompt('Rejection reason (required):');if(r===null||r.trim()===''){return false;}document.getElementById('reject-reason-{{ $run->id }}').value=r;this.form.submit();">✗ Reject</button>
                            </form>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('comment-{{ $run->id }}').focus()">💬 Comment</button>
                        </div>
                    </div>

                    <div class="card-sec">
                        <div style="margin-bottom:12px">
                            <label class="em" style="display:block;margin-bottom:6px;font-weight:600;color:var(--ink)">Approval Comment</label>
                            <input class="input" type="text" id="comment-{{ $run->id }}" placeholder="Add approval comment (mandatory on reject / changes)…" style="width:100%">
                        </div>

                        @if($run->history && $run->history->count())
                            <div style="border-top:1px solid var(--line,#E2ECEC);padding-top:12px">
                                <div class="em" style="font-weight:700;color:var(--ink);margin-bottom:8px">Approval History</div>
                                <div class="audit">
                                    @foreach($run->history as $h)
                                        <div class="arow">
                                            <span class="when">{{ $h->created_at?->format('d M Y H:i') ?? '—' }}</span>
                                            <span class="who">{{ $h->actor?->name ?? 'System' }}</span>
                                            <span class="what">
                                                @if($h->action === 'approved')
                                                    <span style="color:var(--sec,#107C7B);font-weight:700">✓ Approved</span>
                                                @elseif($h->action === 'rejected')
                                                    <span style="color:var(--red,#dc2626);font-weight:700">✗ Rejected</span>
                                                @elseif($h->action === 'changes_requested')
                                                    <span style="color:var(--amber,#b45309);font-weight:700">💬 Changes Requested</span>
                                                @else
                                                    {{ ucfirst(str_replace('_', ' ', $h->action)) }}
                                                @endif
                                                @if($h->notes)
                                                    <span class="em"> — {{ $h->notes }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @empty
                <section class="card">
                    <div class="card-sec" style="padding:48px 24px;text-align:center">
                        <div style="font-size:2.5rem;margin-bottom:12px">✓</div>
                        <div style="font-size:1rem;font-weight:700;color:var(--ink);margin-bottom:4px">No journals pending approval.</div>
                        <div class="em" style="font-size:13px">All generated journal entries have been reviewed. Check back later.</div>
                        <a href="{{ route('accounting.rj.dashboard') }}" class="btn btn-ghost btn-sm" style="margin-top:16px">← Back to Dashboard</a>
                    </div>
                </section>
            @endforelse
        </div>
    </div>
</x-app-layout>
