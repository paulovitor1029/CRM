<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\ImportJob;
use App\Models\ImportJobError;
use App\Models\Product;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportService
{
    public function getDisk(): Filesystem
    {
        $disk = config('files.disk', env('FILES_DISK', config('filesystems.default', 'local')));
        return Storage::disk($disk);
    }

    public function readHeader(ImportJob $job): array
    {
        $disk = $this->getDisk();
        $stream = $disk->readStream($job->file_key);
        if (!$stream) return [];
        $h = $this->readCsvHeader($stream);
        fclose($stream);
        return $h;
    }

    private function readCsvHeader($stream): array
    {
        $row = fgetcsv($stream);
        if (!$row) return [];
        // Remove BOM from first cell if present
        if (isset($row[0])) {
            $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
        }
        return $row;
    }

    public function preview(ImportJob $job, int $limit = 20): array
    {
        $disk = $this->getDisk();
        $stream = $disk->readStream($job->file_key);
        if (!$stream) return [];
        $header = $this->readCsvHeader($stream);
        $rows = [];
        $i = 0;
        while (($data = fgetcsv($stream)) !== false && $i < $limit) {
            $mapped = $this->mapRow($job->entity_type, $job->mapping ?? [], $header, $data);
            [$valid, $errors] = $this->validateRow($job->entity_type, $mapped);
            $rows[] = [
                'row_number' => $i + 2,
                'data' => $mapped,
                'errors' => $errors,
            ];
            $i++;
        }
        fclose($stream);
        return [ 'header' => $header, 'rows' => $rows ];
    }

    public function validateAll(ImportJob $job, int $maxErrorsStore = 200): array
    {
        $disk = $this->getDisk();
        $stream = $disk->readStream($job->file_key);
        if (!$stream) return [0,0,0];
        $header = $this->readCsvHeader($stream);
        $total = 0; $valid = 0; $invalid = 0; $errorsStored = 0;
        $errorCsv = fopen('php://temp', 'r+');
        fputcsv($errorCsv, array_merge($header, ['__errors']));
        while (($data = fgetcsv($stream)) !== false) {
            $total++;
            $mapped = $this->mapRow($job->entity_type, $job->mapping ?? [], $header, $data);
            [$ok, $errors] = $this->validateRow($job->entity_type, $mapped);
            if ($ok) {
                $valid++;
            } else {
                $invalid++;
                if ($errorsStored < $maxErrorsStore) {
                    ImportJobError::create([
                        'import_job_id' => $job->id,
                        'row_number' => $total + 1, // + header
                        'errors' => $errors,
                        'row_data' => $mapped,
                    ]);
                    $errorsStored++;
                }
                fputcsv($errorCsv, array_merge($data, [implode('; ', $errors)]));
            }
        }
        fclose($stream);
        rewind($errorCsv);
        $errorKey = null;
        if ($invalid > 0) {
            $errorKey = 'imports/errors/'.$job->id.'_errors.csv';
            $this->getDisk()->put($errorKey, stream_get_contents($errorCsv), ['visibility' => 'private', 'ContentType' => 'text/csv']);
        }
        fclose($errorCsv);
        return [$total, $valid, $invalid, $errorKey];
    }

    public function processChunk(ImportJob $job, int $offset, int $limit): array
    {
        $disk = $this->getDisk();
        $stream = $disk->readStream($job->file_key);
        if (!$stream) return [0,0];
        $header = $this->readCsvHeader($stream);
        // skip until offset
        $skipped = 0;
        while ($skipped < $offset && ($data = fgetcsv($stream)) !== false) { $skipped++; }
        $processed = 0; $imported = 0;
        while ($processed < $limit && ($data = fgetcsv($stream)) !== false) {
            $processed++;
            $mapped = $this->mapRow($job->entity_type, $job->mapping ?? [], $header, $data);
            [$ok, $errors] = $this->validateRow($job->entity_type, $mapped);
            if (!$ok) continue;
            $this->importRow($job->entity_type, $mapped);
            $imported++;
        }
        fclose($stream);
        return [$processed, $imported];
    }

    private function mapRow(string $entity, array $mapping, array $header, array $row): array
    {
        $source = [];
        foreach ($header as $i => $col) {
            $source[$col] = $row[$i] ?? null;
        }
        $out = [];
        foreach ($mapping as $target => $col) {
            $out[$target] = $source[$col] ?? null;
        }
        return $out;
    }

    private function validateRow(string $entity, array $data): array
    {
        if ($entity === 'customers') {
            $validator = Validator::make($data, [
                'name' => ['required','string','max:255'],
                'email' => ['nullable','email','max:255'],
                'phone' => ['nullable','string','max:64'],
                'status' => ['nullable','in:ativo,teste,inativo,suspenso,cancelado'],
            ]);
        } elseif ($entity === 'products') {
            $validator = Validator::make($data, [
                'name' => ['required','string','max:255'],
                'sku' => ['required','string','max:64'],
                'price_cents' => ['required','integer','min:0'],
                'currency' => ['nullable','string','size:3'],
            ]);
        } elseif ($entity === 'contacts') {
            $validator = Validator::make($data, [
                'customer_id' => ['nullable','uuid'],
                'type' => ['required','in:email,phone,whatsapp'],
                'value' => ['required','string','max:255'],
            ]);
        } else {
            return [false, ['Unsupported entity']];
        }
        return [$validator->passes(), $validator->errors()->all()];
    }

    private function importRow(string $entity, array $data): void
    {
        if ($entity === 'customers') {
            Customer::create([
                'organization_id' => 'default',
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? 'ativo',
            ]);
        } elseif ($entity === 'products') {
            Product::firstOrCreate([
                'organization_id' => 'default',
                'sku' => $data['sku'] ?? '',
            ], [
                'name' => $data['name'] ?? '',
                'price_cents' => (int) ($data['price_cents'] ?? 0),
                'currency' => $data['currency'] ?? 'BRL',
            ]);
        } elseif ($entity === 'contacts') {
            $customerId = $data['customer_id'] ?? null;
            if (!$customerId && !empty($data['email'])) {
                $customerId = optional(Customer::where('email',$data['email'])->first())->id;
            }
            CustomerContact::create([
                'customer_id' => $customerId,
                'type' => $data['type'] ?? 'email',
                'value' => $data['value'] ?? '',
                'preferred' => false,
            ]);
        }
    }
}
