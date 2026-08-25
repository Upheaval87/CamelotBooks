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
            <div class="suite ex-suite stage">

                {{-- crumbs --}}
                <div class="crumbs">
                    <a href="{{ route('accounting.vendors.dashboard') }}">Vendor Centre</a>
                    <span>›</span>
                    <span class="here">Vendors</span>
                </div>

                {{-- page-head --}}
                <div class="page-head">
                    <div>
                        <h1>Vendors</h1>
                        <div class="sub">Vendor directory — records, terms, balances and contact details.</div>
                    </div>
                    <div class="cluster">
                        <a href="{{ route('accounting.vendors.export', request()->except('page')) }}" class="btn btn-ghost">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                            Export CSV
                        </a>
                        <a href="{{ route('accounting.vendors.create') }}" class="btn btn-cta">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg>
                            Create Vendor
                        </a>
                    </div>
                </div>

                {{-- status tiles (§2) --}}
                <div class="statgrid">
                    <a href="{{ route('accounting.vendors.index') }}" class="fbox {{ !$statusFilter ? 'on' : '' }}">
                        <span class="t t-ink"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg></span>
                        <span><span class="l">All</span><span class="v" style="display:block">{{ number_format($stats['total']) }}</span></span>
                    </a>
                    <a href="{{ route('accounting.vendors.index', ['status' => 'active']) }}" class="fbox {{ $statusFilter === 'active' ? 'on' : '' }}">
                        <span class="t t-mint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                        <span><span class="l">Active</span><span class="v" style="display:block">{{ number_format($stats['active']) }}</span></span>
                    </a>
                    <a href="{{ route('accounting.vendors.index', ['status' => 'inactive']) }}" class="fbox {{ $statusFilter === 'inactive' ? 'on' : '' }}">
                        <span class="t t-gray"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                        <span><span class="l">Inactive</span><span class="v" style="display:block">{{ number_format($stats['inactive']) }}</span></span>
                    </a>
                    <a href="{{ route('accounting.vendors.index', ['status' => 'overdue']) }}" class="fbox {{ $statusFilter === 'overdue' ? 'on' : '' }}">
                        <span class="t t-red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></span>
                        <span><span class="l">Overdue</span><span class="v" style="display:block">{{ number_format($stats['overdue']) }}</span></span>
                    </a>
                    <a href="{{ route('accounting.vendors.index', ['status' => 'zero']) }}" class="fbox {{ $statusFilter === 'zero' ? 'on' : '' }}">
                        <span class="t t-teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/></svg></span>
                        <span><span class="l">Zero Balance</span><span class="v" style="display:block">{{ number_format($stats['zero']) }}</span></span>
                    </a>
                </div>

                <form method="GET" action="{{ route('accounting.vendors.index') }}" id="vend-list-form">
                    <div class="controls">
                        <div class="search">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                            <input type="text" name="search" class="input" placeholder="Name, email, phone..." value="{{ request('search') }}" />
                        </div>
                        <select name="status" class="input">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            <option value="zero" {{ request('status') === 'zero' ? 'selected' : '' }}>Zero Balance</option>
                        </select>
                        <button type="submit" class="btn btn-ghost">Filter</button>
                        @if(request()->hasAny('search', 'status'))
                            <a href="{{ route('accounting.vendors.index') }}" class="btn btn-ghost">Clear</a>
                        @endif
                        <span class="chip-t">{{ $vendors->total() }} vendors</span>
                    </div>
                </form>

                {{-- bulk bar (§2) --}}
                <div id="vend-bulkbar" class="bulkbar" style="display:none">
                    <span class="bcount"><span id="vend-bcount">0</span> selected</span>
                    <span class="spacer"></span>
                    <a href="#" id="vend-bulk-export" class="bbtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                        Export
                    </a>
                    <button type="button" class="bbtn plain" id="vend-bulk-clear" onclick="window.VendBulk.clear()">Clear selection</button>
                </div>

                {{-- vendor directory --}}
                <section class="card" style="padding:20px 24px; margin-top:16px">
                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:3%"><input type="checkbox" id="vend-allchk" onchange="window.VendBulk.toggleAll(this)" /></th>
                                    <th style="width:26%">Vendor</th>
                                    <th style="width:13%">Contact Person</th>
                                    <th style="width:12%">Phone</th>
                                    <th style="width:14%">Email</th>
                                    <th style="width:9%">Terms</th>
                                    <th class="num" style="width:11%">Outstanding ({{ $cs }})</th>
                                    <th style="width:9%">Status</th>
                                    <th style="width:6%">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="vend-tbody">
                                @forelse($vendors as $vendor)
                                <tr>
                                    <td><input type="checkbox" class="vend-row" value="{{ $vendor->id }}" onchange="window.VendBulk.toggle(this)" /></td>
                                    <td>
                                        <div class="cust">
                                            <span class="ava">{{ $initialsFor($vendor->name) }}<span class="st {{ $vendor->is_active ? '' : 'inact' }}"></span></span>
                                            <div class="nm">
                                                <a href="{{ route('accounting.vendors.show', $vendor) }}">{{ $vendor->name }}</a>
                                                <span class="s">Since {{ $vendor->created_at?->format('M Y') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="em">{{ $vendor->display_name ?: '—' }}</td>
                                    <td class="em">{{ $vendor->phone ?? '—' }}</td>
                                    <td class="em">{{ $vendor->email ?? '—' }}</td>
                                    <td><span class="tchip">{{ str_replace('_', ' ', ucfirst($vendor->payment_terms ?? 'due_on_receipt')) }}</span></td>
                                    <td class="numr {{ $vendor->balance_due > 0 ? 'red' : '' }}">{{ format_number($vendor->balance_due) }}</td>
                                    <td>
                                        @if($vendor->is_active)
                                            <span class="badge b-act"><span class="bdot"></span>Active</span>
                                        @else
                                            <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="row-act">
                                            <a href="{{ route('accounting.vendors.show', $vendor) }}" class="ibtn" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                            <a href="{{ route('accounting.vendors.edit', $vendor) }}" class="ibtn" title="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                            <form method="POST" action="{{ route('accounting.vendors.toggle', $vendor) }}" class="inline" @if($vendor->is_active) onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to deactivate this vendor?') }}', { type: 'danger' })" @endif>
                                                @csrf @method('PATCH')
                                                <button type="submit" class="ibtn {{ $vendor->is_active ? 'del' : '' }}" title="{{ $vendor->is_active ? 'Deactivate' : 'Activate' }}">
                                                    @if($vendor->is_active)
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
                                    <td colspan="9"><div class="empty">No vendors found.</div></td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if($vendors->hasPages())
                            @php
                                $paginator = $vendors->appends(request()->query());
                                $last = $paginator->lastPage();
                                $cur = $paginator->currentPage();
                                $winStart = max(1, $cur - 2);
                                $winEnd = min($last, $cur + 2);
                                $firstItem = $paginator->firstItem() ?: 0;
                                $lastItem = $paginator->lastItem() ?: 0;
                            @endphp
                            <div class="pagi">
                                <span class="t">Showing {{ $firstItem }}–{{ $lastItem }} of {{ $paginator->total() }} vendors</span>
                                <span class="p">
                                    @if($paginator->onFirstPage())
                                        <button class="pg ds" aria-disabled="true" aria-label="Previous">‹</button>
                                    @else
                                        <a href="{{ $paginator->previousPageUrl() }}" class="pg" aria-label="Previous">‹</a>
                                    @endif

                                    @if($winStart > 1)
                                        <a href="{{ $paginator->url(1) }}" class="pg">1</a>
                                        @if($winStart > 2)<span class="pg ds" aria-hidden="true">…</span>@endif
                                    @endif

                                    @for($page = $winStart; $page <= $winEnd; $page++)
                                        @if($page === $cur)
                                            <span class="pg on" aria-current="page">{{ $page }}</span>
                                        @else
                                            <a href="{{ $paginator->url($page) }}" class="pg">{{ $page }}</a>
                                        @endif
                                    @endfor

                                    @if($winEnd < $last)
                                        @if($winEnd < $last - 1)<span class="pg ds" aria-hidden="true">…</span>@endif
                                        <a href="{{ $paginator->url($last) }}" class="pg">{{ $last }}</a>
                                    @endif

                                    @if($paginator->hasMorePages())
                                        <a href="{{ $paginator->nextPageUrl() }}" class="pg" aria-label="Next">›</a>
                                    @else
                                        <button class="pg ds" aria-disabled="true" aria-label="Next">›</button>
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.VendBulk = {
            sel: new Set(),
            toggleAll: function (box) {
                document.querySelectorAll('#vend-tbody tr').forEach(function (tr) {
                    var cb = tr.querySelector('input.vend-row');
                    if (cb) { cb.checked = box.checked; this.sel.add(cb.value); }
                }, this);
                this.sync();
            },
            toggle: function (cb) {
                if (cb.checked) { this.sel.add(cb.value); } else { this.sel.delete(cb.value); }
                this.sync();
            },
            sync: function () {
                var bar = document.getElementById('vend-bulkbar');
                var n = this.sel.size;
                if (n > 0) { bar.style.display = 'flex'; } else { bar.style.display = 'none'; }
                document.getElementById('vend-bcount').textContent = n;
                var all = document.getElementById('vend-allchk');
                if (all) { all.checked = n === document.querySelectorAll('input.vend-row').length; }
                var url = new URL(document.getElementById('vend-bulk-export').href);
                url.searchParams.set('ids', Array.from(this.sel).join(','));
                document.getElementById('vend-bulk-export').href = url.toString();
            },
            clear: function () {
                this.sel.clear();
                document.querySelectorAll('input.vend-row').forEach(function (cb) { cb.checked = false; });
                this.sync();
            }
        };
    </script>
    @endpush
</x-app-layout>
