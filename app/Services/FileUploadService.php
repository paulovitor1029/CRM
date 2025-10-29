<?php

namespace App\Services;

use App\Models\FileObject;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    public function presign(string $tenantId, ?string $userId, string $key, ?string $contentType = null, ?int $size = null, ?string $checksum = null, array $meta = []): array
    {
        $diskName = config('files.disk', env('FILES_DISK', config('filesystems.default', 'local')));
        $disk = Storage::disk($diskName);

        $record = FileObject::updateOrCreate([
            'tenant_id' => $tenantId,
            'key' => $key,
        ], [
            'user_id' => $userId,
            'disk' => $diskName,
            'size' => $size,
            'content_type' => $contentType,
            'checksum' => $checksum,
            'meta' => $meta,
        ]);

        $url = null; $headers = [];
        // Try S3 presign
        try {
            $adapter = $disk->getAdapter();
            if (method_exists($adapter, 'getClient')) {
                $client = $adapter->getClient();
                $bucket = $adapter->getBucket();
                $cmd = $client->getCommand('PutObject', [
                    'Bucket' => $bucket,
                    'Key' => $key,
                    'ContentType' => $contentType ?? 'application/octet-stream',
                ]);
                $request = $client->createPresignedRequest($cmd, '+10 minutes');
                $url = (string) $request->getUri();
                $headers = ['Content-Type' => $contentType ?? 'application/octet-stream'];
            }
        } catch (\Throwable $e) {
            // Fallback: backend upload endpoint
            $url = url('/api/files/upload?key='.rawurlencode($key));
        }

        return [
            'file' => $record,
            'upload_url' => $url,
            'headers' => $headers,
        ];
    }
}

