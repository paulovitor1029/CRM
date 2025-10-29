<?php

use App\Models\Sector;
use App\Models\Task;
use App\Models\User;
use App\Models\SlaPolicy;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // default SLA
    SlaPolicy::updateOrCreate(['tenant_id' => 'default', 'key' => 'default'], [
        'name' => 'Default', 'target_response_minutes' => 60, 'target_resolution_minutes' => 240
    ]);
});

it('creates a task with SLA timers and appears in kanban', function () {
    $this->actingAs(User::factory()->create(['password' => Hash::make('Str0ngP@ssw0rd!')]));
    $sector = \App\Models\Sector::create(['tenant_id' => 'default', 'name' => 'Suporte']);

    $resp = $this->postJson('/api/tasks', [
        'title' => 'Atender chamado',
        'sector_id' => $sector->id,
        'priority' => 'high',
    ])->assertCreated();
    $id = $resp->json('data.id');
    expect($resp->json('data.response_due_at'))->not->toBeNull();
    expect($resp->json('data.resolution_due_at'))->not->toBeNull();

    $kanban = $this->getJson('/api/tasks/kanban?sector_id='.$sector->id)->assertOk();
    $columns = $kanban->json('columns');
    expect($columns['open'])->toBeArray();
});

it('enforces per-user open assignment limit', function () {
    config()->set('tasks.max_open_assignments', 1);
    $u = User::factory()->create();
    $this->actingAs($u);
    // two tasks
    $t1 = $this->postJson('/api/tasks', ['title' => 'T1'])->assertCreated()->json('data.id');
    $t2 = $this->postJson('/api/tasks', ['title' => 'T2'])->assertCreated()->json('data.id');

    $this->postJson("/api/tasks/{$t1}/assign", ['user_id' => $u->id])->assertOk();
    $this->postJson("/api/tasks/{$t2}/assign", ['user_id' => $u->id])->assertStatus(422);
});

it('blocks completion when dependency is not done; allows after done', function () {
    $this->actingAs(User::factory()->create());
    $dep = $this->postJson('/api/tasks', ['title' => 'DEP'])->assertCreated()->json('data.id');
    $task = $this->postJson('/api/tasks', ['title' => 'MAIN', 'depends_on_task_id' => $dep])->assertCreated()->json('data.id');
    $this->postJson("/api/tasks/{$task}/complete", [])->assertStatus(422);
    $this->postJson("/api/tasks/{$dep}/complete", [])->assertOk();
    $this->postJson("/api/tasks/{$task}/complete", [])->assertOk();
});

it('lists my agenda for assigned tasks', function () {
    $u = User::factory()->create();
    $this->actingAs($u);
    $t1 = $this->postJson('/api/tasks', ['title' => 'A'])->assertCreated()->json('data.id');
    $this->postJson("/api/tasks/{$t1}/assign", ['user_id' => $u->id])->assertOk();
    $agenda = $this->getJson('/api/tasks/my-agenda')->assertOk();
    expect($agenda->json('data'))->toBeArray();
});

