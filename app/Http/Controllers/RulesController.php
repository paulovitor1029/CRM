<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessOutboxEvent;
use App\Models\OutboxEvent;
use App\Models\RuleAction;
use App\Models\RuleDefinition;
use App\Models\RuleRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RulesController
{
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'event_key' => ['required','string','max:128'],
            'enabled' => ['boolean'],
            'conditions' => ['array'],
            'actions' => ['array','min:1'],
            'actions.*.type' => ['required','string','in:create_task,change_stage,send_notification,webhook'],
            'actions.*.position' => ['nullable','integer'],
            'actions.*.params' => ['nullable','array'],
        ]);
        $rule = RuleDefinition::create([
            'tenant_id' => (string) ($request->query('tenant_id') ?? 'default'),
            'name' => $data['name'],
            'event_key' => $data['event_key'],
            'conditions' => $data['conditions'] ?? [],
            'enabled' => (bool) ($data['enabled'] ?? true),
        ]);
        foreach ($data['actions'] as $i => $a) {
            RuleAction::create([
                'rule_id' => $rule->id,
                'type' => $a['type'],
                'position' => $a['position'] ?? $i,
                'params' => $a['params'] ?? [],
            ]);
        }
        return response()->json(['data' => $rule->load('actions')], Response::HTTP_CREATED);
    }

    public function simulate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_key' => ['required','string'],
            'payload' => ['required','array'],
        ]);
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $rules = RuleDefinition::with('actions')->where('tenant_id',$tenant)->where('event_key',$data['event_key'])->where('enabled',true)->get();
        $results = [];
        foreach ($rules as $rule) {
            $results[] = [
                'rule_id' => $rule->id,
                'name' => $rule->name,
                'conditions' => $rule->conditions,
                'actions' => $rule->actions->map(fn($a)=>['type'=>$a->type,'params'=>$a->params,'position'=>$a->position])->values()->all(),
            ];
        }
        return response()->json(['matches' => $results]);
    }

    public function ingest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_key' => ['required','string'],
            'payload' => ['required','array'],
        ]);
        $evt = OutboxEvent::create([
            'tenant_id' => (string) ($request->query('tenant_id') ?? 'default'),
            'event_key' => $data['event_key'],
            'payload' => $data['payload'],
            'status' => 'pending',
        ]);
        ProcessOutboxEvent::dispatch($evt->id);
        return response()->json(['outbox_id' => $evt->id], Response::HTTP_ACCEPTED);
    }

    public function replay(string $id): JsonResponse
    {
        ProcessOutboxEvent::dispatch($id);
        return response()->json(['outbox_id' => $id, 'status' => 'requeued']);
    }

    public function runs(Request $request): JsonResponse
    {
        $runs = RuleRun::orderByDesc('created_at')->limit(50)->get();
        return response()->json(['data' => $runs]);
    }
}

