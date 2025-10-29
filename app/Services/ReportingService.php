<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function widgets(string $tenantId, array $filters = []): array
    {
        $mrr = DB::table('mrr_por_mes')->where('tenant_id', $tenantId)->orderBy('month')->limit(24)->get();
        $churn = DB::table('churn_por_mes')->where('tenant_id', $tenantId)->orderBy('month')->limit(24)->get();
        $aging = DB::table('aging_pendencias')->where('tenant_id', $tenantId)->get();
        $prod = DB::table('produtividade_setor')->where('tenant_id', $tenantId)->orderBy('day', 'desc')->limit(30)->get();
        $funnel = DB::table('conversoes_funil')->where('tenant_id', $tenantId)->orderBy('day', 'desc')->limit(30)->get();
        return compact('mrr','churn','aging','prod','funnel');
    }
}

