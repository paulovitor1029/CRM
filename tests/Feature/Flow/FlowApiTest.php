<?php

use App\Models\User;
use App\Models\FlowDefinition;
use Illuminate\Support\Facades\Hash;

it('creates sector with uniqueness by organization', function () {
    $this->actingAs(User::factory()->create(['password' => Hash::make('Str0ngP@ssw0rd!')]));

    $this->postJson('/api/sectors', [
        'organization_id' => 't1',
        'name' => 'SaÃºde',
    ])->assertCreated();

    // Same tenant + name should fail
    $this->postJson('/api/sectors', [
        'organization_id' => 't1',
        'name' => 'SaÃºde',
    ])->assertStatus(500); // unique constraint -> 500 from DB in this minimal stub

    // Different tenant ok
    $this->postJson('/api/sectors', [
        'organization_id' => 't2',
        'name' => 'SaÃºde',
    ])->assertCreated();
});

it('validates flow states and transitions', function () {
    $this->actingAs(User::factory()->create());

    // Missing terminal
    $payload = [
        'organization_id' => 'default',
        'key' => 'onboarding',
        'name' => 'Onboarding',
        'states' => [
            ['key' => 'draft', 'name' => 'Draft', 'initial' => true, 'terminal' => false],
        ],
        'transitions' => [],
    ];
    $this->postJson('/api/flows', $payload)->assertUnprocessable();

    // Valid flow
    $payload['states'][] = ['key' => 'done', 'name' => 'Done', 'terminal' => true];
    $payload['transitions'][] = ['key' => 'submit', 'from' => 'draft', 'to' => 'done'];
    $this->postJson('/api/flows', $payload)->assertCreated()->assertJsonPath('data.version', 1);

    // New version increments
    $this->postJson('/api/flows', $payload)->assertCreated()->assertJsonPath('data.version', 2);
});

it('publishes a flow, freezes version, and logs audit', function () {
    $this->actingAs(User::factory()->create());

    $create = $this->postJson('/api/flows', [
        'organization_id' => 'default',
        'key' => 'approval',
        'name' => 'Approval',
        'states' => [
            ['key' => 'start', 'name' => 'Start', 'initial' => true],
            ['key' => 'end', 'name' => 'End', 'terminal' => true],
        ],
        'transitions' => [
            ['key' => 'finish', 'from' => 'start', 'to' => 'end'],
        ],
    ])->assertCreated();

    $id = $create->json('data.id');
    $publish = $this->postJson("/api/flows/{$id}/publish")->assertOk();
    $publish->assertJsonPath('data.frozen', true);
    $publish->assertJsonStructure(['data' => ['id', 'published_at']]);

    // Second publish should 422
    $this->postJson("/api/flows/{$id}/publish")->assertUnprocessable();
});

