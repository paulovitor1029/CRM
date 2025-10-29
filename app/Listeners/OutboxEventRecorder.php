<?php

namespace App\Listeners;

use App\Events\CustomerCreated;
use App\Events\CustomerStageChanged;
use App\Events\PaymentApproved;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Models\OutboxEvent;

class OutboxEventRecorder
{
    public function handle($event): void
    {
        [$key, $payload, $tenant] = $this->map($event);
        if (!$key) return;
        OutboxEvent::create([
            'tenant_id' => $tenant ?? 'default',
            'event_key' => $key,
            'payload' => $payload,
            'status' => 'pending',
        ]);
    }

    private function map($event): array
    {
        switch (true) {
            case $event instanceof TaskCreated:
                return ['task.created', ['task_id' => $event->taskId, 'title' => $event->title], $event->tenantId];
            case $event instanceof TaskAssigned:
                return ['task.assigned', ['task_id' => $event->taskId, 'assignee_id' => $event->assigneeId], $event->tenantId];
            case $event instanceof TaskCompleted:
                return ['task.completed', ['task_id' => $event->taskId], $event->tenantId];
            case $event instanceof CustomerStageChanged:
                return ['customer.stage.changed', ['customer_id' => $event->customerId, 'to_stage_id' => $event->toStageId, 'pipeline_id' => $event->pipelineId], $event->tenantId];
            case $event instanceof CustomerCreated:
                return ['customer.created', ['customer_id' => $event->customerId], $event->tenantId];
            case $event instanceof PaymentApproved:
                return ['payment.approved', ['payment_id' => $event->paymentId, 'customer_id' => $event->customerId, 'amount_cents' => $event->amountCents], $event->tenantId];
            default:
                return [null, [], null];
        }
    }
}

