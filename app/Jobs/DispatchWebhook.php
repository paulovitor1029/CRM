<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DispatchWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $deliveryId) {}

    public function handle(): void
    {
        $d = WebhookDelivery::with('endpoint')->find($this->deliveryId);
        if (!$d || $d->status === 'delivered') return;
        $ep = $d->endpoint;

        $body = json_encode($d->payload, JSON_UNESCAPED_UNICODE);
        $ts = (string) now()->getTimestamp();
        $signature = hash_hmac('sha256', $ts.'.'.$body, $ep->secret);
        $headers = array_merge($ep->headers ?? [], [
            'X-Webhook-Event' => $d->event_key,
            'X-Webhook-Id' => $d->id,
            'X-Webhook-Timestamp' => $ts,
            'X-Webhook-Signature' => 'sha256='.$signature,
            'Idempotency-Key' => $d->idempotency_key ?? $d->id,
            'Content-Type' => 'application/json',
        ]);

        $d->status = 'delivering';
        $d->attempts += 1;
        $d->save();

        try {
            $resp = Http::withHeaders($headers)->timeout(5)->post($ep->url, $d->payload);
            $d->response_status = $resp->status();
            if ($resp->successful()) {
                $d->status = 'delivered';
                $d->delivered_at = now();
                $d->last_error = null;
                $d->save();
                return;
            }
            throw new \RuntimeException('HTTP '.$resp->status());
        } catch (\Throwable $e) {
            $d->last_error = $e->getMessage();
            // Exponential backoff: 1m, 2m, 4m, 8m, capped 60m
            $delay = min(60, 2 ** ($d->attempts - 1));
            $d->next_attempt_at = now()->addMinutes($delay);
            if ($d->attempts >= config('webhooks.max_attempts', 8)) {
                $d->status = 'failed';
            } else {
                $d->status = 'pending';
            }
            $d->save();
            throw $e;
        }
    }
}

