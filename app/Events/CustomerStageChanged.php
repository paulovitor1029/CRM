<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerStageChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $customerId,
        public readonly string $pipelineId,
        public readonly ?string $fromStageId,
        public readonly string $toStageId,
        public readonly ?string $justification,
        public readonly ?string $userId,
        public readonly ?string $origin,
        public readonly ?string $organizationId = 'default',
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('organization.'.$this->organizationId)];
        if ($this->userId) {
            $channels[] = new PrivateChannel('users.'.$this->userId);
        }
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'customer.stage.changed';
    }
}
