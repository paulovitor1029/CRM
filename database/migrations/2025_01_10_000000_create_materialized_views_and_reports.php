<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<SQL
        -- Aging de pendências (tarefas em aberto por faixas)
        CREATE MATERIALIZED VIEW IF NOT EXISTS aging_pendencias AS
        SELECT
          t.organization_id,
          CASE
            WHEN EXTRACT(EPOCH FROM (now() - t.created_at))/3600 < 24 THEN 'lt_24h'
            WHEN EXTRACT(EPOCH FROM (now() - t.created_at))/3600 < 72 THEN '1_3d'
            ELSE 'gt_3d'
          END AS bucket,
          COUNT(*)::bigint AS total
        FROM tasks t
        WHERE t.status IN ('open','in_progress','on_hold','blocked')
        GROUP BY t.organization_id, bucket;
        CREATE INDEX IF NOT EXISTS idx_aging_pendencias_org_bucket ON aging_pendencias (organization_id, bucket);

        -- Produtividade por setor (tarefas concluídas por dia)
        CREATE MATERIALIZED VIEW IF NOT EXISTS produtividade_setor AS
        SELECT
          t.organization_id,
          t.sector_id,
          date_trunc('day', t.completed_at)::date AS day,
          COUNT(*)::bigint AS completed
        FROM tasks t
        WHERE t.completed_at IS NOT NULL
        GROUP BY t.organization_id, t.sector_id, day;
        CREATE INDEX IF NOT EXISTS idx_produtividade_setor_org_sector_day ON produtividade_setor (organization_id, sector_id, day);

        -- Conversões de funil (transições por dia)
        CREATE MATERIALIZED VIEW IF NOT EXISTS conversoes_funil AS
        SELECT
          p.organization_id,
          l.pipeline_id,
          date_trunc('day', l.created_at)::date AS day,
          COUNT(*)::bigint AS transitions
        FROM pipeline_transition_logs l
        JOIN pipelines p ON p.id = l.pipeline_id
        GROUP BY p.organization_id, l.pipeline_id, day;
        CREATE INDEX IF NOT EXISTS idx_conversoes_funil_org_pipeline_day ON conversoes_funil (organization_id, pipeline_id, day);
        SQL);

        DB::unprepared(<<<SQL
        CREATE TABLE IF NOT EXISTS report_exports (
          id uuid PRIMARY KEY,
          organization_id varchar(64) NOT NULL DEFAULT 'default',
          report_key varchar(64) NOT NULL,
          format varchar(8) NOT NULL,
          params jsonb NULL,
          status varchar(16) NOT NULL DEFAULT 'pending', -- pending|processing|completed|failed
          file_key text NULL,
          error text NULL,
          created_at timestamptz(0) NOT NULL DEFAULT now(),
          updated_at timestamptz(0) NOT NULL DEFAULT now()
        );
        CREATE INDEX IF NOT EXISTS idx_report_exports_org_created ON report_exports (organization_id, created_at);
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP MATERIALIZED VIEW IF EXISTS conversoes_funil;');
        DB::unprepared('DROP MATERIALIZED VIEW IF EXISTS produtividade_setor;');
        DB::unprepared('DROP MATERIALIZED VIEW IF EXISTS aging_pendencias;');
        DB::unprepared('DROP TABLE IF EXISTS report_exports;');
    }
};

