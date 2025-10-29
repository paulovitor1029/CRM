<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {
    // If your user has tenant membership, validate here. Default allow for authenticated users.
    return (bool) $user;
});

