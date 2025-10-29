<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    public function __construct(private readonly ReportingService $reporting)
    {
    }

    public function widgets(Request $request): JsonResponse
    {
        $org = (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $data = $this->reporting->widgets($org, $request->all());
        return response()->json(['data' => $data]);
    }
}
