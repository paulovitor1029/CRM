<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'sector_id' => ['nullable', 'uuid', 'exists:sectors,id'],
            'meta' => ['array'],
        ];
    }
}

