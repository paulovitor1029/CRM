<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserSecurityPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserSecurityPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

