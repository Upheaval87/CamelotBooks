<x-app-layout>
    <x-list-header title="System Settings" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            

            

            @if($errors->any())
                <x-feedback.alert variant="error" class="mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-feedback.alert>
            @endif

            <div class="settings-layout">
                {{-- Sidebar --}}
                <div class="settings-layout-sidebar">
                    <x-settings.sidebar :activeTab="$tab" :groups="[['company','regional','currency','accounts','accounting','approval'],['notifications','data-hub','import-export','backups'],['audit-log']]" />
                </div>

                {{-- Content --}}
                <div class="settings-layout-content">
                    @if($tab === 'company')
                    @include('system-settings._tab-company')

                    @elseif($tab === 'regional')
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
