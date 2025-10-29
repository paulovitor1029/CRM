<?php

use App\Models\Customer;
use App\Models\Task;

it('isolates data by tenant in listing endpoints', function () {
    Customer::create(['tenant_id' => 't1', 'name' => 'C1', 'status' => 'ativo']);
    Customer::create(['tenant_id' => 't2', 'name' => 'C2', 'status' => 'ativo']);
    $resp1 = $this->getJson('/api/customers?tenant_id=t1')->assertOk();
    $names = array_column($resp1->json('data'), 'name');
    expect($names)->toContain('C1')->not->toContain('C2');

    Task::create(['tenant_id' => 't1', 'title' => 'T1']);
    Task::create(['tenant_id' => 't2', 'title' => 'T2']);
    $tresp = $this->getJson('/api/tasks?tenant_id=t2')->assertOk();
    $titles = array_column($tresp->json('data'), 'title');
    expect($titles)->toContain('T2')->not->toContain('T1');
});

