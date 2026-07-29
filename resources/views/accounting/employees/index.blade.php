<x-app-layout>
    <x-slot name="header">{{ __('Create Employee') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.employees.create') }}">
                    {{ __('Create Employee') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.employees.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="search" value="{{ __('Search') }}" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Name, employee number, or email..." />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="department" value="{{ __('Department') }}" />
                        <x-text-input id="department" name="department" type="text" class="mt-1 block w-full" :value="request('department')" placeholder="Department..." />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('search') || request('department') || request('status'))
                            <a href="{{ route('accounting.employees.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Employee Number</th>
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
                                <tr class="{{ $employee->is_active ? '' : 'bg-gray-50 text-gray-400' }}">
                                    <td>
                                        {{ $employee->employee_number }}
                                    </td>
                                    <td>
                                        <a href="{{ route('accounting.employees.show', $employee) }}" class="text-ink hover:text-gold">
                                            {{ $employee->full_name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $employee->position ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $employee->department ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $employee->branch->name ?? '—' }}
                                    </td>
                                    <td class="text-center">
                                        @if($employee->is_active)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill neutral">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.employees.show', $employee) }}" class="text-green-600 hover:text-green-900">View</a>
                                        <a href="{{ route('accounting.employees.edit', $employee) }}" class="text-ink hover:text-gold">Edit</a>
                                        <form method="POST" action="{{ route('accounting.employees.toggle', $employee) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-{{ $employee->is_active ? 'red' : 'green' }}-600 hover:text-{{ $employee->is_active ? 'red' : 'green' }}-900">
                                                {{ $employee->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No employees found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($employees->hasPages())
                    <div class="px-6 py-3 border-t border-gray-200">
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
