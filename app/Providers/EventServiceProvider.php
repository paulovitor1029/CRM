<?php

namespace App\Providers;

use App\Events\CustomerStageChanged;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Listeners\BroadcastNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TaskCreated::class => [BroadcastNotifications::class],
        TaskAssigned::class => [BroadcastNotifications::class],
        TaskCompleted::class => [BroadcastNotifications::class],
        CustomerStageChanged::class => [BroadcastNotifications::class],
    ];
}

