<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'sector_id' => ['sometimes', 'uuid', 'exists:sectors,id'],
            'meta' => ['sometimes', 'array'],
        ];
    }
}

