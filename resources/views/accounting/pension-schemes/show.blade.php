<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pension Scheme Detail') }}</h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('accounting.pension-schemes.edit', $scheme) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Edit') }}
                </a>
                <form action="{{ route('accounting.pension-schemes.toggle', $scheme) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 {{ $scheme->is_current ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 {{ $scheme->is_current ? 'focus:ring-red-500' : 'focus:ring-green-500' }}">
                        {{ $scheme->is_current ? __('Deactivate') : __('Activate') }}
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $scheme->name }}</h3>
                        @if ($scheme->is_current)
                            <span class="ml-3 px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Current</span>
                        @else
                            <span class="ml-3 px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Expired</span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $scheme->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Registration Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $scheme->registration_number ?? '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Employee Rate</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $scheme->employee_rate }}%</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Employer Rate</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $scheme->employer_rate }}%</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Max Contributory Salary</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $scheme->max_contributory_salary ? number_format($scheme->max_contributory_salary, 2) : '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Effective From</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($scheme->effective_from)->format('d M Y') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $scheme->is_current ? 'Current' : 'Expired' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $scheme->created_at ? $scheme->created_at->format('d M Y H:i') : '—' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Updated At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $scheme->updated_at ? $scheme->updated_at->format('d M Y H:i') : '—' }}</dd>
                        </div>
                    </div>

                    <div class="mt-8">
                        <a href="{{ route('accounting.pension-schemes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Back to List') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
