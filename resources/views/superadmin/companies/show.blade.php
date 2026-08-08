<x-app-layout>

    <x-superadmin.layout>
        <div class="flex flex-col gap-5" style="background: radial-gradient(1200px 600px at 90% -10%, rgba(100,116,139,.10), transparent 60%), radial-gradient(800px 500px at -5% 110%, rgba(148,163,184,.07), transparent 55%), #eef1f6;">

            {{-- Overview --}}
            <x-superadmin.card>
                <div class="flex flex-wrap items-start justify-between gap-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-[26px] font-extrabold tracking-[-0.02em] text-gray-900">{{ $company->name }}</span>
                        @if(!$company->is_active)
                            <x-superadmin.badge variant="danger">{{ __('Suspended') }}</x-superadmin.badge>
                        @endif
                        @if($company->provisioning_status === 'active')
                            <x-superadmin.badge variant="active">{{ __('Active') }}</x-superadmin.badge>
                        @else
                            @php
                                $badge = match ($company->provisioning_status) {
                                    'pending', 'provisioning' => 'warning',
                                    'failed' => 'danger',
                                    default => 'muted',
                                };
                            @endphp
                            <x-superadmin.badge :variant="$badge">{{ ucfirst($company->provisioning_status) }}</x-superadmin.badge>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-superadmin.btn variant="edit" href="{{ route('superadmin.companies.edit', $company) }}">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            {{ __('Edit Details') }}
                        </x-superadmin.btn>
                        <x-superadmin.btn variant="ghost" size="md" href="{{ route('superadmin.companies.modules', $company) }}">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ __('Manage Modules') }}
                        </x-superadmin.btn>
                        @if($company->is_active && $company->db_name)
                            <form method="POST" action="{{ route('companies.select', $company->id) }}">
                                @csrf
                                <x-superadmin.btn type="submit" title="{{ __('Enter this company as support access. Every entry is logged with a start/end time.') }}">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ __('Enter Company (Support)') }}
                                </x-superadmin.btn>
                            </form>
                        @endif
                        @if($company->is_active)
                            <form method="POST" action="{{ route('superadmin.companies.suspend', $company) }}" onsubmit="return fbConfirmSubmit(event, '{{ __('Suspend this company? Users will lose access immediately.') }}', { type: 'danger' })">
                                @csrf
                                <x-superadmin.btn variant="danger" size="md" type="submit">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ __('Suspend') }}
                                </x-superadmin.btn>
                            </form>
                        @else
                            <form method="POST" action="{{ route('superadmin.companies.reactivate', $company) }}">
                                @csrf
                                <x-superadmin.btn type="submit">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    {{ __('Reactivate') }}
                                </x-superadmin.btn>
                            </form>
                        @endif
                    </div>
                </div>

                @if($company->last_provisioning_error)
                    <div class="mt-4 rounded-lg border border-brick bg-brick-soft px-4 py-3 text-sm text-brick">
                        <p class="font-semibold">{{ __('Last provisioning error') }}</p>
                        <p class="mt-1 font-mono text-xs break-all">{{ $company->last_provisioning_error }}</p>
                    </div>
                @endif

                <div class="mt-[22px] grid grid-cols-1 gap-x-8 gap-y-[22px] border-t border-line pt-[22px] md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Company Code') }}</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $company->company_code ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Legal Name') }}</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $company->legal_name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Base Currency') }}</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $company->base_currency }}</div>
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Tax ID') }}</div>
                        <div class="font-mono text-[13px] font-medium text-gray-900">{{ $company->tax_id ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Fiscal Year Start') }}</div>
                        <div class="text-sm font-semibold text-gray-900">{{ \Carbon\Carbon::create()->month($company->fiscal_year_start_month)->format('F') }}</div>
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Email') }}</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $company->email ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Phone') }}</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $company->phone ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Address') }}</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $company->address ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('City') }}</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $company->city ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Country') }}</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $company->country ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Database Name') }}</div>
                        @if($company->db_name)
                            <code class="font-mono text-[13px] font-medium text-gray-900">{{ $company->db_name }}</code>
                        @else
                            <div class="font-mono text-[13px] font-medium text-gray-900">—</div>
                        @endif
                    </div>
                    <div>
                        <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Provisioned At') }}</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $company->provisioned_at?->format('M j, Y g:i A') ?? '—' }}</div>
                    </div>
                </div>
            </x-superadmin.card>

            {{-- Branch Limit --}}
            <x-superadmin.card title="{{ __('Branch Limit') }}">
                <x-slot name="action">
                    <div class="flex items-center gap-2">
                        @if($branchUsage['branch_limit'] === null)
                            <span class="inline-flex items-center rounded-full border border-navy-700/[.22] bg-navy-700/[.08] px-3 py-1 text-[11px] font-bold uppercase tracking-[0.06em] text-navy-700">{{ __('Unlimited') }}</span>
                        @elseif($branchUsage['branch_count'] > $branchUsage['branch_limit'])
                            <span class="inline-flex items-center rounded-full border border-red-300 bg-red-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.06em] text-red-700">{{ __('Over limit') }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full border border-shell bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-[0.06em] text-gray-500">{{ $branchUsage['branch_count'] }} {{ __('of') }} {{ $branchUsage['branch_limit'] }} {{ __('used') }}</span>
                        @endif
                    </div>
                </x-slot>
                <p class="max-w-[880px] text-[13.5px] leading-relaxed text-gray-500">
                    {{ __('The company can create up to its limit of active branches; creation beyond it is blocked (the Company Manager keeps the ability, the system enforces the count).') }}
                    @if($branchUsage['branch_limit'] === null)
                        {{ __('No limit is currently enforced.') }}
                    @endif
                </p>

                <form method="POST" action="{{ route('superadmin.companies.branch-limit', $company) }}"
                      class="mt-[18px] flex flex-wrap items-center gap-[18px]"
                      x-data="{ unlimited: {{ $branchUsage['branch_limit'] === null ? 'true' : 'false' }} }">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="branch_limit" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.08em] text-gray-500">{{ __('Limit') }}</label>
                        <input id="branch_limit" name="branch_limit" type="number" min="0"
                               value="{{ $branchUsage['branch_limit'] }}" x-bind:disabled="unlimited"
                               class="w-[130px] rounded-[10px] border border-shell bg-[rgba(244,246,250,.6)] px-3 py-2 text-sm text-gray-400 focus:border-gold-500 focus:outline-none focus:ring-0 disabled:cursor-not-allowed disabled:opacity-50" />
                        <x-input-error :messages="$errors->get('branch_limit')" class="mt-1" />
                    </div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" x-model="unlimited" class="h-4 w-4 rounded border-shell accent-gold-600" />
                        {{ __('Unlimited') }}
                    </label>
                    <x-superadmin.btn type="submit">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('Save Limit') }}
                    </x-superadmin.btn>
                </form>
                <p class="mt-3.5 text-xs text-gray-400">
                    {{ __('Raising a limit beyond the initial value normally goes through the branch request/billing process; this manual override is for exceptions (trials, corrections, negotiated deals).') }}
                </p>
            </x-superadmin.card>

            {{-- Assigned Users + Active Modules --}}
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-superadmin.card title="{{ __('Assigned Users') }}">
                    <x-slot name="action">
                        <a href="{{ route('superadmin.assignments.create') }}" class="text-[13px] font-semibold text-gold-700 hover:text-gold-800 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">{{ __('Assign a user') }}</a>
                    </x-slot>
                    <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                        <table class="w-full min-w-[560px] border-collapse text-sm">
                            <thead>
                                <tr>
                                    <x-superadmin.th>{{ __('User') }}</x-superadmin.th>
                                    <x-superadmin.th>{{ __('Role') }}</x-superadmin.th>
                                    <x-superadmin.th align="right">{{ __('Branches') }}</x-superadmin.th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @forelse($assignments as $assignment)
                                    <tr>
                                        <td class="px-5 py-3.5 align-middle">
                                            <span class="font-bold text-gray-900">{{ $assignment->user->name }}</span>
                                            <span class="mt-[3px] block text-xs text-gray-400">{{ $assignment->user->email }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 align-middle text-gray-600">{{ $assignment->role }}</td>
                                        <td class="px-5 py-3.5 text-right align-middle tabular-nums text-gray-700">{{ count($assignment->branch_ids ?? []) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-5 py-3.5 text-center align-middle text-gray-400">{{ __('No users assigned.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-superadmin.card>

                <x-superadmin.card title="{{ __('Active Modules') }}">
                    <x-slot name="action">
                        <a href="{{ route('superadmin.companies.modules', $company) }}" class="text-[13px] font-semibold text-gold-700 hover:text-gold-800 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">{{ __('Manage') }}</a>
                    </x-slot>
                    <div class="flex flex-wrap gap-2">
                        @forelse($modules->filter(fn ($m) => ($moduleStates[$m->id]?->is_active ?? false) || $m->is_core) as $module)
                            <x-superadmin.badge variant="accent">{{ $module->name }}</x-superadmin.badge>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('No modules enabled.') }}</p>
                        @endforelse
                    </div>
                </x-superadmin.card>
            </div>

            {{-- Support Access Sessions --}}
            <x-superadmin.card title="{{ __('Support Access Sessions') }}">
                <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                    <table class="w-full min-w-[560px] border-collapse text-sm">
                        <thead>
                            <tr>
                                <x-superadmin.th>{{ __('Admin') }}</x-superadmin.th>
                                <x-superadmin.th>{{ __('Started') }}</x-superadmin.th>
                                <x-superadmin.th>{{ __('Ended') }}</x-superadmin.th>
                                <x-superadmin.th>{{ __('Duration') }}</x-superadmin.th>
                                <x-superadmin.th>{{ __('End Reason') }}</x-superadmin.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($supportSessions as $session)
                                <tr>
                                    <td class="px-5 py-3.5 align-middle">
                                        <span class="font-bold text-gray-900">{{ $session->user?->name ?? '—' }}</span>
                                        <span class="mt-[3px] block text-xs text-gray-400">{{ $session->user?->email }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 align-middle text-gray-500">{{ $session->started_at->format('M j, Y g:i A') }}</td>
                                    <td class="px-5 py-3.5 align-middle text-gray-500">{{ $session->ended_at?->format('M j, Y g:i A') ?? __('Ongoing') }}</td>
                                    <td class="px-5 py-3.5 align-middle tabular-nums text-gray-700">{{ $session->duration }}</td>
                                    <td class="px-5 py-3.5 align-middle">
                                        @if($session->ended_reason)
                                            <code class="rounded-md border border-slate-200 bg-slate-100 px-2 py-[3px] font-mono text-xs text-slate-600">{{ $session->ended_reason }}</code>
                                        @else
                                            <x-superadmin.badge variant="warning">{{ __('Ongoing') }}</x-superadmin.badge>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-3.5 text-center align-middle text-gray-400">{{ __('No support access recorded.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-superadmin.card>

            {{-- Recent Activity --}}
            <x-superadmin.card title="{{ __('Recent Activity') }}">
                <ul class="divide-y divide-line">
                    @forelse($audit as $log)
                        @php
                            $dotClass = 'bg-[#c2cad4] shadow-[0_0_0_4px_rgba(194,202,212,.16)]';
                            if (str_contains($log->action, 'provision')) {
                                $dotClass = 'bg-[#354a63] shadow-[0_0_0_4px_rgba(53,74,99,.12)]';
                            } elseif (str_contains($log->action, 'branch_limit') || str_contains($log->action, 'branch_payment') || str_contains($log->action, 'branch_request')) {
                                $dotClass = 'bg-[#c9a353] shadow-[0_0_0_4px_rgba(201,163,83,.14)]';
                            } elseif (str_contains($log->action, 'support')) {
                                $dotClass = 'bg-[#34d399] shadow-[0_0_0_4px_rgba(52,211,153,.14)]';
                            }
                        @endphp
                        <li class="flex items-start gap-3 py-3.5">
                            <span class="mt-[5px] h-[7px] w-[7px] shrink-0 rounded-full {{ $dotClass }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[13.5px] leading-relaxed text-gray-700">
                                    <strong class="font-bold text-gray-900">{{ $log->user?->name ?? 'System' }}</strong>
                                    <code class="mx-1 rounded border border-slate-200 bg-slate-100 px-1.5 py-[1px] font-mono text-[10.5px] uppercase tracking-wide text-slate-500">{{ $log->action }}</code>
                                    <span class="text-gray-600">{{ $log->description }}</span>
                                </p>
                            </div>
                            <span class="ml-auto shrink-0 whitespace-nowrap pt-0.5 text-xs text-gray-400">{{ $log->created_at->format('M j, Y g:i A') }}</span>
                        </li>
                    @empty
                        <li class="py-3.5 text-sm text-gray-500">{{ __('No activity yet.') }}</li>
                    @endforelse
                </ul>
            </x-superadmin.card>

        </div>
    </x-superadmin.layout>
</x-app-layout>
