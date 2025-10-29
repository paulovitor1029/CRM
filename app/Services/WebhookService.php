<?php

namespace App\Services;

use App\Models\OutboxEvent;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function enqueueForOutbox(OutboxEvent $evt): void
    {
        $endpoints = WebhookEndpoint::where('organization_id', $evt->organization_id)
            ->where('event_key', $evt->event_key)
            ->where('active', true)
            ->get();
        foreach ($endpoints as $ep) {
            WebhookDelivery::firstOrCreate([
                'endpoint_id' => $ep->id,
                'outbox_id' => $evt->id,
            ], [
                'event_key' => $evt->event_key,
                'payload' => $evt->payload,
                'idempotency_key' => $evt->id.'_'.$ep->id,
                'status' => 'pending',
                'attempts' => 0,
                'next_attempt_at' => now(),
            ]);
        }
    }
}
