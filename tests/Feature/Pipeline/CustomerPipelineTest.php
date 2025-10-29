<?php

use App\Events\CustomerStageChanged;
use App\Models\Customer;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function seedPipeline($tenant = 'default') {
    $p = Pipeline::create(['organization_id' => $tenant, 'key' => 'vendas', 'type' => 'vendas', 'name' => 'Vendas']);
    PipelineStage::create(['pipeline_id' => $p->id, 'key' => 'novo', 'name' => 'Novo', 'position' => 1, 'initial' => true]);
    PipelineStage::create(['pipeline_id' => $p->id, 'key' => 'qualificado', 'name' => 'Qualificado', 'position' => 2]);
    return $p;
}

it('transitions customer through pipeline, logs and emits event', function () {
    $p = seedPipeline();
    $u = User::factory()->create();
    $this->actingAs($u);
    $customer = Customer::create(['organization_id' => 'default', 'name' => 'ACME', 'status' => 'ativo']);

    Event::fake([CustomerStageChanged::class]);

    // Move to novo
    $resp1 = $this->postJson("/api/customers/{$customer->id}/transition", [
        'pipeline_key' => 'vendas',
        'to_stage' => 'novo',
        'justification' => 'criado',
    ])->assertOk();
    $resp1->assertJsonPath('data.current_stage.key', 'novo');

    // Advance to qualificado
    $resp2 = $this->postJson("/api/customers/{$customer->id}/transition", [
        'pipeline_key' => 'vendas',
        'to_stage' => 'qualificado',
        'justification' => 'verificado',
    ])->assertOk();
    $resp2->assertJsonPath('data.current_stage.key', 'qualificado');

    Event::assertDispatched(CustomerStageChanged::class, 2);
});
