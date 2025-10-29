<?php

use App\Models\OAuthClient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('issues client credentials token and accesses public API', function () {
    $secret = 'topsecret';
    $client = OAuthClient::create(['name' => 'Test', 'secret' => Hash::make($secret), 'scopes' => []]);

    $token = $this->postJson('/api/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => $client->id,
        'client_secret' => $secret,
    ])->assertOk()->json('access_token');

    $this->getJson('/api/v1/ping', ['Authorization' => 'Bearer '.$token])->assertOk();
});

