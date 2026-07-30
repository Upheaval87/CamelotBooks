<x-app-layout>
    <x-slot name="header">{{ __('Create Pension Scheme') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6 text-gray-900">
                        <form action="{{ route('accounting.pension-schemes.store') }}" method="POST">
                            @csrf

                            <x-form.section number="01" :title="__('Scheme Details')" />

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <div>
                                    <x-input-label for="name" value="Name" />
                                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                        class="input mt-1 @error('name') border-red-500 @enderror" />
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <x-input-label for="registration_number" value="Registration Number" />
                                    <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number') }}"
                                        class="input mt-1 @error('registration_number') border-red-500 @enderror" />
                                    @error('registration_number')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <x-input-label for="employee_rate" value="Employee Rate (%)" />
                                    <div class="mt-1 relative">
                                        <input type="number" name="employee_rate" id="employee_rate" value="{{ old('employee_rate') }}" required step="0.01" min="0" max="100"
                                            class="input w-full pr-12 @error('employee_rate') border-red-500 @enderror" />
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">%</span>
                                        </div>
                                    </div>
                                    @error('employee_rate')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <x-input-label for="employer_rate" value="Employer Rate (%)" />
                                    <div class="mt-1 relative">
                                        <input type="number" name="employer_rate" id="employer_rate" value="{{ old('employer_rate') }}" required step="0.01" min="0" max="100"
                                            class="input w-full pr-12 @error('employer_rate') border-red-500 @enderror" />
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">%</span>
                                        </div>
                                    </div>
                                    @error('employer_rate')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <x-input-label for="max_contributory_salary" value="Max Contributory Salary" />
                                    <input type="number" name="max_contributory_salary" id="max_contributory_salary" value="{{ old('max_contributory_salary') }}" step="0.01" min="0"
                                        class="input mt-1 @error('max_contributory_salary') border-red-500 @enderror" />
                                    @error('max_contributory_salary')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <x-input-label for="effective_from" value="Effective From" />
                                    <input type="date" name="effective_from" id="effective_from" value="{{ old('effective_from') }}" required
                                        class="input mt-1 @error('effective_from') border-red-500 @enderror" />
                                    @error('effective_from')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>

                            <div class="flex items-center justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.pension-schemes.index') }}">{{ __('Cancel') }}</x-button>
                                <x-button variant="primary" type="submit">{{ __('Create Scheme') }}</x-button>
                            </div>
                        </form>
                    </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Employee'), 'route' => route('accounting.employees.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z\"/></svg>'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Pension Schemes List'), 'route' => route('accounting.pension-schemes.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z\"/></svg>'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
