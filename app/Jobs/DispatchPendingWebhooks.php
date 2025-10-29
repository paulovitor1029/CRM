<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchPendingWebhooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        WebhookDelivery::where('status', 'pending')
            ->where(function ($q) { $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()); })
            ->orderBy('created_at')
            ->limit(25)
            ->get()
            ->each(fn($d) => DispatchWebhook::dispatch($d->id));
    }
}

