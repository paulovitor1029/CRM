<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskAssignRequest;
use App\Http\Requests\TaskCompleteRequest;
use App\Http\Requests\TaskStoreRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TaskController
{
    public function __construct(private readonly TaskService $tasks)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $query = Task::query()->where('tenant_id', $tenant);
        if ($sector = $request->query('sector_id')) {
            $query->where('sector_id', $sector);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($assignee = $request->query('assignee_id')) {
            $query->where('assignee_id', $assignee);
        }
        $tasks = $query->orderBy('created_at', 'desc')->paginate(20);
        return response()->json(['data' => $tasks->items(), 'meta' => ['current_page' => $tasks->currentPage()]]);
    }

    public function store(TaskStoreRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['tenant_id'] = $payload['tenant_id'] ?? 'default';
        $origin = $request->header('X-Origin') ?? $request->userAgent();
        $userId = optional($request->user())->id;
        $task = $this->tasks->create($payload, $userId, $origin);
        return response()->json(['data' => $task], Response::HTTP_CREATED);
    }

    public function assign(string $id, TaskAssignRequest $request): JsonResponse
    {
        $task = Task::findOrFail($id);
        $userId = (string) $request->validated('user_id');
        $origin = $request->validated('origin') ?? $request->header('X-Origin') ?? $request->userAgent();
        $task = $this->tasks->assign($task, $userId, $origin);
        return response()->json(['data' => $task]);
    }

    public function complete(string $id, TaskCompleteRequest $request): JsonResponse
    {
        $task = Task::findOrFail($id);
        $userId = optional($request->user())->id;
        $origin = $request->validated('origin') ?? $request->header('X-Origin') ?? $request->userAgent();
        $task = $this->tasks->complete($task, $userId, $origin);
        return response()->json(['data' => $task]);
    }

    public function kanban(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $sector = (string) ($request->query('sector_id') ?? '');
        $query = Task::query()->where('tenant_id', $tenant);
        if ($sector !== '') { $query->where('sector_id', $sector); }
        $rows = $query->get(['id','title','status','priority','assignee_id','due_at']);
        $columns = [];
        foreach (['open','in_progress','on_hold','blocked','done','canceled'] as $st) { $columns[$st] = []; }
        foreach ($rows as $t) {
            $columns[$t->status][] = [
                'id' => $t->id,
                'title' => $t->title,
                'priority' => $t->priority,
                'assignee_id' => $t->assignee_id,
                'due_at' => optional($t->due_at)?->toISOString(),
            ];
        }
        return response()->json(['columns' => $columns]);
    }

    public function myAgenda(Request $request): JsonResponse
    {
        $userId = optional($request->user())->id;
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $tasks = Task::where('tenant_id', $tenant)->where('assignee_id', $userId)
            ->whereIn('status', ['open','in_progress','on_hold','blocked'])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END, due_at ASC')
            ->get(['id','title','status','priority','due_at']);
        return response()->json(['data' => $tasks]);
    }
}

