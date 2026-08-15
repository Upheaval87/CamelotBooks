<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Assignments') }}" description="{{ __('User-to-company access with role and branch scope.') }}">
            <a href="{{ route('superadmin.assignments.create') }}" class="inline-flex items-center gap-2 rounded-[12px] border border-white/20 bg-gradient-to-b from-gold-500 to-gold-600 px-5 py-3 text-sm font-semibold text-white shadow-new transition hover:-translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New Assignment') }}
            </a>
        </x-superadmin.page-head>

        <div class="rounded-3xl bg-white/[.66] p-6 shadow-card backdrop-blur-[14px]">
            <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                <table class="w-full min-w-[960px] border-collapse text-sm">
                    <thead>
                        <tr>
                            <th class="bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 px-5 py-4 text-left text-[0.786rem] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead">{{ __('User') }}</th>
                            <th class="bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 px-5 py-4 text-left text-[0.786rem] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead">{{ __('Company') }}</th>
                            <th class="bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 px-5 py-4 text-left text-[0.786rem] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead">{{ __('Role') }}</th>
                            <th class="bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 px-5 py-4 text-left text-[0.786rem] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead">{{ __('Branches') }}</th>
                            <th class="bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 px-5 py-4 text-left text-[0.786rem] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead">{{ __('Status') }}</th>
                            <th class="bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 px-5 py-4 text-left text-[0.786rem] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($assignments as $assignment)
                            <tr>
                                <td class="px-5 py-[18px] align-middle">
                                    <a href="{{ route('superadmin.users.show', $assignment->user) }}" class="font-bold text-gray-900">{{ $assignment->user->name }}</a>
                                    <span class="mt-1 block text-[0.893rem] text-gray-400">{{ $assignment->user->email }}</span>
                                </td>
                                <td class="px-5 py-[18px] align-middle">
                                    <a href="{{ route('superadmin.companies.show', $assignment->company) }}" class="font-bold text-gray-800">{{ $assignment->company->name }}</a>
                                </td>
                                <td class="px-5 py-[18px] align-middle text-gray-600">{{ $assignment->role }}</td>
                                <td class="px-5 py-[18px] align-middle text-gray-600">
                                    @if(count($assignment->branch_ids ?? []))
                                        {{ count($assignment->branch_ids ?? []) }} {{ __('branches') }}
                                    @else
                                        {{ __('All branches') }}
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] align-middle">
                                    @if($assignment->is_active)
                                        <span class="inline-flex items-center gap-[7px] rounded-full border border-green-600/30 bg-gradient-to-b from-mint-100 to-mint-200 px-3 py-1.5 text-xs font-bold text-green-700 shadow-badge">
                                            <span class="h-[7px] w-[7px] rounded-full bg-green-500 shadow-[0_0_0_3px_rgba(34,197,94,.18)]"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="sa-pill sa-pill--muted">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] align-middle">
                                    <a href="{{ route('superadmin.assignments.edit', $assignment) }}" class="inline-flex items-center gap-1.5 rounded-[10px] border border-gold-600/35 bg-gradient-to-b from-[#F4FBFB] to-[#DFF7F6] px-4 py-2 text-[0.929rem] font-bold text-gold-700 shadow-edit transition hover:-translate-y-px hover:border-gold-600/55 hover:text-gold-800 hover:shadow-edit-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        {{ __('Edit') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-[18px] align-middle text-center text-gray-400">{{ __('No assignments yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div class="mt-4">{{ $assignments->links() }}</div>
            @endif
        </div>
    </x-superadmin.layout>

</x-app-layout>
