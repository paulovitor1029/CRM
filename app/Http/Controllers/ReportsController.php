<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ReportsController
{
    public function export(Request $request): JsonResponse
    {
        $data = $request->validate([
            'report_key' => ['required', 'string', 'in:mrr_por_mes,churn_por_mes,aging_pendencias,produtividade_setor,conversoes_funil'],
            'format' => ['required', 'string', 'in:csv,xlsx,pdf'],
            'params' => ['nullable', 'array'],
        ]);
        $tenant = (string) ($request->query('tenant_id') ?? 'default');
        $id = (string) Str::uuid();
        ReportExport::create([
            'id' => $id,
            'tenant_id' => $tenant,
            'report_key' => $data['report_key'],
            'format' => $data['format'],
            'params' => $data['params'] ?? [],
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        GenerateReportExport::dispatch($id);

        return response()->json(['export_id' => $id, 'status' => 'pending'], Response::HTTP_ACCEPTED);
    }

    public function show(string $id): JsonResponse
    {
        $export = ReportExport::findOrFail($id);
        return response()->json(['data' => $export]);
    }
}

