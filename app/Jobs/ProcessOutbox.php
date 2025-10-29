<?php

namespace App\Jobs;

use App\Models\OutboxEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOutbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Claim a small batch of pending events and dispatch individual jobs
        OutboxEvent::where('status', 'pending')->orderBy('created_at')->limit(25)->get()->each(function (OutboxEvent $evt) {
            ProcessOutboxEvent::dispatch($evt->id);
            $evt->status = 'processing';
            $evt->save();
        });
    }
}

