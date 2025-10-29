<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename tenant_id -> organization_id in all public tables that still have it
        $rows = DB::select("SELECT table_name FROM information_schema.columns WHERE table_schema = 'public' AND column_name = 'tenant_id'");
        foreach ($rows as $row) {
            $t = $row->table_name;
            try {
                DB::statement("ALTER TABLE \"$t\" RENAME COLUMN tenant_id TO organization_id");
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        // Not implemented
    }
};
