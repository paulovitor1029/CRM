<?php

namespace App\Http\Controllers;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class WebhookController
{
    public function __construct(private readonly WebhookService $webhooks)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $list = WebhookEndpoint::where('tenant_id', $tenant)->orderBy('event_key')->get();
        return response()->json(['data' => $list]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable','string','max:64'],
            'event_key' => ['required','string','max:128'],
            'url' => ['required','url'],
            'secret' => ['nullable','string','max:255'],
            'headers' => ['array'],
            'active' => ['boolean'],
        ]);
        $data['tenant_id'] = $data['tenant_id'] ?? 'default';
        $data['secret'] = $data['secret'] ?? Str::random(32);
        $endpoint = WebhookEndpoint::create($data);
        return response()->json(['data' => $endpoint], Response::HTTP_CREATED);
    }

    public function deliveries(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $rows = WebhookDelivery::query()
            ->join('webhook_endpoints as e', 'e.id', '=', 'webhook_deliveries.endpoint_id')
            ->where('e.tenant_id', $tenant)
            ->orderByDesc('webhook_deliveries.created_at')
            ->limit(100)
            ->get(['webhook_deliveries.*']);
        return response()->json(['data' => $rows]);
    }
}

