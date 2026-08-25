<x-app-layout>
    <div class="pos">
            <div class="pos-page-head">
                <div>
                    <h1>Returns &amp; Refunds</h1>
                    <div class="pos-sub">Process returns · view refund history</div>
                </div>
                <div class="pos-actions">
                    <a href="{{ route('pos.returns.create') }}" class="pos-btn pos-btn-cta">New Return</a>
                </div>
            </div>

            {{-- KPIs --}}
            @php
                $totalReturns = $returns->total();
                $postedReturns = $returns->getCollection()->filter(fn($r) => $r->isPosted())->count();
                $draftReturns = $returns->getCollection()->filter(fn($r) => $r->isDraft())->count();
            @endphp
            <div class="pos-kpis" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Total Returns</div>
                    <div class="pos-kpi-v">{{ $totalReturns }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Posted</div>
                    <div class="pos-kpi-v">{{ $postedReturns }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Draft</div>
                    <div class="pos-kpi-v">{{ $draftReturns }}</div>
                </div>
            </div>

            {{-- Table + Rail --}}
            <div class="pos-shell">
                <div class="pos-card">
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Return #</th>
                                    <th>Original Sale</th>
                                    <th>Date</th>
                                    <th class="num">Amount</th>
                                    <th>Status</th>
                                    <th class="num">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returns as $return)
                                    <tr>
                                        <td class="pos-mono pos-em">{{ $return->return_number }}</td>
                                        <td class="pos-em">{{ $return->sale?->sale_number ?? '—' }}</td>
                                        <td class="pos-em">{{ $return->date?->format('d M Y') ?? '—' }}</td>
                                        <td class="num pos-bold" style="color:var(--pos-red)">-{{ format_money($return->total) }}</td>
                                        <td>
                                            @if($return->isPosted())
                                                <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Posted</span>
                                            @elseif($return->isDraft())
                                                <span class="pos-badge pos-badge-pend"><span class="pos-bdot"></span>Draft</span>
                                            @else
                                                <span class="pos-badge pos-badge-rev"><span class="pos-bdot"></span>Voided</span>
                                            @endif
                                        </td>
                                        <td class="num">
                                            <div class="pos-row-act">
                                                <a href="{{ route('pos.returns.show', $return) }}" class="pos-ibtn" title="View">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="pos-empty">
                                                <h3>No returns found</h3>
                                                <p>POS returns will appear here once processed.</p>
                                                <a href="{{ route('pos.returns.create') }}" class="pos-btn pos-btn-sec">New Return</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pos-pag">
                        <span>Showing {{ $returns->firstItem() }}–{{ $returns->lastItem() }} of {{ $returns->total() }} returns</span>
                        {{ $returns->links() }}
                    </div>
                </div>

                <div class="pos-rail">
                    <div class="pos-rail-card">
                        <h3>Quick Nav</h3>
                        <a href="{{ route('pos.returns.create') }}" class="pos-rail-link">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            New Return
                        </a>
                        <a href="{{ route('pos.receipts.index') }}" class="pos-rail-link">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            Receipts
                        </a>
                    </div>
                    <div class="pos-rail-card">
                        <h3>Views</h3>
                        <a href="{{ route('pos.returns.index') }}" class="pos-rail-view on">All Returns</a>
                        <a href="{{ route('pos.returns.index', ['status' => 'posted']) }}" class="pos-rail-view">Posted</a>
                        <a href="{{ route('pos.returns.index', ['status' => 'draft']) }}" class="pos-rail-view">Draft</a>
                    </div>
                </div>
            </div>
</x-app-layout>
