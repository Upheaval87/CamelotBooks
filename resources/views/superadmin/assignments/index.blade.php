<x-app-layout>

    <div class="sa-page py-6" style="background: #F8F9FC;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="sa-page-head">
                <div>
                    <h1 class="sa-page-title">{{ __('Assignments') }}</h1>
                    <p class="sa-page-subtitle">{{ __('User-to-company access with role and branch scope.') }}</p>
                </div>
                <a href="{{ route('superadmin.assignments.create') }}" class="sa-btn sa-btn--primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('New Assignment') }}
                </a>
            </div>

            <x-elevated-card :flush="true">
                <div class="sa-table-wrap">
                    <table class="sa-table sa-table--warm">
                        <thead>
                            <tr>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Company') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Branches') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td>
                                        <a href="{{ route('superadmin.users.show', $assignment->user) }}" class="sa-table-primary">{{ $assignment->user->name }}</a>
                                        <span class="sa-table-sub">{{ $assignment->user->email }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('superadmin.companies.show', $assignment->company) }}" class="sa-table-primary">{{ $assignment->company->name }}</a>
                                    </td>
                                    <td><span style="color: var(--sa-ink);">{{ $assignment->role }}</span></td>
                                    <td>
                                        @if(count($assignment->branch_ids ?? []))
                                            <span style="color: var(--sa-ink);">{{ count($assignment->branch_ids ?? []) }} {{ __('branches') }}</span>
                                        @else
                                            <span style="color: var(--sa-ink);">{{ __('All branches') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($assignment->is_active)
                                            <span class="sa-pill sa-pill--accent">Active</span>
                                        @else
                                            <span class="sa-pill sa-pill--muted">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('superadmin.assignments.edit', $assignment) }}" class="sa-btn sa-btn--tint">{{ __('Edit') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="sa-table-empty">{{ __('No assignments yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($assignments->hasPages())
                    <div class="sa-table-pagination">{{ $assignments->links() }}</div>
                @endif
            </x-elevated-card>
        </div>
    </div>
</x-app-layout>
