<?php

namespace App\Services;

use App\Models\FlowDefinition;
use App\Models\FlowLog;
use App\Models\FlowState;
use App\Models\FlowTransition;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FlowService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function create(array $payload): FlowDefinition
    {
        $tenant = $payload['tenant_id'] ?? 'default';
        $key = $payload['key'];
        $name = $payload['name'];
        $description = $payload['description'] ?? null;
        $states = $payload['states'] ?? [];
        $transitions = $payload['transitions'] ?? [];

        // Validate states: at least one initial and one terminal
        $initialCount = collect($states)->where('initial', true)->count();
        $terminalCount = collect($states)->where('terminal', true)->count();
        if ($initialCount < 1 || $terminalCount < 1) {
            throw ValidationException::withMessages([
                'states' => 'Flow must have at least one initial and one terminal state.',
            ]);
        }

        // Validate referenced transitions
        $stateKeys = collect($states)->pluck('key')->all();
        foreach ($transitions as $t) {
            if (!in_array($t['from'], $stateKeys, true) || !in_array($t['to'], $stateKeys, true)) {
                throw ValidationException::withMessages([
                    'transitions' => 'Transitions must reference existing states.',
                ]);
            }
        }

        return $this->db->transaction(function () use ($tenant, $key, $name, $description, $states, $transitions) {
            $currentMax = FlowDefinition::where('tenant_id', $tenant)->where('key', $key)->max('version');
            $version = (int) $currentMax + 1;

            $flow = FlowDefinition::create([
                'tenant_id' => $tenant,
                'key' => $key,
                'version' => $version,
                'name' => $name,
                'description' => $description,
                'frozen' => false,
            ]);

            // Create states keyed
            $createdStates = [];
            foreach ($states as $s) {
                $createdStates[$s['key']] = FlowState::create([
                    'flow_definition_id' => $flow->id,
                    'key' => $s['key'],
                    'name' => $s['name'],
                    'initial' => (bool) ($s['initial'] ?? false),
                    'terminal' => (bool) ($s['terminal'] ?? false),
                ]);
            }

            // Create transitions
            foreach ($transitions as $t) {
                FlowTransition::create([
                    'flow_definition_id' => $flow->id,
                    'from_state_id' => $createdStates[$t['from']]->id,
                    'to_state_id' => $createdStates[$t['to']]->id,
                    'key' => $t['key'],
                ]);
            }

            return $flow->load(['states', 'transitions']);
        });
    }

    public function saveDesign(FlowDefinition $flow, array $graph): FlowDefinition
    {
        // Basic dedupe of node/edge keys
        $nodeKeys = collect($graph['nodes'] ?? [])->pluck('key');
        if ($nodeKeys->unique()->count() !== $nodeKeys->count()) {
            throw ValidationException::withMessages(['nodes' => 'Node keys must be unique.']);
        }
        $edgeKeys = collect($graph['edges'] ?? [])->pluck('key');
        if ($edgeKeys->unique()->count() !== $edgeKeys->count()) {
            throw ValidationException::withMessages(['edges' => 'Edge keys must be unique.']);
        }

        $this->validateGraph($graph);
        $flow->design_draft = $graph;
        $flow->save();
        return $flow->fresh();
    }

    public function validateGraph(array $graph): void
    {
        $nodes = collect($graph['nodes'] ?? []);
        $edges = collect($graph['edges'] ?? []);

        if ($nodes->isEmpty()) {
            throw ValidationException::withMessages(['nodes' => 'At least one node is required.']);
        }
        if ($edges->isEmpty()) {
            throw ValidationException::withMessages(['edges' => 'At least one edge is required.']);
        }

        $initialCount = $nodes->where('initial', true)->count();
        $terminalCount = $nodes->where('terminal', true)->count();
        if ($initialCount < 1 || $terminalCount < 1) {
            throw ValidationException::withMessages(['nodes' => 'Graph requires at least one initial and one terminal node.']);
        }

        $nodeKeys = $nodes->pluck('key')->all();
        foreach ($edges as $e) {
            if (!in_array($e['from'], $nodeKeys, true) || !in_array($e['to'], $nodeKeys, true)) {
                throw ValidationException::withMessages(['edges' => 'Edges must reference existing nodes.']);
            }

            // Validate conditions
            $conds = collect($e['conditions'] ?? []);
            foreach ($conds as $cond) {
                $type = $cond['type'] ?? '';
                $params = $cond['params'] ?? [];
                if ($type === 'always') {
                    // ok
                } elseif ($type === 'attribute_equals') {
                    if (!isset($params['attribute']) || !array_key_exists('value', $params)) {
                        throw ValidationException::withMessages(['conditions' => 'attribute_equals requires attribute and value.']);
                    }
                } elseif ($type === 'tag_in') {
                    if (empty($params['tags']) || !is_array($params['tags'])) {
                        throw ValidationException::withMessages(['conditions' => 'tag_in requires non-empty tags array.']);
                    }
                } else {
                    throw ValidationException::withMessages(['conditions' => 'Unknown condition type: '.$type]);
                }
            }

            // Validate trigger
            if (isset($e['trigger'])) {
                $tr = $e['trigger'];
                if (($tr['type'] ?? null) === 'event' && empty($tr['name'])) {
                    throw ValidationException::withMessages(['trigger' => 'event trigger requires name']);
                }
                if (!in_array(($tr['type'] ?? ''), ['manual', 'event'], true)) {
                    throw ValidationException::withMessages(['trigger' => 'Unsupported trigger type']);
                }
            }
        }

        // Reachability: every node must be reachable from at least one initial
        $graphAdj = [];
        foreach ($nodeKeys as $k) { $graphAdj[$k] = []; }
        foreach ($edges as $e) { $graphAdj[$e['from']][] = $e['to']; }

        $initials = $nodes->filter(fn ($n) => !empty($n['initial']))->pluck('key')->all();
        $reachable = [];
        $queue = $initials;
        while ($queue) {
            $curr = array_shift($queue);
            if (isset($reachable[$curr])) continue;
            $reachable[$curr] = true;
            foreach ($graphAdj[$curr] as $next) {
                if (!isset($reachable[$next])) $queue[] = $next;
            }
        }

        $unreachables = array_values(array_diff($nodeKeys, array_keys($reachable)));
        if (!empty($unreachables)) {
            throw ValidationException::withMessages(['nodes' => 'Unreachable nodes: '.implode(',', $unreachables)]);
        }
    }

    public function publish(FlowDefinition $flow, ?string $userId = null): FlowDefinition
    {
        if ($flow->frozen || $flow->published_at !== null) {
            throw ValidationException::withMessages([
                'flow' => 'This flow definition is already published and frozen.',
            ]);
        }

        return $this->db->transaction(function () use ($flow, $userId) {
            // If design draft exists, validate and rebuild states/transitions from it
            if (!empty($flow->design_draft)) {
                $graph = is_array($flow->design_draft) ? $flow->design_draft : (array) json_decode(json_encode($flow->design_draft), true);
                $this->validateGraph($graph);

                // wipe existing states/transitions for this definition
                FlowTransition::where('flow_definition_id', $flow->id)->delete();
                FlowState::where('flow_definition_id', $flow->id)->delete();

                $createdStates = [];
                foreach ($graph['nodes'] as $n) {
                    $createdStates[$n['key']] = FlowState::create([
                        'flow_definition_id' => $flow->id,
                        'key' => $n['key'],
                        'name' => $n['name'],
                        'initial' => (bool) ($n['initial'] ?? false),
                        'terminal' => (bool) ($n['terminal'] ?? false),
                    ]);
                }
                foreach ($graph['edges'] as $e) {
                    FlowTransition::create([
                        'flow_definition_id' => $flow->id,
                        'from_state_id' => $createdStates[$e['from']]->id,
                        'to_state_id' => $createdStates[$e['to']]->id,
                        'key' => $e['key'],
                    ]);
                }
            }

            $flow->forceFill([
                'published_at' => now(),
                'frozen' => true,
            ])->save();

            FlowLog::create([
                'flow_definition_id' => $flow->id,
                'action' => 'publish',
                'details' => [
                    'version' => $flow->version,
                    'key' => $flow->key,
                ],
                'user_id' => $userId,
            ]);

            Log::info('flow_published', [
                'flow_id' => $flow->id,
                'key' => $flow->key,
                'version' => $flow->version,
                'user_id' => $userId,
            ]);

            return $flow->fresh(['states', 'transitions']);
        });
    }
}
