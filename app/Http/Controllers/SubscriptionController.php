<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionStoreRequest;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController
{
    public function __construct(private readonly SubscriptionService $subs)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $list = Subscription::with(['items'])->where('tenant_id', $tenant)->orderByDesc('created_at')->get();
        return response()->json(['data' => $list]);
    }

    public function store(SubscriptionStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $data['tenant_id'] ?? 'default';
        $userId = optional($request->user())->id;
        $origin = $data['origin'] ?? $request->header('X-Origin') ?? $request->userAgent();
        $sub = $this->subs->create($data, $userId, $origin);
        return response()->json(['data' => $sub], Response::HTTP_CREATED);
    }
}

