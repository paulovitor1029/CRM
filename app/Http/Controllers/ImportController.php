<?php

namespace App\Http\Controllers;

use App\Jobs\ImportOrchestratorJob;
use App\Jobs\ImportValidateJob;
use App\Models\ImportJob;
use App\Models\ImportJobError;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ImportController
{
    public function __construct(private readonly ImportService $import)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default');
        $list = ImportJob::where('organization_id', $tenant)->orderByDesc('created_at')->paginate(20);
        return response()->json(['data' => $list->items(), 'meta' => ['current_page' => $list->currentPage()]]);
    }

    public function upload(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity_type' => ['required','in:customers,products,contacts'],
            'file' => ['nullable','file'],
            'file_key' => ['nullable','string'],
        ]);
        if (!$request->hasFile('file') && empty($data['file_key'])) {
            return response()->json(['message' => 'file or file_key is required'], 422);
        }
        $disk = config('files.disk', env('FILES_DISK', config('filesystems.default', 'local')));
        $key = $data['file_key'] ?? null; $original = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $original = $file->getClientOriginalName();
            $key = 'imports/'.Str::uuid()->toString().'_'.$original;
            Storage::disk($disk)->put($key, file_get_contents($file->getRealPath()), ['visibility' => 'private']);
        }
        $job = ImportJob::create([
            'organization_id' => (string) ($request->attributes->get('organization_id') ?? $request->query('organization_id') ?? 'default'),
            'entity_type' => $data['entity_type'],
            'file_key' => $key,
            'original_filename' => $original,
            'status' => 'uploaded',
            'created_by' => optional($request->user())->id,
        ]);
        return response()->json(['data' => $job], Response::HTTP_CREATED);
    }

    public function map(string $id, Request $request): JsonResponse
    {
        $job = ImportJob::findOrFail($id);
        $data = $request->validate([
            'mapping' => ['required','array'],
        ]);
        $job->mapping = $data['mapping'];
        $job->status = 'mapped';
        $job->save();
        return response()->json(['data' => $job]);
    }

    public function preview(string $id): JsonResponse
    {
        $job = ImportJob::findOrFail($id);
        $prev = $this->import->preview($job, 20);
        return response()->json(['data' => $prev]);
    }

    public function validateAll(string $id): JsonResponse
    {
        $job = ImportJob::findOrFail($id);
        $job->status = 'validating';
        $job->save();
        ImportValidateJob::dispatch($job->id);
        return response()->json(['data' => $job]);
    }

    public function errors(string $id): JsonResponse
    {
        $job = ImportJob::findOrFail($id);
        $rows = ImportJobError::where('import_job_id', $job->id)->orderBy('row_number')->limit(200)->get();
        return response()->json(['data' => $rows, 'error_report_key' => $job->error_report_key]);
    }

    public function start(string $id): JsonResponse
    {
        $job = ImportJob::findOrFail($id);
        $job->status = 'processing';
        $job->started_at = now();
        $job->save();
        ImportOrchestratorJob::dispatch($job->id);
        return response()->json(['data' => $job]);
    }

    public function show(string $id): JsonResponse
    {
        $job = ImportJob::findOrFail($id);
        return response()->json(['data' => $job]);
    }
}
