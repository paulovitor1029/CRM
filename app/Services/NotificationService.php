<?php

namespace App\Services;

use App\Events\CustomerStageChanged;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Models\Notification;
use App\Models\UserNotificationPref;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function notifyTaskCreated(TaskCreated $e): void
    {
        $title = 'Nova tarefa';
        $body = $e->title;
        $this->store(null, $e->organizationId, 'task.created', $title, $body, ['task_id' => $e->taskId]);
    }

    public function notifyTaskAssigned(TaskAssigned $e): void
    {
        $title = 'Tarefa atribuída';
        $body = 'Você recebeu uma nova tarefa';
        $this->store($e->assigneeId, $e->organizationId, 'task.assigned', $title, $body, ['task_id' => $e->taskId]);
    }

    public function notifyTaskCompleted(TaskCompleted $e): void
    {
        $title = 'Tarefa concluída';
        $body = 'Uma tarefa foi concluída';
        $this->store($e->assigneeId, $e->organizationId, 'task.completed', $title, $body, ['task_id' => $e->taskId]);
    }

    public function notifyStageChanged(CustomerStageChanged $e): void
    {
        $title = 'Mudança de etapa';
        $body = 'Cliente mudou de etapa';
        $this->store($e->userId, $e->organizationId ?? 'default', 'customer.stage.changed', $title, $body, [
            'customer_id' => $e->customerId,
            'pipeline_id' => $e->pipelineId,
            'to_stage_id' => $e->toStageId,
        ]);
    }

    private function store(?string $userId, string $organizationId, string $type, string $title, string $body, array $data = []): void
    {
        // Respect quiet hours: store in-app notification regardless; push/broadcast handled by frontend
        $prefs = $userId ? UserNotificationPref::find($userId) : null;
        Notification::create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'channel' => 'in_app',
        ]);
        Log::info('notification_created', ['type' => $type, 'user_id' => $userId, 'organization_id' => $organizationId]);
    }
}
