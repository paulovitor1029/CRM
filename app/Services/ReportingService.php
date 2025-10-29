<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function widgets(string $organizationId, array $filters = []): array
    {
        $aging = DB::table('aging_pendencias')->where('organization_id', $organizationId)->get();
        $prod = DB::table('produtividade_setor')->where('organization_id', $organizationId)->orderBy('day', 'desc')->limit(30)->get();
        $funnel = DB::table('conversoes_funil')->where('organization_id', $organizationId)->orderBy('day', 'desc')->limit(30)->get();
        return compact('aging','prod','funnel');
    }
}
