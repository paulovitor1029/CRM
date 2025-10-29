<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<SQL
        -- MRR por mês (somatório de itens de assinatura ativos/trial)
        CREATE MATERIALIZED VIEW IF NOT EXISTS mrr_por_mes AS
        SELECT
          s.tenant_id,
          date_trunc('month', COALESCE(s.starts_at, s.created_at))::date AS month,
          SUM((si.price_cents::numeric * si.quantity) / 100.0) AS mrr
        FROM subscriptions s
        JOIN subscription_items si ON si.subscription_id = s.id
        WHERE s.status IN ('active','trialing')
        GROUP BY s.tenant_id, month;
        CREATE INDEX IF NOT EXISTS idx_mrr_por_mes_tenant_month ON mrr_por_mes (tenant_id, month);

        -- Churn por mês (assinaturas canceladas por mês de updated_at)
        CREATE MATERIALIZED VIEW IF NOT EXISTS churn_por_mes AS
        SELECT
          s.tenant_id,
          date_trunc('month', COALESCE(s.updated_at, s.created_at))::date AS month,
          COUNT(*)::bigint AS canceled
        FROM subscriptions s
        WHERE s.status = 'canceled'
        GROUP BY s.tenant_id, month;
        CREATE INDEX IF NOT EXISTS idx_churn_por_mes_tenant_month ON churn_por_mes (tenant_id, month);

        -- Aging de pendências (tarefas em aberto por faixas)
        CREATE MATERIALIZED VIEW IF NOT EXISTS aging_pendencias AS
        SELECT
          t.tenant_id,
          CASE
            WHEN EXTRACT(EPOCH FROM (now() - t.created_at))/3600 < 24 THEN 'lt_24h'
            WHEN EXTRACT(EPOCH FROM (now() - t.created_at))/3600 < 72 THEN '1_3d'
            ELSE 'gt_3d'
          END AS bucket,
          COUNT(*)::bigint AS total
        FROM tasks t
        WHERE t.status IN ('open','in_progress','on_hold','blocked')
        GROUP BY t.tenant_id, bucket;
        CREATE INDEX IF NOT EXISTS idx_aging_pendencias_tenant_bucket ON aging_pendencias (tenant_id, bucket);

        -- Produtividade por setor (tarefas concluídas por dia)
        CREATE MATERIALIZED VIEW IF NOT EXISTS produtividade_setor AS
        SELECT
          t.tenant_id,
          t.sector_id,
          date_trunc('day', t.completed_at)::date AS day,
          COUNT(*)::bigint AS completed
        FROM tasks t
        WHERE t.completed_at IS NOT NULL
        GROUP BY t.tenant_id, t.sector_id, day;
        CREATE INDEX IF NOT EXISTS idx_produtividade_setor_tenant_sector_day ON produtividade_setor (tenant_id, sector_id, day);

        -- Conversões de funil (transições por dia)
        CREATE MATERIALIZED VIEW IF NOT EXISTS conversoes_funil AS
        SELECT
          p.tenant_id,
          l.pipeline_id,
          date_trunc('day', l.created_at)::date AS day,
          COUNT(*)::bigint AS transitions
        FROM pipeline_transition_logs l
        JOIN pipelines p ON p.id = l.pipeline_id
        GROUP BY p.tenant_id, l.pipeline_id, day;
        CREATE INDEX IF NOT EXISTS idx_conversoes_funil_tenant_pipeline_day ON conversoes_funil (tenant_id, pipeline_id, day);
        SQL);

        // Tabela para exportações assíncronas
        DB::unprepared(<<<SQL
        CREATE TABLE IF NOT EXISTS report_exports (
          id uuid PRIMARY KEY,
          tenant_id varchar(64) NOT NULL DEFAULT 'default',
          report_key varchar(64) NOT NULL,
          format varchar(8) NOT NULL,
          params jsonb NULL,
          status varchar(16) NOT NULL DEFAULT 'pending', -- pending|processing|completed|failed
          file_key text NULL,
          error text NULL,
          created_at timestamptz(0) NOT NULL DEFAULT now(),
          updated_at timestamptz(0) NOT NULL DEFAULT now()
        );
        CREATE INDEX IF NOT EXISTS idx_report_exports_tenant_created ON report_exports (tenant_id, created_at);
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP MATERIALIZED VIEW IF EXISTS conversoes_funil;');
        DB::unprepared('DROP MATERIALIZED VIEW IF EXISTS produtividade_setor;');
        DB::unprepared('DROP MATERIALIZED VIEW IF EXISTS aging_pendencias;');
        DB::unprepared('DROP MATERIALIZED VIEW IF EXISTS churn_por_mes;');
        DB::unprepared('DROP MATERIALIZED VIEW IF EXISTS mrr_por_mes;');
        DB::unprepared('DROP TABLE IF EXISTS report_exports;');
    }
};

