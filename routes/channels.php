<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('organization.{organizationId}', function ($user, $organizationId) {
    // Validate if user belongs to organization. For MVP, allow authenticated users.
    return (bool) $user;
});
