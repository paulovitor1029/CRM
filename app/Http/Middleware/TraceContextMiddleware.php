<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TraceContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $traceparent = $request->headers->get('traceparent');
        if (!$traceparent) {
            $traceId = bin2hex(random_bytes(16));
            $spanId = bin2hex(random_bytes(8));
            $traceparent = sprintf('00-%s-%s-01', $traceId, $spanId);
        } else {
            $parts = explode('-', $traceparent);
            $traceId = $parts[1] ?? bin2hex(random_bytes(16));
            $spanId = bin2hex(random_bytes(8));
            $traceparent = sprintf('00-%s-%s-01', $traceId, $spanId);
        }
        $request->attributes->set('trace_id', $traceId);
        $request->attributes->set('span_id', $spanId);
        Log::withContext(['trace_id' => $traceId]);

        $response = $next($request);
        $response->headers->set('traceparent', $traceparent);
        return $response;
    }
}

