<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerStageChanged
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
    ) {
    }
}

