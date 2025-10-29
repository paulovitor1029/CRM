<?php

use App\Models\ImportJob;
use Illuminate\Support\Facades\Storage;

it('uploads, maps, previews, validates and imports customers CSV', function () {
    config()->set('files.disk', 'local');
    $csv = "name,email,phone,status\nA,a@example.com,111,ativo\nB,b@example.com,222,ativo\n";
    Storage::disk('local')->put('imports/test_customers.csv', $csv);

    $job = $this->postJson('/api/imports/upload?tenant_id=default', [
        'entity_type' => 'customers',
        'file_key' => 'imports/test_customers.csv',
    ])->assertCreated()->json('data');

    $this->postJson("/api/imports/{$job['id']}/map", [
        'mapping' => [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'status' => 'status',
        ],
    ])->assertOk();

    $this->getJson("/api/imports/{$job['id']}/preview")->assertOk();

    $this->postJson("/api/imports/{$job['id']}/validate")->assertOk();

    // For test simplicity, run validation synchronously
    $importJob = ImportJob::find($job['id']);
    [$total, $valid, $invalid] = app(\App\Services\ImportService::class)->validateAll($importJob);
    $importJob->update(['total_rows' => $total, 'valid_rows' => $valid, 'invalid_rows' => $invalid]);

    $this->postJson("/api/imports/{$job['id']}/start")->assertOk();
    // Run a chunk directly
    app(\App\Services\ImportService::class)->processChunk(ImportJob::find($job['id']), 0, 1000);

    $this->assertDatabaseHas('customers', ['email' => 'a@example.com']);
});

