<x-app-layout>
    <x-slot name="header">{{ __('System Health') }}</x-slot>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">System Health</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            @foreach($checks as $key => $check)
            <div class="bg-white overflow-hidden shadow sm:rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full
                            @if(($check['status'] ?? 'ok') === 'ok') bg-green-100 text-green-600
                            @elseif(($check['status'] ?? '') === 'warning') bg-yellow-100 text-yellow-600
                            @else bg-red-100 text-red-600 @endif">
                            @if(($check['status'] ?? 'ok') === 'ok')
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @elseif(($check['status'] ?? '') === 'warning')
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            @else
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            @endif
                        </span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $check['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}</p>
                            <p class="text-xs text-gray-500">{{ $check['message'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if(!empty($recentErrors))
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Errors</h2>
            <div class="space-y-3">
                @foreach($recentErrors as $error)
                <div class="p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-xs text-gray-500">{{ $error['time'] }}</p>
                    <p class="text-sm text-red-800 mt-1">{{ $error['message'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
</x-app-layout>
