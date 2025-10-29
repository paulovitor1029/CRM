<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates, autosaves, lists versions and rollbacks a document', function () {
    $this->actingAs(User::factory()->create(['password' => Hash::make('Str0ngP@ssw0rd!')]));

    $create = $this->postJson('/api/documents', [
        'title' => 'Doc 1',
        'content' => 'v1 content',
    ])->assertCreated();
    $id = $create->json('data.id');
    expect($create->json('data.current_version'))->toBe(1);

    $this->postJson("/api/documents/{$id}/autosave", [
        'content' => 'v2 content'
    ])->assertOk();

    $versions = $this->getJson("/api/documents/{$id}/versions")->assertOk();
    expect(count($versions->json('data')))->toBeGreaterThanOrEqual(2);

    $this->postJson("/api/documents/{$id}/versions/1/rollback", [])->assertOk();
    $doc = $this->getJson("/api/documents/{$id}")->assertOk();
    expect($doc->json('data.content'))->toBe('v1 content');
});

