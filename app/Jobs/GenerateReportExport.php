<?php

namespace App\Jobs;

use App\Models\ReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $exportId)
    {
    }

    public function handle(): void
    {
        $export = ReportExport::findOrFail($this->exportId);
        $export->status = 'processing';
        $export->updated_at = now();
        $export->save();

        try {
            $rows = $this->fetchRows($export->report_key, $export->organization_id, $export->params ?? []);
            $csv = $this->toCsv($rows);
            $disk = config('files.disk', 'local');
            $key = 'exports/'.$export->report_key.'_'.$export->id.'.'.($export->format ?: 'csv');
            Storage::disk($disk)->put($key, $csv, ['visibility' => 'private', 'ContentType' => 'text/csv']);
            $export->file_key = $key;
            $export->status = 'completed';
            $export->updated_at = now();
            $export->save();
        } catch (\Throwable $e) {
            $export->status = 'failed';
            $export->error = $e->getMessage();
            $export->updated_at = now();
            $export->save();
            throw $e;
        }
    }

    private function fetchRows(string $key, string $organizationId, array $params): array
    {
        return match ($key) {
            'aging_pendencias' => DB::table('aging_pendencias')->where('organization_id', $organizationId)->orderBy('bucket')->get()->map(fn($r)=>(array)$r)->all(),
            'produtividade_setor' => DB::table('produtividade_setor')->where('organization_id', $organizationId)->orderBy('day')->get()->map(fn($r)=>(array)$r)->all(),
            'conversoes_funil' => DB::table('conversoes_funil')->where('organization_id', $organizationId)->orderBy('day')->get()->map(fn($r)=>(array)$r)->all(),
            default => [],
        };
    }

    private function toCsv(array $rows): string
    {
        if (empty($rows)) return "";
        $out = fopen('php://temp', 'r+');
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) { fputcsv($out, $row); }
        rewind($out);
        return stream_get_contents($out) ?: '';
    }
}
