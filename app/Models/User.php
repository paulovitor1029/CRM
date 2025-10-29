<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyRel;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name', 'email', 'password',
        'password_expires_at', 'password_must_change', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_expires_at' => 'datetime',
        'password_must_change' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function security(): HasOne
    {
        return $this->hasOne(UserSecurity::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function hasRole(string $name): bool
    {
        return $this->roles()->where('name', $name)->exists();
    }

    public function organizations(): BelongsToManyRel
    {
        return $this->belongsToMany(Organization::class, 'organization_user')->withPivot(['role','permissions','invited_at','accepted_at'])->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('superadmin');
    }
}
