<?php

namespace App\Services;

use App\Models\Pending;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Models\SlaPolicy;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\TaskLabel;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function create(array $data, ?string $userId = null, ?string $origin = null): Task
    {
        return $this->db->transaction(function () use ($data, $userId, $origin) {
            $tenant = $data['organization_id'] ?? 'default';
            $sla = null;
            if (!empty($data['sla_policy_id'])) {
                $sla = SlaPolicy::findOrFail($data['sla_policy_id']);
            } else {
                $sla = SlaPolicy::where('organization_id', $tenant)->where('key', 'default')->first();
            }

            $task = Task::create([
                'organization_id' => $tenant,
                'sector_id' => $data['sector_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => 'open',
                'priority' => $data['priority'] ?? 'normal',
                'due_at' => $data['due_at'] ?? null,
                'created_by' => $userId,
                'assignee_id' => null,
                'sla_policy_id' => $sla?->id,
                'response_due_at' => $sla ? now()->copy()->addMinutes($sla->target_response_minutes) : null,
                'resolution_due_at' => $sla ? now()->copy()->addMinutes($sla->target_resolution_minutes) : null,
                'recurrence' => $data['recurrence'] ?? null,
                'depends_on_task_id' => $data['depends_on_task_id'] ?? null,
            ]);

            if (!empty($data['labels'])) {
                $task->labels()->sync($data['labels']);
            }

            Pending::create(['task_id' => $task->id]);

            TaskHistory::create([
                'task_id' => $task->id,
                'action' => 'create',
                'before' => null,
                'after' => $task->only(['title','description','status','priority','due_at','sla_policy_id','response_due_at','resolution_due_at','recurrence','depends_on_task_id']),
                'user_id' => $userId,
                'origin' => $origin,
            ]);

            Log::info('task_created', ['task_id' => $task->id, 'organization_id' => $tenant, 'user_id' => $userId]);
            event(new TaskCreated($task->id, $tenant, $task->sector_id, $task->title));
            return $task->load(['labels']);
        });
    }

    public function assign(Task $task, string $userId, ?string $origin = null): Task
    {
        return $this->db->transaction(function () use ($task, $userId, $origin) {
            $maxOpen = (int) (Config::get('tasks.max_open_assignments', 5));
            $openStatuses = (array) Config::get('tasks.open_statuses', ['open','in_progress','on_hold']);
            $openCount = Task::where('assignee_id', $userId)->whereIn('status', $openStatuses)->count();
            if ($openCount >= $maxOpen) {
                throw ValidationException::withMessages(['user_id' => 'User has reached the limit of open assignments']);
            }

            $before = $task->only(['assignee_id','status']);
            // Atomic claim on pending to prevent race
            $claimed = Pending::where('task_id', $task->id)->whereNull('assigned_to')->update(['assigned_to' => $userId]);
            if ($claimed === 0 && Pending::where('task_id', $task->id)->whereNotNull('assigned_to')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['task' => 'Task already claimed by another user']);
            }
            $task->assignee_id = $userId;
            if ($task->first_response_at === null) {
                $task->first_response_at = now();
            }
            if ($task->status === 'open') {
                $task->status = 'in_progress';
            }
            $task->save();

            TaskHistory::create([
                'task_id' => $task->id,
                'action' => 'assign',
                'before' => $before,
                'after' => $task->only(['assignee_id','status','first_response_at']),
                'user_id' => $userId,
                'origin' => $origin,
            ]);

            Log::info('task_assigned', ['task_id' => $task->id, 'user_id' => $userId]);
            event(new TaskAssigned($task->id, $task->organization_id, $userId));
            return $task;
        });
    }

    public function complete(Task $task, ?string $userId = null, ?string $origin = null): Task
    {
        if ($task->depends_on_task_id) {
            $dep = Task::find($task->depends_on_task_id);
            if ($dep && $dep->status !== 'done') {
                throw ValidationException::withMessages(['task' => 'Task depends on another task not completed']);
            }
        }

        return $this->db->transaction(function () use ($task, $userId, $origin) {
            $before = $task->only(['status','completed_at']);
            $task->status = 'done';
            $task->completed_at = now();
            $task->save();

            // Remove from pending
            Pending::where('task_id', $task->id)->delete();

            TaskHistory::create([
                'task_id' => $task->id,
                'action' => 'complete',
                'before' => $before,
                'after' => $task->only(['status','completed_at']),
                'user_id' => $userId,
                'origin' => $origin,
            ]);

            Log::info('task_completed', ['task_id' => $task->id, 'user_id' => $userId]);
            event(new TaskCompleted($task->id, $task->organization_id, $task->assignee_id));
            return $task;
        });
    }
}
