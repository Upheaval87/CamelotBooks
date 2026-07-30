<x-app-layout>
    <x-slot name="header">{{ __('Record Payment Settlement') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <form method="POST" action="{{ route('pos.settlements.store') }}">
                            @csrf

                            <x-form.section number="01" :title="__('Settlement Details')" />

                            <div class="mb-4">
                                <x-input-label for="payment_method_id" value="Payment Method" />
                                <select name="payment_method_id" id="payment_method_id" class="input mt-1" required>
                                    <option value="">-- Select Payment Method --</option>
                                    @foreach($paymentMethods as $pm)
                                        <option value="{{ $pm->id }}" {{ old('payment_method_id') == $pm->id ? 'selected' : '' }}>
                                            {{ $pm->name }} ({{ $pm->type }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_method_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-4">
                                <x-input-label for="bank_account_id" value="Deposit To (Bank Account)" />
                                <select name="bank_account_id" id="bank_account_id" class="input mt-1" required>
                                    <option value="">-- Select Bank Account --</option>
                                    @foreach($bankAccounts as $acct)
                                        <option value="{{ $acct->id }}" {{ old('bank_account_id') == $acct->id ? 'selected' : '' }}>
                                            {{ $acct->code }} – {{ $acct->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('bank_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <x-input-label for="period_start" value="Period Start" />
                                    <input type="date" name="period_start" id="period_start" value="{{ old('period_start', now()->subDays(7)->toDateString()) }}"
                                        class="input mt-1" required />
                                    @error('period_start') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <x-input-label for="period_end" value="Period End" />
                                    <input type="date" name="period_end" id="period_end" value="{{ old('period_end', now()->toDateString()) }}"
                                        class="input mt-1" required />
                                    @error('period_end') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <x-input-label for="total_amount" value="Total Settled Amount ($)" />
                                    <input type="number" name="total_amount" id="total_amount" step="0.01" min="0.01" value="{{ old('total_amount') }}"
                                        class="input mt-1" required />
                                    @error('total_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <x-input-label for="fee_amount" value="Processing Fee ($)" />
                                    <input type="number" name="fee_amount" id="fee_amount" step="0.01" min="0" value="{{ old('fee_amount', '0.00') }}"
                                        class="input mt-1" />
                                    @error('fee_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <x-input-label for="reference" value="Processor Reference" />
                                <input type="text" name="reference" id="reference" value="{{ old('reference') }}"
                                    class="input mt-1" placeholder="Optional" />
                                @error('reference') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-6">
                                <x-input-label for="notes" value="Notes" />
                                <textarea name="notes" id="notes" rows="3"
                                    class="input mt-1">{{ old('notes') }}</textarea>
                                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('pos.settlements.index') }}">{{ __('Cancel') }}</x-button>
                                <x-button variant="primary" type="submit">{{ __('Record Settlement') }}</x-button>
                            </div>
                        </form>
                    </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('View'), 'links' => [
                        ['title' => __('POS Dashboard'), 'route' => route('pos.dashboard'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z\"/></svg>'],
                        ['title' => __('Settlements List'), 'route' => route('pos.settlements.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z\"/></svg>'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
