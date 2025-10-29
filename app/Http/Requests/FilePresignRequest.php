<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilePresignRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:512'],
            'content_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'checksum' => ['nullable', 'string', 'max:128'],
            'meta' => ['array'],
        ];
    }
}

