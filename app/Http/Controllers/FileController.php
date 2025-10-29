<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilePresignRequest;
use App\Models\FileObject;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileController
{
    public function __construct(private readonly FileUploadService $files)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $list = FileObject::where('tenant_id', $tenant)->orderByDesc('created_at')->limit(50)->get();
        return response()->json(['data' => $list]);
    }

    public function presign(FilePresignRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tenant = (string) ($data['tenant_id'] ?? 'default');
        $userId = optional($request->user())->id;
        $res = $this->files->presign($tenant, $userId, $data['key'], $data['content_type'] ?? null, $data['size'] ?? null, $data['checksum'] ?? null, $data['meta'] ?? []);
        return response()->json([
            'key' => $data['key'],
            'upload_url' => $res['upload_url'],
            'headers' => $res['headers'],
        ], Response::HTTP_CREATED);
    }

    // Fallback backend upload: /api/files/upload?key=<...>
    public function upload(Request $request): JsonResponse
    {
        $key = (string) $request->query('key');
        $file = $request->file('file');
        if (!$file || $key === '') {
            return response()->json(['message' => 'Missing file or key'], 422);
        }
        $disk = config('files.disk', env('FILES_DISK', config('filesystems.default', 'local')));
        $path = $key;
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()), [
            'visibility' => 'private',
            'ContentType' => $file->getMimeType(),
        ]);
        FileObject::where('key', $key)->update([
            'size' => $file->getSize(),
            'content_type' => $file->getMimeType(),
            'uploaded_at' => now(),
        ]);
        return response()->json(['message' => 'Uploaded']);
    }
}

