<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportOrchestratorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $importJobId) {}

    public function handle(ImportService $import): void
    {
        $job = ImportJob::find($this->importJobId);
        if (!$job) return;
        // Use previously validated total_rows else compute quickly by counting lines
        $total = $job->total_rows;
        if ($total <= 0) {
            [$total] = $import->validateAll($job, 0);
            $job->total_rows = $total;
            $job->save();
        }
        $chunk = 2000;
        $offset = 0;
        while ($offset < $total) {
            ImportProcessChunkJob::dispatch($job->id, $offset, min($chunk, $total - $offset));
            $offset += $chunk;
        }
        $job->finished_at = now();
        $job->status = 'completed';
        $job->save();
    }
}

