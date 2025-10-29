<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $drops = [
            'subscription_logs', 'subscription_items', 'subscriptions',
            'bundle_items', 'bundles', 'addons', 'plans',
            'product_metadata',
            'invoice_logs', 'payments', 'invoice_items', 'invoices',
        ];
        foreach ($drops as $t) {
            DB::statement("DROP TABLE IF EXISTS \"$t\" CASCADE;");
        }
        // Drop materialized views if any from billing reports
        $views = ['mrr_por_mes','churn_por_mes'];
        foreach ($views as $v) {
            DB::statement("DROP MATERIALIZED VIEW IF EXISTS \"$v\";");
        }
    }

    public function down(): void
    {
        // No-op: billing modules removed
    }
};

