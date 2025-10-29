<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'string', 'max:64'],
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'billing_interval' => ['required', 'in:day,week,month,year'],
            'billing_period' => ['required', 'integer', 'min:1', 'max:365'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'pro_rata' => ['boolean'],
            'courtesy_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'limits' => ['array'],
        ];
    }
}

