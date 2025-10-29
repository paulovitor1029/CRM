<?php

namespace App\Http\Controllers;

use App\Models\OAuthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OAuthClientsController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'scopes' => ['array'],
        ]);
        $secretPlain = Str::random(40);
        $client = OAuthClient::create([
            'name' => $data['name'],
            'secret' => Hash::make($secretPlain),
            'scopes' => $data['scopes'] ?? [],
            'active' => true,
        ]);
        return response()->json([
            'client_id' => $client->id,
            'client_secret' => $secretPlain,
        ], Response::HTTP_CREATED);
    }
}

