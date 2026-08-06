<x-app-layout>
    <x-slot name="header">{{ __('Assignments') }}</x-slot>

    @include('superadmin._nav', ['active' => 'assignments'])

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="card p-6">
                <h3 class="text-sm font-semibold text-ink mb-6">{{ __('All Assignments') }}</h3>
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-gray-500">{{ __('User-to-company access with role and branch scope.') }}</p>
                    <a href="{{ route('superadmin.assignments.create') }}" class="list-header-create">{{ __('New Assignment') }}</a>
                </div>

                <div class="list-table-wrap">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Company') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Branches') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td>
                                        <a href="{{ route('superadmin.users.show', $assignment->user) }}" class="font-medium text-ink">{{ $assignment->user->name }}</a>
                                        <span class="block text-xs text-gray-500">{{ $assignment->user->email }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('superadmin.companies.show', $assignment->company) }}" class="text-ink">{{ $assignment->company->name }}</a>
                                    </td>
                                    <td>{{ $assignment->role }}</td>
                                    <td>
                                        @if(count($assignment->branch_ids ?? []))
                                            <span class="text-gray-600">{{ count($assignment->branch_ids ?? []) }} {{ __('branches') }}</span>
                                        @else
                                            <span class="text-gray-400">{{ __('All branches') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($assignment->is_active)
                                            <x-status-badge variant="success">Active</x-status-badge>
                                        @else
                                            <x-status-badge variant="default">Inactive</x-status-badge>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('superadmin.assignments.edit', $assignment) }}" class="text-sm text-accent hover:underline">{{ __('Edit') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-gray-500">{{ __('No assignments yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $assignments->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
