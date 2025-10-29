<?php

namespace App\Providers;

use App\Observability\MetricsRegistry as Metrics;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ObservabilityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // DB query timing
        DB::listen(function ($query) {
            $time = ($query->time ?? 0) / 1000.0; // ms to seconds if provided
            if ($time > 0) {
                Metrics::observeHistogram('db_query_duration_seconds', ['tenant' => 'default'], $time, [0.005,0.01,0.02,0.05,0.1,0.25,0.5,1,2]);
            }
        });

        // Queue metrics
        Event::listen(JobProcessed::class, function (JobProcessed $e) {
            Metrics::incCounter('queue_jobs_processed_total', ['queue' => $e->job->getQueue() ?? 'default']);
        });
        Event::listen(JobFailed::class, function (JobFailed $e) {
            Metrics::incCounter('queue_jobs_failed_total', ['queue' => $e->job->getQueue() ?? 'default']);
        });
    }
}

