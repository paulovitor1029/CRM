<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlowStoreRequest;
use App\Http\Requests\FlowDesignRequest;
use App\Models\FlowDefinition;
use App\Services\FlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class FlowController
{
    public function __construct(private readonly FlowService $flows)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $list = FlowDefinition::with(['states', 'transitions'])
            ->where('tenant_id', $tenant)
            ->orderBy('key')->orderByDesc('version')
            ->get();
        return response()->json(['data' => $list]);
    }

    public function store(FlowStoreRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['tenant_id'] = $payload['tenant_id'] ?? 'default';
        $flow = $this->flows->create($payload);
        return response()->json(['data' => $flow], Response::HTTP_CREATED);
    }

    public function design(string $id, FlowDesignRequest $request): JsonResponse
    {
        $flow = FlowDefinition::findOrFail($id);
        if ($flow->frozen) {
            return response()->json(['message' => 'Flow is frozen'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $graph = $request->validated();
        $flow = $this->flows->saveDesign($flow, $graph);
        return response()->json(['data' => $flow]);
    }

    public function publish(string $id, Request $request): JsonResponse
    {
        $flow = FlowDefinition::with(['states', 'transitions'])->findOrFail($id);
        $user = $request->user();
        $flow = $this->flows->publish($flow, optional($user)->id);
        return response()->json(['data' => $flow]);
    }
}
