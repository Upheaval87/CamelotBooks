<x-app-layout>
    <x-slot name="header">{{ __('Create Pension Scheme') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6 text-gray-900">
                <div class="form-section-label">1 · Scheme Details</div>
                <form action="{{ route('accounting.pension-schemes.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-600">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="input mt-1 @error('name') border-red-500 @enderror" />
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="registration_number" class="block text-sm font-medium text-gray-700">Registration Number</label>
                            <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number') }}"
                                class="input mt-1 @error('registration_number') border-red-500 @enderror" />
                            @error('registration_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="employee_rate" class="block text-sm font-medium text-gray-700">Employee Rate (%) <span class="text-red-600">*</span></label>
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
                            <label for="employer_rate" class="block text-sm font-medium text-gray-700">Employer Rate (%) <span class="text-red-600">*</span></label>
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
                            <label for="max_contributory_salary" class="block text-sm font-medium text-gray-700">Max Contributory Salary</label>
                            <input type="number" name="max_contributory_salary" id="max_contributory_salary" value="{{ old('max_contributory_salary') }}" step="0.01" min="0"
                                class="input mt-1 @error('max_contributory_salary') border-red-500 @enderror" />
                            @error('max_contributory_salary')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="effective_from" class="block text-sm font-medium text-gray-700">Effective From <span class="text-red-600">*</span></label>
                            <input type="date" name="effective_from" id="effective_from" value="{{ old('effective_from') }}" required
                                class="input mt-1 @error('effective_from') border-red-500 @enderror" />
                            @error('effective_from')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <div class="flex items-center justify-end mt-6 space-x-3">
                        <x-button variant="ghost" href="{{ route('accounting.pension-schemes.index') }}">{{ __('Cancel') }}</x-button>
                        <x-button variant="primary" type="submit">{{ __('Create Scheme') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
