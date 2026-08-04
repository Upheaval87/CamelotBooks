<x-app-layout>
    <x-list-header title="{{ __('Pension Scheme Detail') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.pension-schemes.edit', $scheme) }}" class="tr-save">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    {{ __('Edit') }}
                </a>
                <form action="{{ route('accounting.pension-schemes.toggle', $scheme) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="tr-archive">{{ $scheme->is_current ? __('Deactivate') : __('Activate') }}</button>
                </form>
                <a href="{{ route('accounting.pension-schemes.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back to List') }}
                </a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="flex items-center mb-5">
                            <p class="text-base font-semibold text-ink">{{ $scheme->name }}</p>
                            @if ($scheme->is_current)
                                <span class="ml-3 status-pill positive">{{ __('Current') }}</span>
                            @else
                                <span class="ml-3 status-pill negative">{{ __('Expired') }}</span>
                            @endif
                        </div>

                        <div class="detail-grid">
                            <x-detail-field label="{{ __('Name') }}" strong>{{ $scheme->name }}</x-detail-field>
                            <x-detail-field label="{{ __('Registration Number') }}">{{ $scheme->registration_number ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Employee Rate') }}">{{ $scheme->employee_rate }}%</x-detail-field>
                            <x-detail-field label="{{ __('Employer Rate') }}">{{ $scheme->employer_rate }}%</x-detail-field>
                            <x-detail-field label="{{ __('Max Contributory Salary') }}">{{ $scheme->max_contributory_salary ? format_money($scheme->max_contributory_salary) : '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Effective From') }}">{{ \Carbon\Carbon::parse($scheme->effective_from)->format('d M Y') }}</x-detail-field>
                            <x-detail-field label="{{ __('Status') }}" noBorder>{{ $scheme->is_current ? __('Current') : __('Expired') }}</x-detail-field>
                            <x-detail-field label="{{ __('Created At') }}">{{ $scheme->created_at ? $scheme->created_at->format('d M Y H:i') : '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Updated At') }}">{{ $scheme->updated_at ? $scheme->updated_at->format('d M Y H:i') : '—' }}</x-detail-field>
                        </div>
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.pension-schemes.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
