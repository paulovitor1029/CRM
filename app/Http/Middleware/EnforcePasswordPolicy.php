<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforcePasswordPolicy
{
    public function handle(Request $request, Closure $next)
    {
        $password = (string) $request->input('password', '');

        $valid = strlen($password) >= 12
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);

        if (!$valid) {
            return response()->json([
                'message' => 'Password does not meet policy',
                'requirements' => 'Min 12 chars, at least one uppercase, lowercase, number, and symbol',
            ], 422);
        }

        return $next($request);
    }
}

