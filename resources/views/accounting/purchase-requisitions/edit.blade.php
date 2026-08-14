<x-app-layout>
    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @include('accounting.purchase-requisitions._form', [
                'isEdit' => true,
                'requisition' => $requisition,
                'title' => __('Edit Requisition'),
                'submitLabel' => __('Save Changes'),
            ])
        </div>
    </div>
</x-app-layout>
