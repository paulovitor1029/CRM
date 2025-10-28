<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlowDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nodes' => ['required', 'array', 'min:1'],
            'nodes.*.key' => ['required', 'string', 'max:128'],
            'nodes.*.name' => ['required', 'string', 'max:255'],
            'nodes.*.initial' => ['sometimes', 'boolean'],
            'nodes.*.terminal' => ['sometimes', 'boolean'],

            'edges' => ['required', 'array', 'min:1'],
            'edges.*.key' => ['required', 'string', 'max:128'],
            'edges.*.from' => ['required', 'string', 'max:128'],
            'edges.*.to' => ['required', 'string', 'max:128'],
            'edges.*.conditions' => ['sometimes', 'array'],
            'edges.*.conditions.*.type' => ['required', 'string', 'in:always,attribute_equals,tag_in'],
            'edges.*.conditions.*.params' => ['sometimes', 'array'],
            'edges.*.trigger' => ['sometimes', 'array'],
            'edges.*.trigger.type' => ['required_with:edges.*.trigger', 'string', 'in:manual,event'],
            'edges.*.trigger.name' => ['required_if:edges.*.trigger.type,event', 'string'],
        ];
    }
}

