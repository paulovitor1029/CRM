<?php

namespace App\Http\Middleware;

use App\Observability\MetricsRegistry as Metrics;
use Closure;
use Illuminate\Http\Request;

class HttpMetricsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $start;

        $method = strtolower($request->getMethod());
        $status = $response->getStatusCode();
        $route = $request->route()?->uri() ?? $request->path();
        $tenant = (string) ($request->attributes->get('organization_id') ?? 'default');

        Metrics::incCounter('http_requests_total', compact('method','route','status','tenant'));
        Metrics::observeHistogram('http_request_duration_seconds', compact('method','route','status','tenant'), $duration, [0.05,0.1,0.25,0.5,1,2,5]);

        if ($status >= 500) {
            Metrics::incCounter('http_errors_total', ['tenant' => $tenant, 'route' => $route, 'status' => $status]);
        }

        return $response;
    }
}
