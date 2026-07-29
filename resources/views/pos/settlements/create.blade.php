<x-app-layout>
    <x-slot name="header">{{ __('Record Payment Settlement') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <div class="form-section-label">1 · Settlement Details</div>
                <form method="POST" action="{{ route('pos.settlements.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <select name="payment_method_id" class="input mt-1" required>
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
                        <label class="block text-sm font-medium text-gray-700">Deposit To (Bank Account)</label>
                        <select name="bank_account_id" class="input mt-1" required>
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
                            <label class="block text-sm font-medium text-gray-700">Period Start</label>
                            <input type="date" name="period_start" value="{{ old('period_start', now()->subDays(7)->toDateString()) }}"
                                class="input mt-1" required />
                            @error('period_start') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Period End</label>
                            <input type="date" name="period_end" value="{{ old('period_end', now()->toDateString()) }}"
                                class="input mt-1" required />
                            @error('period_end') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total Settled Amount ($)</label>
                            <input type="number" name="total_amount" step="0.01" min="0.01" value="{{ old('total_amount') }}"
                                class="input mt-1" required />
                            @error('total_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Processing Fee ($)</label>
                            <input type="number" name="fee_amount" step="0.01" min="0" value="{{ old('fee_amount', '0.00') }}"
                                class="input mt-1" />
                            @error('fee_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Processor Reference</label>
                        <input type="text" name="reference" value="{{ old('reference') }}"
                            class="input mt-1" placeholder="Optional" />
                        @error('reference') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" rows="3"
                            class="input mt-1">{{ old('notes') }}</textarea>
                        @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-button variant="ghost" href="{{ route('pos.settlements.index') }}">{{ __('Cancel') }}</x-button>
                        <x-button variant="primary" type="submit">{{ __('Record Settlement') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
