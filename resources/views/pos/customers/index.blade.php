<x-app-layout>
    @php
        $companyId = (int) session('current_company_id');
        $lastPurchases = $lastPurchases ?? \App\Models\PosSale::forCompany($companyId)
            ->posted()
            ->whereIn('customer_id', $customers->getCollection()->pluck('id'))
            ->selectRaw('customer_id, MAX(created_at) AS last_purchase')
            ->groupBy('customer_id')
            ->pluck('last_purchase', 'customer_id');
    @endphp

    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>POS Customers</h1>
                <p class="pos-sub">Profiles · credit limits · history · statements</p>
            </div>
            <div class="pos-actions">
                <form method="GET" action="{{ route('pos.customers.index') }}" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
                    <div class="pos-f" style="margin-bottom:0">
                        <label>Status</label>
                        <select name="status" onchange="this.form.submit()" class="pos-in" style="width:auto;height:38px">
                            <option value="">All Statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="pos-search" style="width:280px">
                        <svg class="pos-mag" width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <input type="text" name="q" class="pos-in" placeholder="Search customers…" value="{{ request('q') }}">
                    </div>
                    <button type="submit" class="pos-btn pos-btn-ghost pos-btn-sm">Search</button>
                </form>
            </div>
        </div>

        <div class="pos-shell">
            <div class="pos-card">
                <div class="pos-li-wrap">
                    <table class="pos-tbl">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Payment Terms</th>
                                <th class="num">Balance</th>
                                <th>Status</th>
                                <th class="num">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                                <tr>
                                    <td>
                                        <div style="font-weight:700">{{ $customer->name }}</div>
                                        <div style="font-size:11px;color:var(--pos-muted)">{{ $customer->email ?? '—' }}</div>
                                    </td>
                                    <td class="pos-em">{{ $customer->phone ?? '—' }}</td>
                                    <td><span class="pos-tchip">{{ $customer->payment_terms ?? '—' }}</span></td>
                                    <td class="num pos-bold">{{ format_money($customer->sales_sum_total ?? 0) }}</td>
                                    <td>
                                        @if($customer->is_active)
                                            <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Active</span>
                                        @else
                                            <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Inactive</span>
                                        @endif
                                    </td>
                                    <td class="num">
                                        <div class="pos-row-act">
                                            <a href="{{ route('accounting.customers.show', $customer) }}" class="pos-ibtn" title="View">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">
                                    <div class="pos-empty">
                                        <h3>No customers found</h3>
                                        <p>Customer profiles will appear here once created.</p>
                                    </div>
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pos-pag">
                    <span>Showing {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} of {{ number_format($customers->total()) }} customers</span>
                    {{ $customers->withQueryString()->links() }}
                </div>
            </div>

            <div class="pos-rail">
                <div class="pos-rail-card">
                    <h3>Quick Nav</h3>
                    <a href="{{ route('pos.sales.checkout') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        New Sale
                    </a>
                    <a href="{{ route('pos.customers.index') }}" class="pos-rail-link on">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        Customers
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
