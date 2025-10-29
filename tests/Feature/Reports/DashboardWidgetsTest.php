<?php

use App\Jobs\RefreshMaterializedViews;
use App\Models\Customer;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use App\Models\Task;

it('returns dashboard widgets payload', function () {
    // seed minimal data
    $s = Subscription::create(['tenant_id' => 'default', 'customer_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'status' => 'active', 'starts_at' => now()]);
    SubscriptionItem::create(['subscription_id' => $s->id, 'item_type' => 'product', 'item_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'quantity' => 1, 'price_cents' => 10000, 'currency' => 'BRL']);
    Task::create(['tenant_id' => 'default', 'title' => 'T', 'status' => 'open']);

    // refresh views
    (new RefreshMaterializedViews())->handle();

    $resp = $this->getJson('/api/dashboard/widgets?tenant_id=default')->assertOk();
    $data = $resp->json('data');
    expect($data)->toHaveKeys(['mrr','churn','aging','prod','funnel']);
});

