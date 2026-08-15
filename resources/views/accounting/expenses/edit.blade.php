<x-app-layout>
    @include('accounting.expenses._form', [
        'isEdit' => true,
        'expense' => $expense,
        'formAction' => route('accounting.expenses.update', $expense),
        'formMethod' => 'PUT',
        'cancelRoute' => route('accounting.expenses.show', $expense),
        'title' => __('Edit Expense') . ' ' . $expense->expense_number,
        'subtitle' => __('Adjust details before this expense is submitted for approval.'),
        'submitLabel' => __('Save & Submit'),
        'budget' => $budget ?? null,
    ])
</x-app-layout>
