<?php

use App\Models\User;
use App\Models\UserSecurity;
use App\Support\Totp;
use Illuminate\Support\Facades\Hash;

it('logs in successfully without 2FA', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Str0ngP@ssw0rd!'),
    ]);

    $resp = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'Str0ngP@ssw0rd!',
        'device_id' => 'device-123',
    ], [
        'X-Request-Id' => 'test-req-1',
    ]);

    $resp->assertOk()->assertJson(['message' => 'Authenticated']);
    expect(session('device_id'))->toBe('device-123');
    $this->assertAuthenticatedAs($user);
    expect($resp->headers->get('X-Request-Id'))->toBe('test-req-1');
});

it('requires 2FA and verifies with TOTP', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Str0ngP@ssw0rd!'),
    ]);
    $secret = 'JBSWY3DPEHPK3PXP'; // base32 for 'Hello!\xDEMO'
    UserSecurity::create([
        'user_id' => $user->id,
        'two_factor_enabled' => true,
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => [
            ['value' => 'RECOVERY-CODE-1', 'used_at' => null],
            ['value' => 'RECOVERY-CODE-2', 'used_at' => null],
        ],
    ]);

    $login = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'Str0ngP@ssw0rd!',
    ]);
    $login->assertStatus(202);
    expect(session()->has('2fa:pending_user_id'))->toBeTrue();

    $code = Totp::generateCode($secret);
    $verify = $this->postJson('/api/auth/2fa/verify', [
        'code' => $code,
    ]);
    $verify->assertOk();
    $this->assertAuthenticatedAs($user);
});

it('verifies 2FA with a recovery code', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Str0ngP@ssw0rd!'),
    ]);
    UserSecurity::create([
        'user_id' => $user->id,
        'two_factor_enabled' => true,
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_recovery_codes' => [
            ['value' => 'ONE-TIME-RECOVERY', 'used_at' => null],
        ],
    ]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'Str0ngP@ssw0rd!',
    ])->assertStatus(202);

    $verify = $this->postJson('/api/auth/2fa/verify', [
        'recovery_code' => 'ONE-TIME-RECOVERY',
    ]);
    $verify->assertOk();
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid password and records failed login', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Str0ngP@ssw0rd!'),
    ]);

    $resp = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password-1!',
    ]);

    $resp->assertUnauthorized();
    $this->assertGuest();
    $this->assertDatabaseHas('failed_logins', [
        'email' => $user->email,
        'reason' => 'invalid_credentials',
    ]);
});

it('logs out and invalidates session', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Str0ngP@ssw0rd!'),
    ]);
    $this->actingAs($user);

    $resp = $this->postJson('/api/auth/logout');
    $resp->assertNoContent();
    $this->assertGuest();
});

it('refreshes the session', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Str0ngP@ssw0rd!'),
    ]);
    $this->actingAs($user);

    $old = session()->getId();
    $resp = $this->postJson('/api/auth/refresh');
    $resp->assertOk();
    expect(session()->getId())->not->toBe($old);
});

it('enforces device/session binding', function () {
    $user = User::factory()->create(['password' => Hash::make('Str0ngP@ssw0rd!')]);
    // Login with device A
    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'Str0ngP@ssw0rd!',
        'device_id' => 'device-A',
    ])->assertOk();

    // Try to refresh with device B header -> should 401
    $this->withHeader('X-Device-Id', 'device-B')
        ->postJson('/api/auth/refresh')
        ->assertUnauthorized();
});
