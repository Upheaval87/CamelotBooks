<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'code' => ['required', 'string', 'max:50', "unique:accounts,code,NULL,id,company_id,{$companyId}"],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:asset,liability,equity,income,expense'],
            'sub_type' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
            'currency' => ['required', 'string', 'max:10'],
        ];
    }
}
