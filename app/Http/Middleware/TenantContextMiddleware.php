<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $request->headers->get('X-Tenant-Id') ?? (string) ($request->query('tenant_id') ?? 'default');
        $request->attributes->set('tenant_id', $tenant);
        Log::withContext(['tenant_id' => $tenant]);
        $response = $next($request);
        $response->headers->set('X-Tenant-Id', $tenant);
        return $response;
    }
}

