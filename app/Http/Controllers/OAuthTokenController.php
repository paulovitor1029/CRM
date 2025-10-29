<?php

namespace App\Http\Controllers;

use App\Models\OAuthAccessToken;
use App\Models\OAuthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OAuthTokenController
{
    public function issueToken(Request $request): JsonResponse
    {
        $grant = $request->input('grant_type');
        if ($grant !== 'client_credentials') {
            return response()->json(['error' => 'unsupported_grant_type'], 400);
        }

        $clientId = $request->input('client_id');
        $clientSecret = $request->input('client_secret');
        // Support Basic auth
        if (!$clientId || !$clientSecret) {
            $auth = $request->headers->get('Authorization', '');
            if (str_starts_with($auth, 'Basic ')) {
                $decoded = base64_decode(substr($auth, 6));
                if ($decoded && str_contains($decoded, ':')) {
                    [$clientId, $clientSecret] = explode(':', $decoded, 2);
                }
            }
        }

        if (!$clientId || !$clientSecret) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        $client = OAuthClient::find($clientId);
        if (!$client || !$client->active || !Hash::check($clientSecret, $client->secret)) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        $token = Str::random(64);
        $ttl = 3600;
        OAuthAccessToken::create([
            'client_id' => $client->id,
            'token' => $token,
            'scopes' => $client->scopes,
            'expires_at' => now()->addSeconds($ttl),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $ttl,
        ], Response::HTTP_OK);
    }
}

