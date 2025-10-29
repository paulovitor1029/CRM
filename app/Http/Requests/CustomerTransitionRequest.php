<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pipeline_key' => ['required', 'string', 'max:64'],
            'to_stage' => ['required', 'string', 'max:64'],
            'justification' => ['nullable', 'string', 'max:2000'],
            'origin' => ['nullable', 'string', 'max:255'],
        ];
    }
}

