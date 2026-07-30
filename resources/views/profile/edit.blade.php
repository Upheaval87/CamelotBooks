<x-app-layout>
    <x-slot name="header">{{ __('Profile') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6 mb-6">
                        @include('profile.partials.update-profile-information-form')
                    </div>

                    <div class="card p-6 mb-6">
                        @include('profile.partials.update-password-form')
                    </div>

                    <div class="card p-6">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
