<?php

namespace App\Services;

use App\Events\CustomerStageChanged;
use App\Models\Customer;
use App\Models\CustomerPipelineState;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\PipelineTransitionLog;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PipelineService
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function transition(Customer $customer, string $pipelineKey, string $toStageKey, ?string $justification = null, ?string $userId = null, ?string $origin = null): CustomerPipelineState
    {
        $tenant = $customer->organization_id;
        $pipeline = Pipeline::where('organization_id', $tenant)->where('key', $pipelineKey)->first();
        if (!$pipeline) {
            throw ValidationException::withMessages(['pipeline_key' => 'Pipeline not found for tenant']);
        }
        $toStage = PipelineStage::where('pipeline_id', $pipeline->id)->where('key', $toStageKey)->first();
        if (!$toStage) {
            throw ValidationException::withMessages(['to_stage' => 'Stage not found in pipeline']);
        }

        return $this->db->transaction(function () use ($customer, $pipeline, $toStage, $justification, $userId, $origin) {
            $state = CustomerPipelineState::firstOrCreate([
                'customer_id' => $customer->id,
                'pipeline_id' => $pipeline->id,
            ]);
            $fromStageId = $state->current_stage_id;

            // TODO: motor de regras (placeholder) — por enquanto sempre permite transição manual

            $state->forceFill([
                'current_stage_id' => $toStage->id,
                'updated_by' => $userId,
            ])->save();

            PipelineTransitionLog::create([
                'customer_id' => $customer->id,
                'pipeline_id' => $pipeline->id,
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $toStage->id,
                'justification' => $justification,
                'user_id' => $userId,
                'origin' => $origin,
            ]);

            event(new CustomerStageChanged(
                $customer->id,
                $pipeline->id,
                $fromStageId,
                $toStage->id,
                $justification,
                $userId,
                $origin,
                $pipeline->organization_id,
            ));

            Log::info('customer_stage_changed', [
                'customer_id' => $customer->id,
                'pipeline_id' => $pipeline->id,
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $toStage->id,
                'user_id' => $userId,
                'origin' => $origin,
            ]);

            return $state->fresh('currentStage');
        });
    }
}
