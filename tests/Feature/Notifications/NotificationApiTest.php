<?php

use App\Events\TaskAssigned;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('saves a web push subscription for user', function () {
    $u = User::factory()->create(['password' => Hash::make('Str0ngP@ssw0rd!')]);
    $this->actingAs($u);
    $this->postJson('/api/notifications/subscription', [
        'endpoint' => 'https://push.example/abc',
        'keys' => ['p256dh' => 'k1', 'auth' => 'k2'],
        'browser' => 'chrome',
        'platform' => 'windows',
    ])->assertCreated();
});

it('stores notification record on task assignment', function () {
    $u = User::factory()->create();
    event(new \App\Events\TaskAssigned('task-id-1', 'default', (string) $u->id));
    $this->assertDatabaseHas('notifications', [
        'tenant_id' => 'default',
        'user_id' => $u->id,
        'type' => 'task.assigned',
    ]);
});

