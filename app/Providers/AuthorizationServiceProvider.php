<?php

namespace App\Providers;

use App\Services\AuthorizationService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthorizationService::class, function (Application $app) {
            return new AuthorizationService();
        });
    }

    public function boot(): void
    {
        // Admin or permission + optional ABAC via Gate::before
        Gate::before(function ($user, string $ability, array $arguments = []) {
            /** @var AuthorizationService $authz */
            $authz = app(AuthorizationService::class);
            // Admin short-circuit
            if (method_exists($user, 'roles') && $user->roles()->where('name', 'admin')->exists()) {
                return true;
            }
            // treat every ability as permission string; pass first array arg as attributes if present
            $attrs = [];
            if (isset($arguments[0]) && is_array($arguments[0])) {
                $attrs = $arguments[0];
            }
            return $authz->can($user, $ability, $attrs) ?: null; // null lets policies handle other abilities
        });
    }
}

