<?php

namespace App\Jobs;

use App\Models\OutboxEvent;
use App\Models\RuleAction;
use App\Models\RuleDefinition;
use App\Models\RuleRun;
use App\Services\NotificationService;
use App\Services\PipelineService;
use App\Services\TaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProcessOutboxEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $outboxId) {}

    public function handle(TaskService $tasks, PipelineService $pipelines, NotificationService $notify): void
    {
        $evt = OutboxEvent::find($this->outboxId);
        if (!$evt) return;
        $rules = RuleDefinition::with('actions')
            ->where('organization_id', $evt->organization_id)
            ->where('event_key', $evt->event_key)
            ->where('enabled', true)
            ->get();

        foreach ($rules as $rule) {
            // Idempotency: skip if already run
            if (RuleRun::where('rule_id', $rule->id)->where('outbox_id', $evt->id)->exists()) {
                continue;
            }
            $run = RuleRun::create([
                'rule_id' => $rule->id,
                'outbox_id' => $evt->id,
                'status' => 'processing',
                'started_at' => now(),
                'attempts' => 1,
                'logs' => [],
            ]);

            try {
                if (!$this->conditionsPass($evt->payload, $rule->conditions ?? [])) {
                    $run->status = 'completed';
                    $run->logs = ['skipped' => 'conditions_not_met'];
                    $run->finished_at = now();
                    $run->save();
                    continue;
                }

                $actionLogs = [];
                foreach ($rule->actions as $action) {
                    $actionLogs[] = $this->executeAction($action, $evt->payload, $tasks, $pipelines, $notify);
                }

                $run->status = 'completed';
                $run->logs = ['actions' => $actionLogs];
                $run->finished_at = now();
                $run->save();
            } catch (\Throwable $e) {
                $run->status = 'failed';
                $run->logs = ['error' => $e->getMessage()];
                $run->finished_at = now();
                $run->save();
                Log::error('rule_run_failed', ['run_id' => $run->id, 'error' => $e->getMessage()]);
            }
        }

        // Enqueue webhooks for this event
        app(\App\Services\WebhookService::class)->enqueueForOutbox($evt);

        // Finalize outbox status
        $evt->status = 'processed';
        $evt->processed_at = now();
        $evt->save();
    }

    private function conditionsPass(array $payload, array $conditions): bool
    {
        // Minimal evaluator: AND of simple conditions
        foreach ($conditions as $cond) {
            $type = $cond['type'] ?? '';
            $params = $cond['params'] ?? [];
            if ($type === 'attribute_equals') {
                $key = (string) ($params['attribute'] ?? '');
                $expected = $params['value'] ?? null;
                $actual = Arr::get($payload, $key);
                if ($actual !== $expected) return false;
            } elseif ($type === 'payload_contains') {
                $key = (string) ($params['attribute'] ?? '');
                $needle = (string) ($params['contains'] ?? '');
                $actual = (string) (Arr::get($payload, $key) ?? '');
                if (strpos($actual, $needle) === false) return false;
            }
        }
        return true;
    }

    private function executeAction(RuleAction $action, array $payload, TaskService $tasks, PipelineService $pipelines, NotificationService $notify): array
    {
        $params = $action->params ?? [];
        $type = $action->type;
        if ($type === 'create_task') {
            $task = $tasks->create([
                'title' => $this->tpl($params['title'] ?? 'Tarefa'),
                'description' => $this->tpl($params['description'] ?? ''),
                'organization_id' => $params['organization_id'] ?? 'default',
                'sector_id' => $params['sector_id'] ?? null,
                'priority' => $params['priority'] ?? 'normal',
            ], $params['user_id'] ?? null, 'rule');
            return ['type' => 'create_task', 'task_id' => $task->id];
        }
        if ($type === 'change_stage') {
            $customerId = $params['customer_id'] ?? ($payload['customer_id'] ?? null);
            $pipelineKey = $params['pipeline_key'] ?? 'vendas';
            $toStage = $params['to_stage'] ?? 'qualificado';
            if ($customerId) {
                $pipelines->transition(\App\Models\Customer::findOrFail($customerId), $pipelineKey, $toStage, 'rule', $params['user_id'] ?? null, 'rule');
            }
            return ['type' => 'change_stage', 'customer_id' => $customerId, 'to_stage' => $toStage];
        }
        if ($type === 'send_notification') {
            // Use NotificationService directly
            $userId = $params['user_id'] ?? ($payload['assignee_id'] ?? null);
            $title = $this->tpl($params['title'] ?? 'Notificação');
            $body = $this->tpl($params['body'] ?? '');
            \App\Models\Notification::create([
                'organization_id' => $params['organization_id'] ?? 'default',
                'user_id' => $userId,
                'type' => 'rule.notification',
                'title' => $title,
                'body' => $body,
                'data' => $payload,
                'channel' => 'in_app',
            ]);
            return ['type' => 'send_notification', 'user_id' => $userId];
        }
        if ($type === 'webhook') {
            $endpoint = $params['endpoint'] ?? null;
            if ($endpoint && config('rules.webhooks_enabled', false)) {
                Http::timeout(3)->post($endpoint, $payload);
                return ['type' => 'webhook', 'endpoint' => $endpoint, 'status' => 'sent'];
            }
            return ['type' => 'webhook', 'status' => 'skipped'];
        }
        return ['type' => $type, 'status' => 'unknown'];
    }

    private function tpl(string $template): string
    {
        // Very small templating: replace {{key}}
        return $template; // keep simple for MVP
    }
}
