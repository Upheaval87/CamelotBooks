<x-app-layout>
    <x-slot name="header">{{ __('Audit Log') }}</x-slot>

    @include('superadmin._nav', ['active' => 'audit'])

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <div class="mb-4">
                    <p class="text-sm text-gray-500">
                        {{ __('Central platform audit trail for super admin actions. This is stored separately from each company\'s own audit log.') }}
                    </p>
                </div>

                <form method="GET" action="{{ route('superadmin.audit.index') }}" class="grid gap-4 sm:grid-cols-3 mb-6">
                    <div>
                        <x-input-label for="company_id">{{ __('Company') }}</x-input-label>
                        <select id="company_id" name="company_id" class="input mt-1 block w-full">
                            <option value="">— {{ __('All companies') }} —</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected((int) request('company_id') === $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="action">{{ __('Action') }}</x-input-label>
                        <select id="action" name="action" class="input mt-1 block w-full">
                            <option value="">— {{ __('All actions') }} —</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <x-button variant="ghost" type="submit">{{ __('Filter') }}</x-button>
                        @if(request()->has('company_id') || request()->has('action'))
                            <a href="{{ route('superadmin.audit.index') }}" class="btn-ghost ml-2">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>

                <div class="list-table-wrap">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>{{ __('When') }}</th>
                                <th>{{ __('Actor') }}</th>
                                <th>{{ __('Company') }}</th>
                                <th>{{ __('Action') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('IP Address') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td class="text-gray-500">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                    <td>{{ $log->user?->name ?? '—' }}</td>
                                    <td>{{ $log->company?->name ?? '—' }}</td>
                                    <td><code class="font-sans text-xs text-ink">{{ $log->action }}</code></td>
                                    <td class="text-gray-600">
                                        {{ $log->description }}
                                        @if($log->after)
                                            <span class="block mt-1 font-mono text-xs text-gray-500">{{ json_encode($log->after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</span>
                                        @endif
                                    </td>
                                    <td class="font-mono text-xs text-gray-500">{{ $log->ip_address ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-gray-500">{{ __('No log entries found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
