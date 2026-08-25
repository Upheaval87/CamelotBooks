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

    <div class="pos-wrap">
        <div class="pos-head">
            <div>
                <h1>POS Customers</h1>
                <p class="pos-sub">{{ number_format($customers->total()) }} customer{{ $customers->total() === 1 ? '' : 's' }} · purchase history & loyalty</p>
            </div>
            <div class="pos-grow"></div>
            <form method="GET" action="{{ route('pos.customers.index') }}" class="pos-actions">
                <select name="status" onchange="this.form.submit()"
                    class="h-[38px] rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-[12.5px] font-bold text-[color:var(--ink)] focus:border-[color:var(--sec)] focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
                <div class="pos-search" style="width:280px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#8AA5A7" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    <input type="text" name="q" placeholder="Search customers…" value="{{ request('q') }}">
                </div>
                <button type="submit" class="pos-btn pos-btn-sec">Search</button>
            </form>
        </div>

        <div class="pos-card">
            <div class="pos-tbl-wrap">
                <table class="pos-tbl">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th class="r">Total Purchases</th>
                            <th>Last Purchase</th>
                            <th>Status</th>
                            <th class="r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td style="font-weight:700">{{ $customer->name }}</td>
                                <td>{{ $customer->email ?? '—' }}</td>
                                <td>{{ $customer->phone ?? '—' }}</td>
                                <td class="r" style="font-weight:700">{{ format_money($customer->sales_sum_total ?? 0) }}</td>
                                <td>
                                    @php
                                        $lastPurchase = $lastPurchases[$customer->id] ?? null;
                                    @endphp
                                    {{ $lastPurchase?->format('d M Y') ?? '—' }}
                                </td>
                                <td>
                                    @if($customer->is_active)
                                        <span class="pos-badge pos-badge-active"><span class="pos-badge-dot green"></span>Active</span>
                                    @else
                                        <span class="pos-badge pos-badge-muted"><span class="pos-badge-dot"></span>Inactive</span>
                                    @endif
                                </td>
                                <td class="r">
                                    <a href="{{ route('accounting.customers.show', $customer) }}" class="pos-btn pos-btn-xs pos-btn-sec">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="pos-tbl-empty">No customers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between border-t border-[color:var(--line)] px-5 py-3 text-[12.5px]" style="color:var(--muted)">
                <span>Showing {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} of {{ number_format($customers->total()) }} customers</span>
                {{ $customers->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
