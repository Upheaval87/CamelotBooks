<x-app-layout>
    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @include('accounting.purchase-requisitions._form', [
                'isEdit' => false,
                'title' => __('Create Requisition'),
                'submitLabel' => __('Save'),
            ])
        </div>
    </div>
</x-app-layout>
