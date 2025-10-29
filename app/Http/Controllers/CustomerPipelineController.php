<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerTransitionRequest;
use App\Models\Customer;
use App\Services\PipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerPipelineController
{
    public function __construct(private readonly PipelineService $pipeline)
    {
    }

    public function transition(string $id, CustomerTransitionRequest $request): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $payload = $request->validated();
        $userId = optional($request->user())->id;
        $origin = $payload['origin'] ?? $request->header('X-Origin') ?? $request->userAgent();

        $state = $this->pipeline->transition(
            $customer,
            $payload['pipeline_key'],
            $payload['to_stage'],
            $payload['justification'] ?? null,
            $userId,
            $origin,
        );

        return response()->json([
            'data' => [
                'pipeline_id' => $state->pipeline_id,
                'current_stage' => $state->currentStage?->only(['id','key','name','position']),
            ],
        ], Response::HTTP_OK);
    }
}

