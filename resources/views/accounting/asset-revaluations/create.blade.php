<x-app-layout>
    <x-slot name="header">{{ __('New Asset Revaluation') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <form method="POST" action="{{ route('accounting.asset-revaluations.store') }}">
                            @csrf

                            <x-form.section number="01" :title="__('Revaluation Details')" />

                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="asset_id" value="{{ __('Fixed Asset') }}" />
                                    <select id="asset_id" name="asset_id" class="input mt-1" required>
                                        <option value="">Select Asset</option>
                                        @foreach($assets as $asset)
                                            <option value="{{ $asset->id }}" {{ old('asset_id', request('asset_id')) == $asset->id ? 'selected' : '' }}>
                                                {{ $asset->asset_code }} - {{ $asset->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('asset_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="revaluation_date" value="{{ __('Revaluation Date') }}" />
                                    <x-text-input id="revaluation_date" name="revaluation_date" type="date" class="mt-1 block w-full" :value="old('revaluation_date', date('Y-m-d'))" required />
                                    <x-input-error :messages="$errors->get('revaluation_date')" class="mt-2" />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="previous_value" value="{{ __('Previous Carrying Value') }}" />
                                        <x-text-input id="previous_value" name="previous_value" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('previous_value')" required />
                                        <x-input-error :messages="$errors->get('previous_value')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="revalued_amount" value="{{ __('Revalued Amount') }}" />
                                        <x-text-input id="revalued_amount" name="revalued_amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('revalued_amount')" required />
                                        <x-input-error :messages="$errors->get('revalued_amount')" class="mt-2" />
                                    </div>
                                </div>

                                <div>
                                    <x-input-label for="revaluation_surplus" value="{{ __('Revaluation Surplus') }}" />
                                    <x-text-input id="revaluation_surplus" name="revaluation_surplus" type="number" step="0.01" class="mt-1 block w-full" :value="old('revaluation_surplus')" />
                                    <x-input-error :messages="$errors->get('revaluation_surplus')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="reason" value="{{ __('Reason') }}" />
                                    <textarea id="reason" name="reason" rows="3" class="input mt-1">{{ old('reason') }}</textarea>
                                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.asset-revaluations.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button>{{ __('Create Revaluation') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Fixed Assets'), 'route' => route('accounting.fixed-assets.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.25 21h19.5m-18-18v18m10.5-18v18m6-18v18M3 3h7.5M3 21h7.5\"/></svg>'],
                        ['title' => __('Asset Revaluations List'), 'route' => route('accounting.asset-revaluations.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z\"/></svg>'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
