<x-app-layout>
    <x-list-header title="{{ __('Branch Requests') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            

            @if($errors->any())
                <x-feedback.alert variant="error" class="mb-4">{{ $errors->first() }}</x-feedback.alert>
            @endif

            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-600">
                    @if($usage['branch_limit'] === null)
                        {{ __('Branch usage: unlimited') }}
                    @else
                        {{ __('Branch usage: :count of :limit used', ['count' => $usage['branch_count'], 'limit' => $usage['branch_limit']]) }}
                    @endif
                </p>
            </div>

            @php $hasOpen = $requests->contains(fn ($r) => in_array($r->status, \App\Models\BranchRequest::OPEN_STATUSES, true)); @endphp

            @if(!$hasOpen)
                <div class="mb-6 card p-6">
                    <div class="form-section-label mb-4">1 · Request Additional Branches</div>
                    <form method="POST" action="{{ route('branch-requests.store') }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="branch_name" value="{{ __('Branch Name') }}" />
                                <x-text-input id="branch_name" name="branch_name" type="text" class="input mt-1 block w-full" :value="old('branch_name')" required />
                                <x-input-error :messages="$errors->get('branch_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="branch_code" value="{{ __('Branch Code') }}" />
                                <x-text-input id="branch_code" name="branch_code" type="text" class="input mt-1 block w-full" :value="old('branch_code')" />
                                <x-input-error :messages="$errors->get('branch_code')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="requested_quantity" value="{{ __('Number of Branches') }}" />
                                <x-text-input id="requested_quantity" name="requested_quantity" type="number" min="1" max="50" class="input mt-1 block w-full" :value="old('requested_quantity', 1)" required />
                                <x-input-error :messages="$errors->get('requested_quantity')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="contact_person" value="{{ __('Contact Person') }}" />
                                <x-text-input id="contact_person" name="contact_person" type="text" class="input mt-1 block w-full" :value="old('contact_person')" />
                            </div>
                            <div>
                                <x-input-label for="contact_email" value="{{ __('Contact Email') }}" />
                                <x-text-input id="contact_email" name="contact_email" type="email" class="input mt-1 block w-full" :value="old('contact_email')" />
                            </div>
                            <div>
                                <x-input-label for="contact_phone" value="{{ __('Contact Phone') }}" />
                                <x-text-input id="contact_phone" name="contact_phone" type="text" class="input mt-1 block w-full" :value="old('contact_phone')" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="branch_address" value="{{ __('Address') }}" />
                                <x-text-input id="branch_address" name="branch_address" type="text" class="input mt-1 block w-full" :value="old('branch_address')" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="reason" value="{{ __('Reason / Notes') }}" />
                                <textarea id="reason" name="reason" rows="3" class="input mt-1 block w-full">{{ old('reason') }}</textarea>
                                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <x-primary-button type="submit">{{ __('Submit Request') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            @else
                <div class="mb-6 rounded-lg border border-gold-line bg-gold-soft px-4 py-3 text-sm text-ink">
                    {{ __('A request is already pending review, quoting, or payment. A new request can be submitted once it is resolved.') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th class="text-center">Quantity</th>
                                <th>Requested</th>
                                <th>Quotation</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $r)
                                <tr>
                                    <td>
                                        <span class="font-medium text-ink">{{ $r->branch_name }}</span>
                                        @if($r->branch_code)
                                            <span class="block text-xs text-ink-soft">{{ $r->branch_code }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $r->requested_quantity }}</td>
                                    <td>{{ $r->requested_at?->format('M j, Y') }}</td>
                                    <td>
                                        @if($r->quotation)
                                            <a href="{{ route('branch-requests.show', $r) }}" class="text-brick hover:underline">{{ $r->quotation->quotation_number }}</a>
                                            <span class="block text-xs text-ink-soft">{{ number_format($r->quotation->total, 2) }} {{ $r->quotation->currency_code }}</span>
                                        @else
                                            <span class="text-ink-soft">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @include('branch-requests._status', ['status' => $r->status])
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <a href="{{ route('branch-requests.show', $r) }}" class="btn-ghost px-3 py-1 text-xs">{{ __('View') }}</a>
                                        @if(in_array($r->status, [\App\Models\BranchRequest::STATUS_PENDING_REVIEW, \App\Models\BranchRequest::STATUS_QUOTED], true))
                                            <form method="POST" action="{{ route('branch-requests.cancel', $r) }}" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Cancel this branch request?') }}', { type: 'danger' })">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 text-xs text-red-600 hover:text-red-800">{{ __('Cancel') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-ink-soft text-center">No branch requests yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
