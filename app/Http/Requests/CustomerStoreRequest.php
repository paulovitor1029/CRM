<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = (string) ($this->input('tenant_id') ?? 'default');
        return [
            'tenant_id' => ['nullable', 'string', 'max:64'],
            'external_id' => ['nullable', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'status' => [
                'required', 'string', 'max:64',
                Rule::exists('customer_statuses', 'name')->where(function ($q) use ($tenant) {
                    $q->where('tenant_id', $tenant)->where('is_active', true);
                }),
            ],
            'funnel_stage' => ['nullable', 'string', 'max:64'],
            'meta' => ['nullable', 'array'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.type' => ['required_with:contacts', 'string', 'max:64'],
            'contacts.*.value' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.preferred' => ['boolean'],
            'contacts.*.meta' => ['array'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.type' => ['required_with:addresses', 'string', 'max:64'],
            'addresses.*.line1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.line2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:128'],
            'addresses.*.state' => ['nullable', 'string', 'max:64'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:32'],
            'addresses.*.country' => ['nullable', 'string', 'size:2'],
            'addresses.*.meta' => ['array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'origin' => ['nullable', 'string', 'max:128'],
        ];
    }
}

