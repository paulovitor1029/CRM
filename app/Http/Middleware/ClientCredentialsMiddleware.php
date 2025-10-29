<?php

namespace App\Http\Middleware;

use App\Models\OAuthAccessToken;
use Closure;
use Illuminate\Http\Request;

class ClientCredentialsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $auth = $request->headers->get('Authorization', '');
        if (!str_starts_with($auth, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $token = substr($auth, 7);
        $rec = OAuthAccessToken::where('token', $token)->first();
        if (!$rec || $rec->expires_at->isPast()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $request->attributes->set('oauth_client_id', $rec->client_id);
        return $next($request);
    }
}

