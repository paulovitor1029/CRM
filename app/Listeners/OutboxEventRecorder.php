<?php

namespace App\Listeners;

use App\Events\CustomerCreated;
use App\Events\CustomerStageChanged;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Models\OutboxEvent;

class OutboxEventRecorder
{
    public function handle($event): void
    {
        [$key, $payload, $org] = $this->map($event);
        if (!$key) return;
        OutboxEvent::create([
            'organization_id' => $org ?? 'default',
            'event_key' => $key,
            'payload' => $payload,
            'status' => 'pending',
        ]);
    }

    private function map($event): array
    {
        switch (true) {
            case $event instanceof TaskCreated:
                return ['task.created', ['task_id' => $event->taskId, 'title' => $event->title], $event->organizationId];
            case $event instanceof TaskAssigned:
                return ['task.assigned', ['task_id' => $event->taskId, 'assignee_id' => $event->assigneeId], $event->organizationId];
            case $event instanceof TaskCompleted:
                return ['task.completed', ['task_id' => $event->taskId], $event->organizationId];
            case $event instanceof CustomerStageChanged:
                return ['customer.stage.changed', ['customer_id' => $event->customerId, 'to_stage_id' => $event->toStageId, 'pipeline_id' => $event->pipelineId], $event->organizationId];
            case $event instanceof CustomerCreated:
                return ['customer.created', ['customer_id' => $event->customerId], $event->organizationId];
            default:
                return [null, [], null];
        }
    }
}

