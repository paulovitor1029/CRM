<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DeviceSessionEnforcer
{
    public function handle(Request $request, Closure $next)
    {
        $deviceId = (string) ($request->header('X-Device-Id') ?? $request->input('device_id', ''));
        $sessionDevice = (string) $request->session()->get('device_id', '');
        if ($sessionDevice !== '' && $deviceId !== '' && $sessionDevice !== $deviceId) {
            return response()->json(['message' => 'Invalid device session'], 401);
        }

        // If not yet bound and provided, bind
        if ($sessionDevice === '' && $deviceId !== '') {
            $request->session()->put('device_id', $deviceId);
        }

        return $next($request);
    }
}

