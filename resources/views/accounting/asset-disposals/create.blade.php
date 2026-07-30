<x-app-layout>
    <x-slot name="header">{{ __('New Asset Disposal') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                <form method="POST" action="{{ route('accounting.asset-disposals.store') }}">
                    @csrf

                    <x-form.section number="01" :title="__('Disposal Details')" />

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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="disposal_date" value="{{ __('Disposal Date') }}" />
                                <x-text-input id="disposal_date" name="disposal_date" type="date" class="mt-1 block w-full" :value="old('disposal_date', date('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('disposal_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="disposal_method" value="{{ __('Disposal Method') }}" />
                                <select id="disposal_method" name="disposal_method" class="input mt-1" required>
                                    <option value="">Select Method</option>
                                    <option value="sale" {{ old('disposal_method') === 'sale' ? 'selected' : '' }}>Sale</option>
                                    <option value="scrap" {{ old('disposal_method') === 'scrap' ? 'selected' : '' }}>Scrap</option>
                                    <option value="donation" {{ old('disposal_method') === 'donation' ? 'selected' : '' }}>Donation</option>
                                    <option value="write_off" {{ old('disposal_method') === 'write_off' ? 'selected' : '' }}>Write Off</option>
                                </select>
                                <x-input-error :messages="$errors->get('disposal_method')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="proceeds" value="{{ __('Sale Proceeds') }}" />
                                <x-text-input id="proceeds" name="proceeds" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('proceeds', '0.00')" />
                                <x-input-error :messages="$errors->get('proceeds')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="book_value_at_disposal" value="{{ __('NBV at Disposal') }}" />
                                <x-text-input id="book_value_at_disposal" name="book_value_at_disposal" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('book_value_at_disposal')" />
                                <x-input-error :messages="$errors->get('book_value_at_disposal')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="reason" value="{{ __('Reason / Notes') }}" />
                            <textarea id="reason" name="reason" rows="3" class="input mt-1">{{ old('reason') }}</textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 gap-3">
                        <x-button variant="ghost" href="{{ route('accounting.asset-disposals.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Create Disposal') }}</x-primary-button>
                    </div>
                </form>
            </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Fixed Assets'), 'route' => route('accounting.fixed-assets.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z\"/></svg>'],
                        ['title' => __('Asset Disposals List'), 'route' => route('accounting.asset-disposals.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 6h16.5M3.75 12h16.5m-16.5 6h16.5\"/></svg>'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
