<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAttribute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AuthorizationService
{
    private const SESSION_KEY = 'auth.permissions';

    public function primeSessionCache(User $user): void
    {
        Session::put(self::SESSION_KEY, $this->computePermissions($user));
    }

    public function clearSessionCache(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function permissions(User $user): array
    {
        $perms = Session::get(self::SESSION_KEY);
        if (is_array($perms)) {
            return $perms;
        }
        $perms = $this->computePermissions($user);
        Session::put(self::SESSION_KEY, $perms);
        return $perms;
    }

    public function can(User $user, string $permission, array $attributes = []): bool
    {
        // Admin short-circuit is handled in Gate::before; keep defensive check
        if ($this->isAdmin($user)) {
            return true;
        }
        $allowed = in_array($permission, $this->permissions($user), true);
        if (!$allowed) {
            return false;
        }
        if ($attributes === [] || !$this->hasAttributeConstraints($permission)) {
            return true;
        }
        return $this->abacSatisfied($user, $attributes);
    }

    private function computePermissions(User $user): array
    {
        $perms = Permission::query()
            ->select('permissions.name')
            ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
            ->join('user_role', 'role_permission.role_id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $user->id)
            ->pluck('permissions.name')
            ->unique()
            ->values()
            ->all();
        sort($perms);
        return $perms;
    }

    private function isAdmin(User $user): bool
    {
        return $user->roles()->where('name', 'admin')->exists();
    }

    private function hasAttributeConstraints(string $permission): bool
    {
        // For now, attribute checks are opt-in from caller by passing $attributes
        // You can enforce specific permissions to always consider attributes by name pattern.
        return true;
    }

    private function abacSatisfied(User $user, array $requirements): bool
    {
        $profile = UserAttribute::firstOrCreate(['user_id' => $user->id], [
            'attributes' => [
                'setor' => null,
                'turno' => null,
                'tags' => [],
            ],
        ]);
        $attrs = (array) ($profile->attributes ?? []);

        foreach ($requirements as $key => $expected) {
            $actual = $attrs[$key] ?? null;
            if ($key === 'tags') {
                $expectedTags = (array) $expected;
                $actualTags = (array) ($actual ?? []);
                // require intersection non-empty
                if (empty(array_intersect($expectedTags, $actualTags))) {
                    return false;
                }
                continue;
            }
            if (is_array($expected)) {
                if (!in_array($actual, $expected, true)) return false;
            } else {
                if ($actual !== $expected) return false;
            }
        }
        return true;
    }
}

