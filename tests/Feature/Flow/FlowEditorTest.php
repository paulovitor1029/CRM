<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function createBaseFlowId($test) {
    $test->actingAs(User::factory()->create(['password' => Hash::make('Str0ngP@ssw0rd!')]));
    $resp = $test->postJson('/api/flows', [
        'tenant_id' => 'default',
        'key' => 'editor-flow',
        'name' => 'Editor Flow',
        'states' => [
            ['key' => 'start', 'name' => 'Start', 'initial' => true],
            ['key' => 'end', 'name' => 'End', 'terminal' => true],
        ],
        'transitions' => [
            ['key' => 'finish', 'from' => 'start', 'to' => 'end'],
        ],
    ])->assertCreated();
    return $resp->json('data.id');
}

it('saves design draft and publishes into states/transitions', function () {
    $id = createBaseFlowId($this);

    $graph = [
        'nodes' => [
            ['key' => 'novo', 'name' => 'Novo', 'initial' => true],
            ['key' => 'teste', 'name' => 'Teste', 'initial' => true],
            ['key' => 'financeiro', 'name' => 'Financeiro'],
            ['key' => 'suporte', 'name' => 'Suporte', 'terminal' => true],
        ],
        'edges' => [
            ['key' => 'e1', 'from' => 'novo', 'to' => 'financeiro', 'conditions' => [['type' => 'always']]],
            ['key' => 'e2', 'from' => 'financeiro', 'to' => 'suporte', 'conditions' => [['type' => 'attribute_equals', 'params' => ['attribute' => 'setor', 'value' => 'financeiro']]]],
            ['key' => 'e3', 'from' => 'teste', 'to' => 'suporte', 'conditions' => [['type' => 'tag_in', 'params' => ['tags' => ['qa', 'lab']]]]],
        ],
    ];

    $this->postJson("/api/flows/{$id}/design", $graph)->assertOk();
    $pub = $this->postJson("/api/flows/{$id}/publish")->assertOk();
    $pub->assertJsonPath('data.frozen', true);
    $pub->assertJson(fn ($json) => $json
        ->has('data.states', 4)
        ->has('data.transitions', 3)
    );
});

it('rejects unreachable nodes in design', function () {
    $id = createBaseFlowId($this);
    $graph = [
        'nodes' => [
            ['key' => 'start', 'name' => 'Start', 'initial' => true],
            ['key' => 'isolated', 'name' => 'Isolated'],
            ['key' => 'end', 'name' => 'End', 'terminal' => true],
        ],
        'edges' => [
            ['key' => 'finish', 'from' => 'start', 'to' => 'end', 'conditions' => [['type' => 'always']]],
        ],
    ];
    $this->postJson("/api/flows/{$id}/design", $graph)->assertUnprocessable();
});

it('rejects invalid condition type', function () {
    $id = createBaseFlowId($this);
    $graph = [
        'nodes' => [
            ['key' => 'start', 'name' => 'Start', 'initial' => true],
            ['key' => 'end', 'name' => 'End', 'terminal' => true],
        ],
        'edges' => [
            ['key' => 'bad', 'from' => 'start', 'to' => 'end', 'conditions' => [['type' => 'script', 'params' => ['code' => '...']]]],
        ],
    ];
    $this->postJson("/api/flows/{$id}/design", $graph)->assertUnprocessable();
});

