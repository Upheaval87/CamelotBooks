<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">System Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            {{-- Tab Navigation --}}
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex space-x-8 overflow-x-auto" aria-label="Settings Tabs">
                    <a href="{{ route('system-settings.index', 'company') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Company Profile
                    </a>
                    <a href="{{ route('system-settings.index', 'regional') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Regional Settings
                    </a>
                    <a href="{{ route('system-settings.index', 'currency') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Currency Settings
                    </a>
                    <a href="{{ route('system-settings.index', 'accounts') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Account Mappings
                    </a>
                    <a href="{{ route('system-settings.index', 'accounting') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Accounting Settings
                    </a>
                    <a href="{{ route('system-settings.index', 'approval') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Approval Settings
                    </a>
                    <a href="{{ route('system-settings.index', 'notifications') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Email
                    </a>
                    <a href="{{ route('system-settings.index', 'data-hub') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Data Hub
                    </a>
                    <a href="{{ route('system-settings.index', 'import-export') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Import/Export
                    </a>
                    <a href="{{ route('system-settings.index', 'backups') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Backups
                    </a>
                    <a href="{{ route('system-settings.audit-log') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">
                        Audit Log
                    </a>
                </nav>
            </div>

            @include('system-settings._audit-log-tab')
        </div>
    </div>
</x-app-layout>
