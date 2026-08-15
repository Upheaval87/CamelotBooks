<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Audit Log') }}" description="{{ __('Central platform activity, independent of tenant audit logs.') }}" />

        <x-superadmin.card>
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <form method="GET" action="{{ route('superadmin.audit.index') }}" class="flex flex-wrap items-center gap-3">
                    <select name="company" class="rounded-[10px] border border-shell bg-[rgba(244,246,250,.6)] px-3 py-2 text-[0.929rem] text-gray-700 focus:border-gold-500 focus:outline-none">
                        <option value="">{{ __('All companies') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected(request('company') == $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <select name="action" class="rounded-[10px] border border-shell bg-[rgba(244,246,250,.6)] px-3 py-2 text-[0.929rem] text-gray-700 focus:border-gold-500 focus:outline-none">
                        <option value="">{{ __('All actions') }}</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') == $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                    <x-superadmin.btn variant="ghost" size="md" type="submit">{{ __('Filter') }}</x-superadmin.btn>
                </form>
            </div>

            <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                <table class="w-full min-w-[960px] border-collapse text-sm">
                    <thead>
                        <tr>
                            <x-superadmin.th>{{ __('When') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Actor') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Company') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Action') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Description') }}</x-superadmin.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($logs as $log)
                            <tr>
                                <td class="px-5 py-[18px] align-middle text-gray-500">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                <td class="px-5 py-[18px] align-middle font-medium text-gray-900">{{ $log->user?->name ?? '—' }}</td>
                                <td class="px-5 py-[18px] align-middle text-gray-600">{{ $log->company?->name ?? '—' }}</td>
                                <td class="px-5 py-[18px] align-middle">
                                    <code class="rounded-md border border-slate-200 bg-slate-100 px-2 py-[3px] font-mono text-xs text-slate-600">{{ $log->action }}</code>
                                </td>
                                <td class="px-5 py-[18px] align-middle text-gray-500">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-[18px] text-center align-middle text-gray-400">{{ __('No activity yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="mt-4">{{ $logs->links() }}</div>
            @endif
        </x-superadmin.card>
    </x-superadmin.layout>

</x-app-layout>
