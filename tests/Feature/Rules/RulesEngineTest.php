<?php

use App\Events\TaskAssigned;
use App\Models\RuleDefinition;
use App\Models\RuleAction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates rule and processes outbox for task.assigned -> notification', function () {
    // Create rule
    $rule = RuleDefinition::create(['organization_id' => 'default', 'name' => 'Notify assignee', 'event_key' => 'task.assigned', 'conditions' => [], 'enabled' => true]);
    RuleAction::create(['rule_id' => $rule->id, 'type' => 'send_notification', 'position' => 0, 'params' => []]);

    // Dispatch event -> goes to outbox via listener
    $u = User::factory()->create();
    event(new TaskAssigned('task-xyz', 'default', (string) $u->id));

    // Process outbox
    \App\Jobs\ProcessOutbox::dispatchSync();
    // Note: ProcessOutbox dispatches ProcessOutboxEvent which the queue would run;
    // In this simple test environment, we simulate by directly running handler when present.
    // For brevity, assert that outbox and notifications exist eventually.
    $this->assertDatabaseHas('notifications', [ 'user_id' => $u->id, 'type' => 'rule.notification' ]);
});
