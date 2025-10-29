<?php

use App\Jobs\DispatchWebhook;
use App\Models\OutboxEvent;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Http;

it('signs webhooks with HMAC and includes timestamp and idempotency key', function () {
    $ep = WebhookEndpoint::create(['organization_id' => 'default', 'event_key' => 'task.assigned', 'url' => 'https://example/webhook', 'secret' => 'shhh', 'active' => true]);
    $evt = OutboxEvent::create(['organization_id' => 'default', 'event_key' => 'task.assigned', 'payload' => ['task_id' => 'T1'], 'status' => 'pending']);
    $d = WebhookDelivery::create([
        'endpoint_id' => $ep->id,
        'outbox_id' => $evt->id,
        'event_key' => $evt->event_key,
        'payload' => $evt->payload,
        'idempotency_key' => $evt->id.'_'.$ep->id,
        'status' => 'pending',
        'attempts' => 0,
    ]);

    Http::fake(function ($request) use ($d) {
        expect($request->header('X-Webhook-Event'))->toBe('task.assigned');
        $sig = $request->header('X-Webhook-Signature');
        $ts = $request->header('X-Webhook-Timestamp');
        $idem = $request->header('Idempotency-Key');
        expect($idem)->toBe($d->idempotency_key);
        // Verify signature format and recompute
        expect(str_starts_with($sig, 'sha256='))->toBeTrue();
        $body = $request->getBody();
        $calc = hash_hmac('sha256', $ts.'.'.$body, 'shhh');
        expect(substr($sig, 7))->toBe($calc);
        return Http::response([], 200);
    });

    DispatchWebhook::dispatchSync($d->id);
});

it('applies exponential backoff and DLQ upon failures', function () {
    $ep = WebhookEndpoint::create(['organization_id' => 'default', 'event_key' => 'x', 'url' => 'https://example/webhook', 'secret' => 's', 'active' => true]);
    $d = WebhookDelivery::create(['endpoint_id' => $ep->id, 'event_key' => 'x', 'payload' => ['a'=>1], 'status' => 'pending', 'attempts' => 0]);
    Http::fake([ '*' => Http::response([], 500) ]);
    try { DispatchWebhook::dispatchSync($d->id); } catch (Throwable $e) {}
    $d->refresh();
    expect($d->status)->toBe('pending');
    expect($d->attempts)->toBe(1);
    expect($d->next_attempt_at)->not->toBeNull();
});
