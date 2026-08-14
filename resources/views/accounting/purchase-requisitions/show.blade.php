<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');

        $badgeMap = [
            'draft' => 'b-draft',
            'submitted' => 'b-pend',
            'approved' => 'b-app',
            'rejected' => 'b-rej',
            'converted' => 'b-conv',
            'void' => 'b-void',
        ];
        $badgeClass = $badgeMap[$requisition->status] ?? 'b-draft';
        $statusLabel = $requisition->statusLabel();

        $budgetStatus = $budgetCheck['status'] ?? 'no_budget';
        $budgetLabel = $budgetStatus === 'within' ? __('Within budget') : ($budgetStatus === 'exceeded' ? __('Over budget') : __('—'));
        $budgetNote = $requisition->costCenter?->name ?? ($budgetStatus === 'no_budget' ? __('no budget linked') : __(''));

        $total = $requisition->grandTotal();
        $linesCount = $requisition->lines->count();

        $submittedAt = $requisition->submitted_at ?? null;
        $approvedAt = $requisition->approved_at ?? null;

        $requesterName = $requisition->requestedBy?->name ?? $requisition->createdBy?->name ?? '—';
    @endphp

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="pr-suite wrap">

                {{-- Sticky page head --}}
                <div class="sticky-head">
                    <div class="left" style="display:flex;align-items:center;gap:10px">
                        <a href="{{ route('accounting.purchase-requisitions.index') }}" class="icon-btn" aria-label="{{ __('Back to requisitions') }}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <nav class="crumbs">
                            <a href="{{ route('accounting.purchase-orders.index') }}">{{ __('Purchasing') }}</a>
                            <span aria-hidden="true">›</span>
                            <a href="{{ route('accounting.purchase-requisitions.index') }}">{{ __('Requisitions') }}</a>
                            <span aria-hidden="true">›</span>
                            <span class="here">{{ $requisition->requisition_number }}</span>
                        </nav>
                    </div>
                    <div class="cluster">
                        @if($requisition->status === 'draft')
                            <a href="{{ route('accounting.purchase-requisitions.edit', $requisition) }}" class="btn btn-ghost btn-sm">✎ {{ __('Edit') }}</a>
                            @if($requisition->created_by && (int) $requisition->created_by !== (int) auth()->id())
                                <form method="POST" action="{{ route('accounting.purchase-requisitions.destroy', $requisition) }}" id="pr-delete-form" class="inline" style="margin:0" onsubmit="return fbConfirmSubmit(event, 'Delete this draft requisition? This cannot be undone.', { type: 'danger' })">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="submit" form="pr-delete-form" class="btn btn-danger-o btn-sm">{{ __('Delete') }}</button>
                            @endif
                        @endif
                        <a href="javascript:window.print()" class="btn btn-ghost btn-sm">🖨 {{ __('Print / PDF') }}</a>
                        @if($requisition->status === 'approved')
                            <a href="{{ route('accounting.purchase-orders.create', ['requisition_id' => $requisition->id]) }}" class="btn btn-sec btn-sm">⚙ {{ __('Convert to PO') }}</a>
                        @elseif($requisition->status === 'converted' && $requisition->purchaseOrder)
                            <a href="{{ route('accounting.purchase-orders.show', $requisition->purchaseOrder) }}" class="btn btn-sec btn-sm">⚙ {{ __('View Purchase Order') }}</a>
                        @endif
                    </div>
                </div>

                {{-- Identity profile --}}
                <section class="card">
                    <div class="prof">
                        <span class="ava-xl"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                        <div>
                            <div class="n">
                                {{ __('Requisition') }}
                                <span class="mono-chip">{{ $requisition->requisition_number }}</span>
                                <span class="badge {{ $badgeClass }}"><span class="bdot"></span>{{ __($statusLabel) }}</span>
                                @if($requisition->priority === 'urgent')
                                    <span class="prio urg">{{ __('Urgent') }}</span>
                                @endif
                            </div>
                            <div class="c">
                                <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>{{ $requesterName }}</span>
                                <span>{{ $requisition->department ?? __('General') }}@if($requisition->costCenter?->name) · {{ $requisition->costCenter->name }}@endif</span>
                                <span>{{ __('Needed by') }} {{ $requisition->required_by?->format('M d, Y') ?? '—' }}</span>
                                @if($requisition->supplier)
                                    <span>{{ __('Suggested') }} · {{ $requisition->supplier }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Summary --}}
                <div class="sumbar" aria-label="Requisition summary">
                    <div class="cell"><div class="l">{{ __('Subtotal') }}</div><div class="v">{{ number_format($requisition->subtotal(), 2) }}</div><div class="n">{{ $linesCount }} {{ __('lines') }}</div></div>
                    <div class="cell"><div class="l">{{ __('Est. Tax') }}</div><div class="v">{{ number_format($requisition->estimatedTax(), 2) }}</div><div class="n">—</div></div>
                    <div class="cell"><div class="l">{{ __('Budget Check') }}</div><div class="v" style="{{ $budgetStatus === 'exceeded' ? 'color:var(--red-2,#b91c1c)' : '' }}">{{ $budgetLabel }}</div><div class="n">{{ $budgetNote }}</div></div>
                    @if($requisition->status === 'submitted')
                        <div class="cell hero amber"><div class="l">{{ __('Awaiting Approval') }}</div><div class="v">{{ $cs }}{{ number_format($total, 2) }}</div></div>
                    @elseif($requisition->status === 'rejected')
                        <div class="cell hero amber"><div class="l">{{ __('Not Approved') }}</div><div class="v">{{ $cs }}{{ number_format($total, 2) }}</div></div>
                    @else
                        <div class="cell hero"><div class="l">{{ __('Grand Total') }}</div><div class="v">{{ $cs }}{{ number_format($total, 2) }}</div></div>
                    @endif
                </div>

                {{-- Main card --}}
                <section class="card">
                    <div class="card-sec">
                        <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg></span><h2>{{ __('Approval Workflow') }}</h2><span class="rule"></span></div>
                        <div class="steps">
                            <div class="step">
                                <span class="sdot {{ $submittedAt || $requisition->status !== 'draft' ? 'done' : 'todo' }}">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <div><div class="tt">{{ __('Submitted') }}</div><div class="mm">{{ $requesterName }} {{ __('raised the requisition') }}</div></div>
                                <span class="when">{{ $submittedAt?->format('M d, g:i') ?? ($requisition->status === 'draft' ? __('not yet') : '—') }}</span>
                            </div>
                            <div class="step">
                                <span class="sdot {{ $requisition->status === 'submitted' ? 'cur' : (in_array($requisition->status, ['approved','rejected','converted'], true) ? 'done' : 'todo') }}">
                                    @if($requisition->status === 'submitted')
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="4" fill="currentColor"/></svg>
                                    @else
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @endif
                                </span>
                                <div>
                                    <div class="tt">{{ __('Approval') }}</div>
                                    <div class="mm">
                                        @if($requisition->status === 'submitted')
                                            {{ __('Awaiting approval from a manager or approver.') }}
                                        @elseif($requisition->status === 'approved')
                                            {{ __('Approved by') }} {{ $requisition->approvedBy?->name ?? '—' }}
                                        @elseif($requisition->status === 'rejected')
                                            {{ __('Rejected.') }} {{ $requisition->rejected_reason ?? '' }}
                                        @elseif($requisition->status === 'converted')
                                            {{ __('Approved and converted.') }}
                                        @else
                                            {{ __('Not yet submitted.') }}
                                        @endif
                                    </div>
                                </div>
                                <span class="when">{{ $requisition->status === 'submitted' ? __('in review') : ($approvedAt?->format('M d, g:i') ?? ($requisition->status === 'rejected' ? __('rejected') : ($requisition->status === 'converted' ? __('done') : __('queued')))) }}</span>
                            </div>
                            <div class="step">
                                <span class="sdot {{ in_array($requisition->status, ['approved','converted'], true) ? 'done' : ($requisition->status === 'submitted' ? 'cur' : 'todo') }}">
                                    @if(in_array($requisition->status, ['approved','converted'], true))
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @else
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/></svg>
                                    @endif
                                </span>
                                <div><div class="tt">{{ __('Budget Check') }}</div><div class="mm">{{ __('Budget and cost-centre validation.') }}</div></div>
                                <span class="when">{{ $budgetStatus === 'within' ? __('within budget') : ($budgetStatus === 'exceeded' ? __('over budget') : __('no budget')) }}</span>
                            </div>
                            <div class="step">
                                <span class="sdot {{ $requisition->status === 'converted' ? 'done' : ($requisition->status === 'approved' ? 'cur' : 'todo') }}">
                                    @if($requisition->status === 'converted')
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8.5 12.5l2.5 2.5 5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @else
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/></svg>
                                    @endif
                                </span>
                                <div>
                                    <div class="tt">{{ __('Convert to Purchase Order') }}</div>
                                    <div class="mm">{{ $requisition->status === 'converted' && $requisition->purchaseOrder ? __('Purchase order') . ' #' . $requisition->purchaseOrder->po_number : ($requisition->status === 'approved' ? __('Ready to create a purchase order.') : __('Auto-suggested PO on final approval.')) }}</div>
                                </div>
                                <span class="when">{{ $requisition->status === 'converted' ? __('done') : ($requisition->status === 'approved' ? __('ready') : __('queued')) }}</span>
                            </div>
                        </div>

                        @if($requisition->status === 'submitted' && $canDecide)
                            <div class="appr-actions">
                                <form method="POST" action="{{ route('accounting.purchase-requisitions.approve', $requisition) }}" id="pr-approve-form" style="margin:0">
                                    @csrf
                                </form>
                                <form method="POST" action="{{ route('accounting.purchase-requisitions.reject', $requisition) }}" id="pr-reject-form" style="margin:0">
                                    @csrf
                                    <input class="input" name="rejected_reason" style="flex:1;min-width:220px" placeholder="{{ __('Add a rejection reason (optional)…') }}" />
                                </form>
                                <button type="submit" form="pr-approve-form" class="btn btn-sec btn-sm">✓ {{ __('Approve') }}</button>
                                <button type="submit" form="pr-reject-form" class="btn btn-danger-o btn-sm">{{ __('Reject') }}</button>
                            </div>
                        @elseif($requisition->status === 'submitted' && auth()->user()->can('purchase-requisitions.approve'))
                            <div class="appr-actions" style="border-top:none;padding-top:8px">
                                <span style="font-size:11.5px;color:var(--faint,#8aa5a7)">{{ __('Waiting for a different approver (you cannot decide on your own requisition).') }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="card-sec">
                        <div class="sec-head"><span class="sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><h2>{{ __('Line Items') }}</h2><span class="rule"></span></div>
                        <div class="li-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:10%">{{ __('Code') }}</th>
                                        <th style="width:20%">{{ __('Item') }}</th>
                                        <th style="width:25%">{{ __('Description') }}</th>
                                        <th class="num" style="width:8%">{{ __('Qty') }}</th>
                                        <th style="width:10%">{{ __('Unit') }}</th>
                                        <th class="num" style="width:13%">{{ __('Est. Unit Price') }}</th>
                                        <th class="num" style="width:14%">{{ __('Amount') }} ({{ $cs }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($requisition->lines as $line)
                                        <tr>
                                            <td class="mono">{{ $line->product?->sku ?? '—' }}</td>
                                            <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $line->product?->name ?? $line->description }}</td>
                                            <td class="em">{{ $line->description }}</td>
                                            <td class="numr">{{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }}</td>
                                            <td class="em">{{ $line->costCenter?->name ?? '—' }}</td>
                                            <td class="numr">{{ $line->estimated_unit_cost ? number_format($line->estimated_unit_cost, 2) : '—' }}</td>
                                            <td class="numr">{{ $line->estimated_total ? number_format($line->estimated_total, 2) : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="em" colspan="7" style="text-align:center;padding:28px">{{ __('No line items.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="li-totals">
                            <div class="box">
                                <div class="trow"><span>{{ __('Subtotal') }}</span><span class="v">{{ number_format($requisition->subtotal(), 2) }}</span></div>
                                <div class="trow"><span>{{ __('Est. Tax') }}</span><span class="v">{{ number_format($requisition->estimatedTax(), 2) }}</span></div>
                                <div class="trow total"><span>{{ __('Grand Total') }}</span><span class="v">{{ $cs }}{{ number_format($total, 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
