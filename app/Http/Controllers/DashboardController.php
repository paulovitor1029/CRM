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
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $data = $this->reporting->widgets($tenant, $request->all());
        return response()->json(['data' => $data]);
    }
}

