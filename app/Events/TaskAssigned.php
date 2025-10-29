<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $taskId,
        public readonly string $organizationId,
        public readonly string $assigneeId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('organization.'.$this->organizationId),
            new PrivateChannel('users.'.$this->assigneeId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.assigned';
    }
}
