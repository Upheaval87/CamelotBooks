<x-app-layout>
    <x-list-header title="{{ __('System Settings') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 ss-suite">

            <div class="sticky-head">
                @include('system-settings._tabnav', ['active' => 'features'])
                <div>
                    <div class="glabel">{{ __('Actions') }}</div>
                    <div class="tbtns">
                        <span class="chip-t">{{ __('Controlled by your system administrator') }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9 9 0 1020.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg></span>
                        <h2>{{ __('Feature Management') }}</h2>
                        <div class="rule"></div>
                    </div>
                    <p class="sub">Module activation is controlled by your system administrator.</p>

                    <x-settings.callout variant="info" class="mb-4">
                        Feature activation is managed centrally from the Super Admin panel. Disabled features are hidden from
                        navigation and inaccessible to users. To request a change, contact your system administrator.
                    </x-settings.callout>

                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ __('Feature') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($features as $key => $label)
                                    @php $isOn = array_key_exists($key, $enabled); @endphp
                                    <tr>
                                        <td>
                                            <div>{{ $label }}</div>
                                            <div class="em">{{ $key }}</div>
                                        </td>
                                        <td>
                                            @if($isOn)
                                                <span class="badge b-act"><span class="bdot"></span>{{ __('Enabled') }}</span>
                                            @else
                                                <span class="badge b-gray"><span class="bdot"></span>{{ __('Disabled') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
