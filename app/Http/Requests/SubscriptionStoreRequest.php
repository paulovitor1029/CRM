<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'string', 'max:64'],
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
            'bundle_id' => ['nullable', 'uuid', 'exists:bundles,id'],
            'items' => ['nullable', 'array'],
            'items.*.item_type' => ['required_with:items', 'string', 'in:plan,addon,product'],
            'items.*.item_id' => ['required_with:items', 'uuid'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'pro_rata' => ['boolean'],
            'courtesy_until' => ['nullable', 'date'],
            'limits' => ['array'],
            'origin' => ['nullable', 'string', 'max:255'],
        ];
    }
}

