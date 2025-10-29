<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'string', 'max:64'],
            'sector_id' => ['nullable', 'uuid', 'exists:sectors,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'due_at' => ['nullable', 'date'],
            'sla_policy_id' => ['nullable', 'uuid', 'exists:sla_policies,id'],
            'labels' => ['array'],
            'labels.*' => ['uuid', 'exists:task_labels,id'],
            'recurrence' => ['array'],
            'depends_on_task_id' => ['nullable', 'uuid', 'exists:tasks,id'],
        ];
    }
}
