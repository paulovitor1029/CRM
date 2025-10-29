<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrganizationContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $orgId = (string) ($request->query('organization_id') ?? $request->session()->get('organization_id') ?? '');

        if (!$orgId && $user) {
            $first = $user->organizations()->first();
            if ($first) $orgId = $first->id;
        }

        if ($orgId) {
            $request->attributes->set('organization_id', $orgId);
            $request->session()->put('organization_id', $orgId);
            Log::withContext(['organization_id' => $orgId]);
        }

        return $next($request);
    }
}

