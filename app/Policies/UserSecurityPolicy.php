<?php

namespace App\Policies;

use App\Models\User;

class UserSecurityPolicy
{
    public function loginWithoutTwoFactor(?User $user): bool
    {
        if (!$user) return false;
        $sec = $user->security()->first();
        return !$sec || !$sec->two_factor_enabled;
    }
}

