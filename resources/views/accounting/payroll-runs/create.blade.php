<x-app-layout>
    <x-slot name="header">{{ __('Create Payroll Run') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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

            <div class="form-page">
                <div class="form-page-main">
                    <form method="POST" action="{{ route('accounting.payroll-runs.store') }}">
                        @csrf

                        <div class="card p-6">
                            <x-form.section number="01" :title="__('Payroll Run Details')" />

                            <div class="grid grid-cols-2 gap-6">
                                <div class="col-span-2">
                                    <x-input-label for="period_label" value="{{ __('Period Label') }}" />
                                    <x-text-input id="period_label" name="period_label" type="text" class="mt-1 block w-full" :value="old('period_label')" placeholder="e.g. July 2026" required />
                                    <x-input-error :messages="$errors->get('period_label')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="pay_date" value="{{ __('Pay Date') }}" />
                                    <x-text-input id="pay_date" name="pay_date" type="date" class="mt-1 block w-full" :value="old('pay_date', now()->format('Y-m-d'))" required />
                                    <x-input-error :messages="$errors->get('pay_date')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="period_start" value="{{ __('Period Start') }}" />
                                    <x-text-input id="period_start" name="period_start" type="date" class="mt-1 block w-full" :value="old('period_start')" required />
                                    <x-input-error :messages="$errors->get('period_start')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="period_end" value="{{ __('Period End') }}" />
                                    <x-text-input id="period_end" name="period_end" type="date" class="mt-1 block w-full" :value="old('period_end')" required />
                                    <x-input-error :messages="$errors->get('period_end')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="employee_count" value="{{ __('Employee Count') }}" />
                                    <div class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 text-sm text-gray-700">
                                        {{ $employeeCount ?? 0 }} {{ __('active employees') }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.payroll-runs.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button type="submit">{{ __('Create Run') }}</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Employee'), 'route' => route('accounting.employees.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z\"/></svg>'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Payroll Runs List'), 'route' => route('accounting.payroll-runs.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z\"/></svg>'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
