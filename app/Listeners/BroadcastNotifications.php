<?php

namespace App\Listeners;

use App\Events\CustomerStageChanged;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Services\NotificationService;

class BroadcastNotifications
{
    public function __construct(private readonly NotificationService $service)
    {
    }

    public function handle($event): void
    {
        if ($event instanceof TaskCreated) {
            $this->service->notifyTaskCreated($event);
        } elseif ($event instanceof TaskAssigned) {
            $this->service->notifyTaskAssigned($event);
        } elseif ($event instanceof TaskCompleted) {
            $this->service->notifyTaskCompleted($event);
        } elseif ($event instanceof CustomerStageChanged) {
            $this->service->notifyStageChanged($event);
        }
    }
}

