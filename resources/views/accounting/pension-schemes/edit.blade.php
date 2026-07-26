<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Pension Scheme') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('accounting.pension-schemes.update', $scheme) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-600">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name', $scheme->name) }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('name') border-red-500 @enderror" />
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="registration_number" class="block text-sm font-medium text-gray-700">Registration Number</label>
                                <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number', $scheme->registration_number) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('registration_number') border-red-500 @enderror" />
                                @error('registration_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="employee_rate" class="block text-sm font-medium text-gray-700">Employee Rate (%) <span class="text-red-600">*</span></label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input type="number" name="employee_rate" id="employee_rate" value="{{ old('employee_rate', $scheme->employee_rate) }}" required step="0.01" min="0" max="100"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pr-12 @error('employee_rate') border-red-500 @enderror" />
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
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input type="number" name="employer_rate" id="employer_rate" value="{{ old('employer_rate', $scheme->employer_rate) }}" required step="0.01" min="0" max="100"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pr-12 @error('employer_rate') border-red-500 @enderror" />
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
                                <input type="number" name="max_contributory_salary" id="max_contributory_salary" value="{{ old('max_contributory_salary', $scheme->max_contributory_salary) }}" step="0.01" min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('max_contributory_salary') border-red-500 @enderror" />
                                @error('max_contributory_salary')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="effective_from" class="block text-sm font-medium text-gray-700">Effective From <span class="text-red-600">*</span></label>
                                <input type="date" name="effective_from" id="effective_from" value="{{ old('effective_from', $scheme->effective_from ? \Carbon\Carbon::parse($scheme->effective_from)->format('Y-m-d') : '') }}" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('effective_from') border-red-500 @enderror" />
                                @error('effective_from')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>

                        <div class="flex items-center justify-end mt-6 space-x-3">
                            <a href="{{ route('accounting.pension-schemes.show', $scheme) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Update Scheme') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
