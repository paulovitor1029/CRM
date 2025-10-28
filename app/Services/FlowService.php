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

    public function publish(FlowDefinition $flow, ?string $userId = null): FlowDefinition
    {
        if ($flow->frozen || $flow->published_at !== null) {
            throw ValidationException::withMessages([
                'flow' => 'This flow definition is already published and frozen.',
            ]);
        }

        return $this->db->transaction(function () use ($flow, $userId) {
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

