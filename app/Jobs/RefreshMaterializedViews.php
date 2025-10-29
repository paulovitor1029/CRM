<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RefreshMaterializedViews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $views = [
            'mrr_por_mes',
            'churn_por_mes',
            'aging_pendencias',
            'produtividade_setor',
            'conversoes_funil',
        ];
        foreach ($views as $v) {
            DB::statement('REFRESH MATERIALIZED VIEW '.$v);
        }
    }
}

