<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlowStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'string', 'max:64'],
            'key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'states' => ['required', 'array', 'min:1'],
            'states.*.key' => ['required', 'string', 'max:128'],
            'states.*.name' => ['required', 'string', 'max:255'],
            'states.*.initial' => ['boolean'],
            'states.*.terminal' => ['boolean'],
            'transitions' => ['array'],
            'transitions.*.key' => ['required', 'string', 'max:128'],
            'transitions.*.from' => ['required', 'string', 'max:128'],
            'transitions.*.to' => ['required', 'string', 'max:128'],
        ];
    }
}

