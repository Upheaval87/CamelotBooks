<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create Payroll Run') }}
            </h2>
            <a href="{{ route('accounting.payroll-runs.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Payroll Runs') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
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

            <form method="POST" action="{{ route('accounting.payroll-runs.store') }}">
                @csrf

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payroll Run Details') }}</h3>
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
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.payroll-runs.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Cancel') }}
                    </a>
                    <x-primary-button type="submit">{{ __('Create Run') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
