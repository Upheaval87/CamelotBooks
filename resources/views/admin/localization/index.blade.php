<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Localization & Regional Settings</h2>
    </x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Localization & Regional Settings</h1>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.localization.update') }}">
            @csrf
            @method('PUT')

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date Format</label>
                        <select name="date_format" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            @foreach($dateFormats as $value => $label)
                                <option value="{{ $value }}" {{ ($settings['date_format'] ?? 'Y-m-d') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Number Format</label>
                        <select name="number_format" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            @foreach($numberFormats as $value => $label)
                                <option value="{{ $value }}" {{ ($settings['number_format'] ?? '1,234.56') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Timezone</label>
                        <select name="timezone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            @foreach($timezones as $value => $label)
                                <option value="{{ $value }}" {{ ($settings['timezone'] ?? 'UTC') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Currency Display</label>
                        <select name="currency_display" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="symbol" {{ ($settings['currency_display'] ?? 'symbol') === 'symbol' ? 'selected' : '' }}>Symbol ($1,234.56)</option>
                            <option value="code" {{ ($settings['currency_display'] ?? '') === 'code' ? 'selected' : '' }}>Code (USD 1,234.56)</option>
                            <option value="none" {{ ($settings['currency_display'] ?? '') === 'none' ? 'selected' : '' }}>None (1,234.56)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition ease-in-out duration-150">
                        Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
