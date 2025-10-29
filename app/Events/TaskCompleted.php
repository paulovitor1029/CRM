<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $taskId,
        public readonly string $tenantId,
        public readonly ?string $assigneeId,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('tenant.'.$this->tenantId)];
        if ($this->assigneeId) { $channels[] = new PrivateChannel('users.'.$this->assigneeId); }
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'task.completed';
    }
}

