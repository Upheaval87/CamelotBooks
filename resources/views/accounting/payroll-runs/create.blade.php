<x-app-layout>
    <x-list-header title="{{ __('Create Payroll Run') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            

            

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
                        ['title' => __('New Employee'), 'route' => route('accounting.employees.create'), 'icon' => 'user-plus'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Payroll Runs List'), 'route' => route('accounting.payroll-runs.index'), 'icon' => 'table-list'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
