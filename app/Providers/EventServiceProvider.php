<?php

namespace App\Providers;

use App\Events\CustomerStageChanged;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Listeners\BroadcastNotifications;
use App\Listeners\OutboxEventRecorder;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TaskCreated::class => [BroadcastNotifications::class, OutboxEventRecorder::class],
        TaskAssigned::class => [BroadcastNotifications::class, OutboxEventRecorder::class],
        TaskCompleted::class => [BroadcastNotifications::class, OutboxEventRecorder::class],
        CustomerStageChanged::class => [BroadcastNotifications::class, OutboxEventRecorder::class],
        \App\Events\CustomerCreated::class => [OutboxEventRecorder::class],
        \App\Events\PaymentApproved::class => [OutboxEventRecorder::class],
    ];
}
