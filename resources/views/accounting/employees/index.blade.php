<x-app-layout>
    <x-slot name="header">{{ __('Employees') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-list-header title="Employees" createRoute="{{ route('accounting.employees.create') }}" createLabel="Create Employee" />

            <div class="list-layout">
                <div class="list-layout-content">
                    <x-list-filter-bar searchRoute="{{ route('accounting.employees.index') }}" searchPlaceholder="Name, number, or email...">
                        <input type="text" name="department" value="{{ request('department') }}" placeholder="Department..." class="list-filter-input" style="max-width:160px">
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
                                    <th>Employee #</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Branch</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $employee)
                                <tr>
                                    <td><span class="text-ink-soft">{{ $employee->employee_number }}</span></td>
                                    <td>
                                        <div class="name-cell">
                                            <x-list-avatar-initials name="{{ $employee->full_name }}" size="sm" />
                                            <div class="name-cell-text">
                                                <span class="name-cell-primary"><a href="{{ route('accounting.employees.show', $employee) }}">{{ $employee->full_name }}</a></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="text-ink-soft">{{ $employee->position ?? '—' }}</span></td>
                                    <td><span class="text-ink-soft">{{ $employee->department ?? '—' }}</span></td>
                                    <td><span class="text-ink-soft">{{ $employee->branch->name ?? '—' }}</span></td>
                                    <td class="text-center">@if($employee->is_active)<span class="status-pill positive">Active</span>@else<span class="status-pill neutral">Inactive</span>@endif</td>
                                    <td>
                                        <div class="action-group">
                                            <a href="{{ route('accounting.employees.show', $employee) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><span class="icon-btn-tooltip">View</span></a>
                                            <a href="{{ route('accounting.employees.edit', $employee) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg><span class="icon-btn-tooltip">Edit</span></a>
                                            <form method="POST" action="{{ route('accounting.employees.toggle', $employee) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M{{ $employee->is_active ? '19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' : '18 4l-4 4m0 0l-4-4m4 4V2m-4 6l4 4m-4-4H2' }}"/></svg><span class="icon-btn-tooltip">{{ $employee->is_active ? 'Deactivate' : 'Activate' }}</span></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-ink-soft py-8">No employees found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($employees->hasPages())
                        <div class="px-6 py-3 border-t border-gray-200">{{ $employees->links() }}</div>
                        @endif
                    </div>

                    <div class="list-mobile-cards">
                        @forelse($employees as $employee)
                        <div class="list-mobile-card">
                            <div class="name-cell mb-2">
                                <x-list-avatar-initials name="{{ $employee->full_name }}" size="sm" />
                                <div class="name-cell-text">
                                    <span class="name-cell-primary"><a href="{{ route('accounting.employees.show', $employee) }}">{{ $employee->full_name }}</a></span>
                                </div>
                            </div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">#</span><span class="list-mobile-card-value">{{ $employee->employee_number }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Position</span><span class="list-mobile-card-value">{{ $employee->position ?? '—' }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Status</span><span class="list-mobile-card-value">@if($employee->is_active)<span class="status-pill positive">Active</span>@else<span class="status-pill neutral">Inactive</span>@endif</span></div>
                        </div>
                        @empty
                        <div class="text-center text-ink-soft py-8">No employees found.</div>
                        @endforelse
                        @if($employees->hasPages())
                        <div class="px-2 py-3">{{ $employees->links() }}</div>
                        @endif
                    </div>
                </div>

                <div class="list-layout-sidebar">
                    <x-list-quick-links title="Employees" :groups="[
                        [
                            ['route' => route('accounting.employees.index'), 'title' => 'All Employees', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                            ['route' => route('accounting.employees.index', ['status' => 'active']), 'title' => 'Active', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.employees.index', ['status' => 'inactive']), 'title' => 'Inactive', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            ['route' => route('accounting.employees.create'), 'title' => 'Create Employee', 'icon' => 'M12 4v16m8-8H4', 'subtitle' => 'Add new record'],
                        ],
                        [
                            ['route' => '#', 'title' => 'Department Directory', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                            ['route' => '#', 'title' => 'Payroll Summary', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ],
                    ]" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
