<x-app-layout>
    <x-slot name="header">{{ __('Pension Scheme Detail') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="primary" href="{{ route('accounting.pension-schemes.edit', $scheme) }}">{{ __('Edit') }}</x-button>
        <form action="{{ route('accounting.pension-schemes.toggle', $scheme) }}" method="POST" class="inline">
            @csrf
            <x-button variant="{{ $scheme->is_current ? 'ghost' : 'primary' }}" type="submit">{{ $scheme->is_current ? __('Deactivate') : __('Activate') }}</x-button>
        </form>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6 text-gray-900">
                <div class="flex items-center mb-6">
                    <div class="form-section-label">1 · {{ $scheme->name }}</div>
                    @if ($scheme->is_current)
                        <span class="ml-3 status-pill positive">Current</span>
                    @else
                        <span class="ml-3 status-pill negative">Expired</span>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-ink-soft">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $scheme->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-ink-soft">Registration Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $scheme->registration_number ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-ink-soft">Employee Rate</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $scheme->employee_rate }}%</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-ink-soft">Employer Rate</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $scheme->employer_rate }}%</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-ink-soft">Max Contributory Salary</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $scheme->max_contributory_salary ? format_money($scheme->max_contributory_salary) : '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-ink-soft">Effective From</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($scheme->effective_from)->format('d M Y') }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-ink-soft">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $scheme->is_current ? 'Current' : 'Expired' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-ink-soft">Created At</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $scheme->created_at ? $scheme->created_at->format('d M Y H:i') : '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-ink-soft">Updated At</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $scheme->updated_at ? $scheme->updated_at->format('d M Y H:i') : '—' }}</dd>
                    </div>
                </div>

                <div class="mt-8">
                    <x-button variant="ghost" href="{{ route('accounting.pension-schemes.index') }}">{{ __('Back to List') }}</x-button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
