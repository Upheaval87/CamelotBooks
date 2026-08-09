@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $initialsFor = function ($name) {
        $words = explode(' ', trim((string) $name));
        $ini = '';
        foreach ($words as $w) {
            if (mb_strlen($w) > 0) {
                $ini .= mb_strtoupper(mb_substr($w, 0, 1));
            }
        }
        return mb_substr($ini, 0, 2);
    };
    $statusFilter = request('status');
@endphp
<x-app-layout>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="cs-suite">

                {{-- §1 page-head --}}
                <div class="page-head">
                    <div>
                        <h1>Customers</h1>
                        <div class="sub">Manage customer records, terms and balances.</div>
                    </div>
                    <div class="tbtns">
                        <a href="{{ route('accounting.customers.export') }}" class="btn ghost">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                            Export
                        </a>
                        <a href="{{ route('accounting.customers.create') }}" class="btn cta">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg>
                            Create Customer
                        </a>
                    </div>
                </div>

                <div class="shell">
                    <div>

                        {{-- Portfolio --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg></span>
                                <h2>Portfolio</h2>
                                <span class="rule"></span>
                            </div>

                            <div class="sgrid">
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg></span>
                                    <div>
                                        <div class="l">Customers</div>
                                        <div class="v">{{ number_format($stats['total']) }}</div>
                                    </div>
                                </div>
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                    <div>
                                        <div class="l">Active</div>
                                        <div class="v">{{ number_format($stats['active']) }}</div>
                                    </div>
                                </div>
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                                    <div>
                                        <div class="l">Outstanding ({{ $cs }})</div>
                                        <div class="v {{ $stats['balance_owed'] > 0 ? 'red' : 'mint' }}">{{ number_format($stats['balance_owed'], 2) }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- controls: search + status + terms --}}
                            <form method="GET" action="{{ route('accounting.customers.index') }}" id="cust-list-form">
                                <div class="controls">
                                    <div class="search">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                                        <input type="text" name="search" class="input" placeholder="Name or email..." value="{{ request('search') }}" />
                                    </div>
                                    <select name="status" class="input">
                                        <option value="">All Statuses</option>
                                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    <select name="terms" class="input">
                                        <option value="">All Terms</option>
                                        @foreach(['due_on_receipt' => 'Due on receipt', 'net_15' => 'Net 15', 'net_30' => 'Net 30', 'net_60' => 'Net 60', 'net_90' => 'Net 90', 'custom' => 'Custom'] as $termKey => $termLabel)
                                            <option value="{{ $termKey }}" {{ request('terms') === $termKey ? 'selected' : '' }}>{{ $termLabel }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn ghost">Filter</button>
                                    @if(request()->hasAny('search', 'status', 'terms'))
                                        <a href="{{ route('accounting.customers.index') }}" class="btn ghost">Clear</a>
                                    @endif
                                    <span class="chip-t">{{ $customers->total() }} customers</span>
                                </div>
                            </form>
                        </section>

                        {{-- customer list --}}
                        <section class="card" style="padding:20px 24px; margin-top:16px">
                            <div class="li-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:22%">Name</th>
                                            <th style="width:22%">Email</th>
                                            <th style="width:15%">Phone</th>
                                            <th style="width:9%">Terms</th>
                                            <th class="num" style="width:12%">Balance ({{ $cs }})</th>
                                            <th style="width:11%">Status</th>
                                            <th style="width:9%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customers as $customer)
                                        <tr>
                                            <td>
                                                <div class="cust">
                                                    <span class="ava">{{ $initialsFor($customer->name) }}<span class="st {{ $customer->is_active ? '' : 'inact' }}"></span></span>
                                                    <div class="nm">
                                                        <a href="{{ route('accounting.customers.show', $customer) }}">{{ $customer->name }}</a>
                                                        <span class="s">Since {{ $customer->created_at?->format('M Y') }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="em">{{ $customer->email ?? '—' }}</td>
                                            <td class="em">{{ $customer->phone ?? '—' }}</td>
                                            <td><span class="tchip">{{ str_replace('_', ' ', ucfirst($customer->payment_terms ?? 'due_on_receipt')) }}</span></td>
                                            <td class="numr {{ $customer->balance_due > 0 ? 'red' : '' }}">{{ format_number($customer->balance_due) }}</td>
                                            <td>
                                                @if($customer->is_active)
                                                    <span class="badge b-act"><span class="bdot"></span>Active</span>
                                                @else
                                                    <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="row-act">
                                                    <a href="{{ route('accounting.customers.show', $customer) }}" class="ibtn" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                                    <a href="{{ route('accounting.customers.edit', $customer) }}" class="ibtn" title="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                                    <form method="POST" action="{{ route('accounting.customers.toggle', $customer) }}" class="inline" @if($customer->is_active) onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to deactivate this customer?') }}', { type: 'danger' })" @endif>
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="ibtn {{ $customer->is_active ? 'del' : '' }}" title="{{ $customer->is_active ? 'Deactivate' : 'Activate' }}">
                                                            @if($customer->is_active)
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16m-10 0V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0l-1 12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 7"/></svg>
                                                            @else
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                                            @endif
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7"><div class="empty">No customers found.</div></td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                @if($customers->hasPages())
                                    @php
                                        $paginator = $customers->appends(request()->query());
                                        $last = $paginator->lastPage();
                                        $cur = $paginator->currentPage();
                                        $winStart = max(1, $cur - 2);
                                        $winEnd = min($last, $cur + 2);
                                        $firstItem = $paginator->firstItem() ?: 0;
                                        $lastItem = $paginator->lastItem() ?: 0;
                                    @endphp
                                    <div class="pagi">
                                        <span class="t">Showing {{ $firstItem }}–{{ $lastItem }} of {{ $paginator->total() }} customers</span>
                                        <span class="pg">
                                            @if($paginator->onFirstPage())
                                                <span class="pgbtn" aria-disabled="true" aria-label="Previous">‹</span>
                                            @else
                                                <a href="{{ $paginator->previousPageUrl() }}" aria-label="Previous">‹</a>
                                            @endif

                                            @if($winStart > 1)
                                                <a href="{{ $paginator->url(1) }}">1</a>
                                                @if($winStart > 2)<span class="pgbtn dots" aria-hidden="true">…</span>@endif
                                            @endif

                                            @for($page = $winStart; $page <= $winEnd; $page++)
                                                @if($page === $cur)
                                                    <span class="pgbtn cur" aria-current="page">{{ $page }}</span>
                                                @else
                                                    <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
                                                @endif
                                            @endfor

                                            @if($winEnd < $last)
                                                @if($winEnd < $last - 1)<span class="pgbtn dots" aria-hidden="true">…</span>@endif
                                                <a href="{{ $paginator->url($last) }}">{{ $last }}</a>
                                            @endif

                                            @if($paginator->hasMorePages())
                                                <a href="{{ $paginator->nextPageUrl() }}" aria-label="Next">›</a>
                                            @else
                                                <span class="pgbtn" aria-disabled="true" aria-label="Next">›</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>

                    {{-- right rail --}}
                    <aside class="railsum">
                        <div class="card">
                            <div class="rail-sec">
                                <div class="sec-head">
                                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                                    <h2>Views</h2>
                                    <span class="rule"></span>
                                </div>
                                <div class="vlist">
                                    <a href="{{ route('accounting.customers.index') }}" class="vitem {{ !$statusFilter ? 'on' : '' }}" {{ !$statusFilter ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg></span>
                                        All Customers
                                    </a>
                                    <a href="{{ route('accounting.customers.index', ['status' => 'active']) }}" class="vitem {{ $statusFilter === 'active' ? 'on' : '' }}" {{ $statusFilter === 'active' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                        Active
                                    </a>
                                    <a href="{{ route('accounting.customers.index', ['status' => 'inactive']) }}" class="vitem {{ $statusFilter === 'inactive' ? 'on' : '' }}" {{ $statusFilter === 'inactive' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                        Inactive
                                    </a>
                                </div>
                            </div>
                            <div class="rail-sec">
                                <div class="sec-head">
                                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg></span>
                                    <h2>Reports</h2>
                                    <span class="rule"></span>
                                </div>
                                <div class="vlist">
                                    <a href="{{ route('accounting.aging.ar-summary') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                                        Customer Balances
                                    </a>
                                    <a href="{{ route('accounting.reports.sales-by-customer') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3.055A9.001 9.001 0 1 0 20.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0 1 20.488 9z"/></svg></span>
                                        Sales by Customer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
