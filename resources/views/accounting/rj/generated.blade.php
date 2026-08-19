<x-app-layout>
    <div class="rj-wrap rj-rebuild">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <h1>Generated Journals</h1>
                    <div class="sub">Everything the automation created — review, approve, post, reverse, print.</div>
                </div>
                <a href="{{ route('accounting.rj.export') }}" class="btn btn-ghost btn-sm">⇩ Export</a>
            </div>

            <section class="card">
                <div class="card-h">
                    <h2>Generated Journals</h2>
                    <span class="tchip">Draft {{ $counts['draft'] ?? 0 }}</span>
                    <span class="tchip tchip-amber">Pending {{ $counts['pending_approval'] ?? 0 }}</span>
                    <span class="tchip tchip-green">Posted {{ $counts['posted'] ?? 0 }}</span>
                    <span class="tchip tchip-steel">Reversed {{ $counts['reversed'] ?? 0 }}</span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th>Journal №</th>
                                <th>Date</th>
                                <th>Reference / Source</th>
                                <th class="num">Amount</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($runs as $run)
                                <tr>
                                    <td class="mono">{{ $run->journalEntry?->entry_number ?? '—' }}</td>
                                    <td class="em">{{ $run->run_date?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        <span class="mono">{{ $run->reference ?? '—' }}</span>
                                        @if($run->template)
                                            <br><span class="em">via {{ $run->template->name }}</span>
                                        @endif
                                    </td>
                                    <td class="numr bold">{{ number_format($run->total_debit, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $run->statusBadgeClass() }}"><span class="bdot"></span>{{ str_replace('_', ' ', ucfirst($run->status)) }}</span>
                                    </td>
                                    <td>
                                        <div class="row-act">
                                            @if($run->status === 'pending_approval')
                                                <form method="POST" action="{{ route('accounting.rj.approve-run', $run) }}" style="display:inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-cta btn-xs">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ route('accounting.rj.reject-run', $run) }}" style="display:inline;display:flex;align-items:center;gap:4px">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Reason" class="btn btn-danger-o btn-xs" style="width:120px">
                                                    <button type="submit" class="btn btn-danger-o btn-xs">Reject</button>
                                                </form>
                                            @elseif($run->status === 'posted')
                                                <a href="{{ route('accounting.journal-entries.show', $run->journalEntry) }}" class="btn btn-ghost btn-xs">View</a>
                                                <a href="{{ route('accounting.journal-entries.show', $run->journalEntry) }}" class="btn btn-ghost btn-xs" target="_blank">Print</a>
                                            @elseif($run->status === 'draft')
                                                <form method="POST" action="{{ route('accounting.rj.run-now', $run->template) }}" style="display:inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sec btn-xs">Post</button>
                                                </form>
                                            @elseif($run->status === 'reversed')
                                                <a href="{{ route('accounting.journal-entries.show', $run->journalEntry) }}" class="btn btn-ghost btn-xs">View</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="em" style="text-align:center;padding:40px 20px">No generated journals yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagi">
                    <span class="t">Showing {{ $runs->firstItem() ?? 0 }}–{{ $runs->lastItem() ?? 0 }} of {{ $runs->total() }} journals</span>
                    <div style="display:flex;gap:8px">{{ $runs->links() }}</div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
