<x-app-layout>
    @include('accounting.expenses._form', [
        'isEdit' => false,
        'formAction' => route('accounting.expenses.store'),
        'formMethod' => 'POST',
        'cancelRoute' => route('accounting.expenses.index'),
        'title' => __('Record Expense'),
        'subtitle' => __('Capture a business expense against an account, budget and cost centre.'),
        'submitLabel' => __('Save & Submit'),
    ])
</x-app-layout>
