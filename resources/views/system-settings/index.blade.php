<x-app-layout>
    <x-list-header title="{{ __('System Settings') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 ss-suite">

            @if($errors->any())
                <x-feedback.alert variant="error" class="mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-feedback.alert>
            @endif

            @if($tab === 'regional')
                @include('system-settings._tab-regional')
            @elseif($tab === 'currency')
                @include('system-settings._tab-currency')
            @elseif($tab === 'accounts')
                @include('system-settings._tab-accounts')
            @elseif($tab === 'accounting')
                @include('system-settings._tab-accounting')
            @elseif($tab === 'approval')
                @include('system-settings._tab-approval')
            @elseif($tab === 'notifications')
                @include('system-settings._tab-notifications')
            @elseif($tab === 'data-hub')
                @include('system-settings._tab-data-hub')
            @elseif($tab === 'import-export')
                @include('system-settings._tab-import-export')
            @else
                @include('system-settings._tab-company')
            @endif
        </div>
    </div>
</x-app-layout>
