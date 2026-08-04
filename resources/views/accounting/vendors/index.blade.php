@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Vendors') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-list-header title="Vendors" createRoute="{{ route('accounting.vendors.create') }}" createLabel="Create Vendor" />

            <div class="list-layout">
                <div class="list-layout-content">
                    <x-list-filter-bar searchRoute="{{ route('accounting.vendors.index') }}" searchPlaceholder="Name or email..." entity="vendor">
                        <select name="status" class="list-filter-select">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </x-list-filter-bar>

                    @if(session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
                    @endif

                    <div class="list-table-wrap">
                        <table class="list-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Terms</th>
                                    <th class="text-right">Balance ({{ $cs }})</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vendors as $vendor)
                                <tr>
                                    <td>
                                        <div class="name-cell">
                                            <x-list-avatar-initials name="{{ $vendor->name }}" size="sm" />
                                            <div class="name-cell-text">
                                                <span class="name-cell-primary"><a href="{{ route('accounting.vendors.show', $vendor) }}">{{ $vendor->name }}</a></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="text-ink-soft">{{ $vendor->email ?? '—' }}</span></td>
                                    <td><span class="text-ink-soft">{{ $vendor->phone ?? '—' }}</span></td>
                                    <td><span class="text-ink-soft">{{ str_replace('_', ' ', ucfirst($vendor->payment_terms ?? 'due_on_receipt')) }}</span></td>
                                    <td class="list-numeric">{{ format_number($vendor->balance_due) }}</td>
                                    <td class="text-center">@if($vendor->is_active)<span class="status-pill positive">Active</span>@else<span class="status-pill neutral">Inactive</span>@endif</td>
                                    <td>
                                        <div class="action-group">
                                            <a href="{{ route('accounting.vendors.show', $vendor) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><span class="icon-btn-tooltip">View</span></a>
                                            <a href="{{ route('accounting.vendors.edit', $vendor) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg><span class="icon-btn-tooltip">Edit</span></a>
                                            <form method="POST" action="{{ route('accounting.vendors.toggle', $vendor) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M{{ $vendor->is_active ? '19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' : '18 4l-4 4m0 0l-4-4m4 4V2m-4 6l4 4m-4-4H2' }}"/></svg><span class="icon-btn-tooltip">{{ $vendor->is_active ? 'Deactivate' : 'Activate' }}</span></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-ink-soft py-8">No vendors found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($vendors->hasPages())
                        <div class="px-6 py-3 border-t border-gray-200">{{ $vendors->links() }}</div>
                        @endif
                    </div>

                    <div class="list-mobile-cards">
                        @forelse($vendors as $vendor)
                        <div class="list-mobile-card">
                            <div class="name-cell mb-2">
                                <x-list-avatar-initials name="{{ $vendor->name }}" size="sm" />
                                <div class="name-cell-text">
                                    <span class="name-cell-primary"><a href="{{ route('accounting.vendors.show', $vendor) }}">{{ $vendor->name }}</a></span>
                                </div>
                            </div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Email</span><span class="list-mobile-card-value">{{ $vendor->email ?? '—' }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Balance</span><span class="list-mobile-card-value">{{ format_number($vendor->balance_due) }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Status</span><span class="list-mobile-card-value">@if($vendor->is_active)<span class="status-pill positive">Active</span>@else<span class="status-pill neutral">Inactive</span>@endif</span></div>
                        </div>
                        @empty
                        <div class="text-center text-ink-soft py-8">No vendors found.</div>
                        @endforelse
                        @if($vendors->hasPages())
                        <div class="px-2 py-3">{{ $vendors->links() }}</div>
                        @endif
                    </div>
                </div>

                <div class="list-layout-sidebar">
                    <x-list-quick-links title="Vendors" :groups="[
                        [
                            ['route' => route('accounting.vendors.index'), 'title' => 'All Vendors', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                            ['route' => route('accounting.vendors.index', ['status' => 'active']), 'title' => 'Active', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.vendors.index', ['status' => 'inactive']), 'title' => 'Inactive', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.vendors.create'), 'title' => 'Create Vendor', 'icon' => 'M12 4v16m8-8H4', 'subtitle' => 'Add new record'],
                        ],
                        [
                            ['route' => '#', 'title' => 'Vendor Balances', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                            ['route' => '#', 'title' => 'Purchases by Vendor', 'icon' => 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z'],
                        ],
                    ]" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
