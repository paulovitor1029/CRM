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
        $org = (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $sectors = Sector::query()->where('organization_id', $org)->orderBy('name')->get(['id', 'organization_id', 'name', 'description']);
        return response()->json(['data' => $sectors]);
    }

    public function store(SectorStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $data['organization_id'] ?? (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $sector = Sector::create($data);
        Log::info('sector_created', [
            'sector_id' => $sector->id,
            'organization_id' => $sector->organization_id,
        ]);
        return response()->json(['data' => $sector], Response::HTTP_CREATED);
    }
}
