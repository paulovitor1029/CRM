<?php

use App\Models\Item;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAttribute;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

it('allows admin to perform all CRUD actions on items', function () {
    $admin = User::factory()->create(['password' => Hash::make('Str0ngP@ssw0rd!')]);
    $adminRole = Role::where('name', 'admin')->first();
    $admin->roles()->sync([$adminRole->id]);
    $this->actingAs($admin);

    // create
    $create = $this->postJson('/api/items', ['name' => 'X']);
    $create->assertCreated();
    $itemId = $create->json('data.id');

    // read
    $this->getJson('/api/items')->assertOk();

    // update
    $this->putJson("/api/items/{$itemId}", ['name' => 'Y'])->assertOk();

    // delete
    $this->deleteJson("/api/items/{$itemId}")->assertNoContent();
});

it('enforces RBAC for agente role (read only)', function () {
    $user = User::factory()->create();
    $role = Role::where('name', 'agente')->first();
    $user->roles()->sync([$role->id]);
    $this->actingAs($user);

    $this->getJson('/api/items')->assertOk();
    $this->postJson('/api/items', ['name' => 'X'])->assertForbidden();
});

it('enforces ABAC by setor for reports', function () {
    $gestor = User::factory()->create();
    $role = Role::where('name', 'gestor_setor')->first();
    $gestor->roles()->sync([$role->id]);
    UserAttribute::updateOrCreate(['user_id' => $gestor->id], [
        'attributes' => ['setor' => 'saude', 'tags' => ['interno']]
    ]);
    $this->actingAs($gestor);

    // allowed for own setor
    $this->getJson('/api/reports/sector/saude')->assertOk();
    // denied for other setor
    $this->getJson('/api/reports/sector/financeiro')->assertForbidden();
});

