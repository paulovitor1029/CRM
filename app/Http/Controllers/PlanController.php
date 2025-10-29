<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanStoreRequest;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlanController
{
    public function index(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $list = Plan::with('product')->where('tenant_id', $tenant)->orderBy('name')->get();
        return response()->json(['data' => $list]);
    }

    public function store(PlanStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $data['tenant_id'] ?? 'default';
        $plan = Plan::create($data);
        return response()->json(['data' => $plan->load('product')], Response::HTTP_CREATED);
    }
}

