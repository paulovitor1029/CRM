<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantToOrganizationAlias
{
    public function handle(Request $request, Closure $next)
    {
        // Backward-compat: map tenant_id query to organization_id
        if ($request->query('tenant_id') && !$request->query('organization_id')) {
            $request->query->set('organization_id', $request->query('tenant_id'));
        }
        return $next($request);
    }
}

