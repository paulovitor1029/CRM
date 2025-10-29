<?php

use App\Jobs\RefreshMaterializedViews;
use App\Models\Customer;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;

it('returns dashboard widgets payload', function () {
    // seed minimal data
    Task::create(['organization_id' => 'default', 'title' => 'T', 'status' => 'open']);

    // refresh views
    (new RefreshMaterializedViews())->handle();

    $resp = $this->getJson('/api/dashboard/widgets?organization_id=default')->assertOk();
    $data = $resp->json('data');
    expect($data)->toHaveKeys(['aging','prod','funnel']);
});
