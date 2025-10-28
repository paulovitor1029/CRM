<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectorStoreRequest;
use App\Models\Sector;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SectorController
{
    public function index(): JsonResponse
    {
        $sectors = Sector::query()->orderBy('name')->get(['id', 'tenant_id', 'name', 'description']);
        return response()->json(['data' => $sectors]);
    }

    public function store(SectorStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $data['tenant_id'] ?? 'default';
        $sector = Sector::create($data);
        Log::info('sector_created', [
            'sector_id' => $sector->id,
            'tenant_id' => $sector->tenant_id,
        ]);
        return response()->json(['data' => $sector], Response::HTTP_CREATED);
    }
}

