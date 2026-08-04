<x-app-layout>
    <x-list-header title="{{ __('Document Numbering Sequences') }}" />

<div class="py-6">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Document Numbering Sequences</h1>
            <x-button variant="primary" href="{{ route('admin.numbering-sequences.create') }}">{{ __('Add Sequence') }}</x-button>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">
                {{ session('success') }}
            </div>
        @endif

        <div class="datasheet-wrap">
            <div class="overflow-x-auto">
                <table class="datasheet">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>Prefix</th>
                            <th>Padding</th>
                            <th>Next Number</th>
                            <th>Reset Policy</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sequences as $sequence)
                        <tr>
                            <td>
                                {{ $labels[$sequence->document_type] ?? $sequence->document_type }}
                            </td>
                            <td>
                                <code class="bg-gray-100 px-2 py-0.5 rounded">{{ $sequence->prefix }}</code>
                            </td>
                            <td>
                                {{ $sequence->padding_width }} digits
                            </td>
                            <td>
                                {{ str_pad($sequence->next_number, $sequence->padding_width, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <span class="status-pill neutral">
                                    {{ ucfirst($sequence->reset_policy) }}
                                </span>
                            </td>
                            <td>
                                @if($sequence->is_active)
                                    <span class="status-pill positive">Active</span>
                                @else
                                    <span class="status-pill negative">Inactive</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.numbering-sequences.show', $sequence) }}" class="text-ink hover:text-gold">View</a>
                                <a href="{{ route('admin.numbering-sequences.edit', $sequence) }}" class="text-ink hover:text-gold ml-2">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No numbering sequences configured. Click "Add Sequence" to create one.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
