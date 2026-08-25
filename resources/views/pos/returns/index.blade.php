<x-app-layout>
    <div class="pos">
        <div class="wrap">
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

            {{-- Table --}}
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
                                            <a href="{{ route('pos.returns.show', $return) }}" class="pos-ibtn" title="View">👁</a>
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
        </div>
    </div>
</x-app-layout>
