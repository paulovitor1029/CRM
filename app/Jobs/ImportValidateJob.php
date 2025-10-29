<?php

namespace App\Jobs;

use App\Models\ImportJob;
use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportValidateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $importJobId) {}

    public function handle(ImportService $import): void
    {
        $job = ImportJob::find($this->importJobId);
        if (!$job) return;
        [$total, $valid, $invalid, $errorKey] = $import->validateAll($job);
        $job->total_rows = $total;
        $job->valid_rows = $valid;
        $job->invalid_rows = $invalid;
        $job->error_report_key = $errorKey;
        $job->status = 'validated';
        $job->save();
    }
}

