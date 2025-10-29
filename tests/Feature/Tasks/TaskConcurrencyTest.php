<?php

use App\Models\Task;
use App\Models\Pending;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('prevents double assignment via atomic claim', function () {
    $u1 = User::factory()->create(['password' => Hash::make('P@ssw0rd!')]);
    $u2 = User::factory()->create(['password' => Hash::make('P@ssw0rd!')]);
    $this->actingAs($u1);
    $task = Task::create(['tenant_id' => 'default', 'title' => 'Lock test']);
    Pending::create(['task_id' => $task->id]);

    // First assign works
    $this->postJson("/api/tasks/{$task->id}/assign", ['user_id' => $u1->id])->assertOk();

    // Simulate concurrent claim by setting assigned_to
    Pending::where('task_id', $task->id)->update(['assigned_to' => $u1->id]);

    // Second user cannot claim
    $this->postJson("/api/tasks/{$task->id}/assign", ['user_id' => $u2->id])->assertStatus(422);
});

