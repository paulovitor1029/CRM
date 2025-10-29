<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreRequest;
use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CustomerController
{
    public function __construct(private readonly CustomerService $customers)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $org = (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $query = Customer::query()->where('organization_id', $org);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($tag = $request->query('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('tag', $tag));
        }
        if ($funnel = $request->query('funnel')) {
            $query->where('funnel_stage', $funnel);
        }

        $perPage = min(100, (int) ($request->query('per_page') ?? 15));
        $page = $query->orderBy('created_at', 'desc')
            ->cursorPaginate($perPage)
            ->appends($request->query());

        return response()->json([
            'data' => $page->items(),
            'next_cursor' => $page->nextCursor()?->encode(),
            'prev_cursor' => $page->previousCursor()?->encode(),
        ]);
    }

    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['organization_id'] = $payload['organization_id'] ?? (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $origin = $payload['origin'] ?? $request->header('X-Origin') ?? $request->userAgent();
        $userId = optional($request->user())->id;

        $customer = $this->customers->create($payload, $userId, $origin);
        Log::info('customer_created_api', [
            'customer_id' => $customer->id,
            'organization_id' => $payload['organization_id'],
        ]);

        return response()->json(['data' => $customer], Response::HTTP_CREATED);
    }
}
