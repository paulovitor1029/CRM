<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('superadmin creates organization and adds member; user can switch', function () {
    $super = User::firstOrCreate(['email' => 'admin@system.local'], ['name' => 'Admin', 'password' => Hash::make('admin')]);
    // mark as superadmin via role
    $role = \App\Models\Role::firstOrCreate(['name'=>'superadmin'], ['label'=>'Super Administrator']);
    $super->roles()->syncWithoutDetaching([$role->id]);
    $this->actingAs($super);

    $org = $this->postJson('/api/organizations', ['name' => 'Org A'])->assertCreated()->json('data');
    $user = User::factory()->create();
    $this->postJson("/api/organizations/{$org['id']}/members", ['user_id' => $user->id, 'role' => 'org_admin'])->assertOk();

    // login as member and switch
    $this->actingAs($user);
    $list = $this->getJson('/api/organizations/switch')->assertOk();
    $this->postJson('/api/organizations/'.$org['id'].'/switch')->assertOk();
});

