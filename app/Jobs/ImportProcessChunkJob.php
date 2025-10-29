<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportProcessChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $importJobId, public readonly int $offset, public readonly int $limit) {}

    public function handle(ImportService $import): void
    {
        $job = ImportJob::find($this->importJobId);
        if (!$job) return;
        $import->processChunk($job, $this->offset, $this->limit);
    }
}

