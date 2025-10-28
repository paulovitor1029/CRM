<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_definitions', function (Blueprint $table) {
            if (method_exists($table, 'jsonb')) {
                $table->jsonb('design_draft')->nullable();
            } else {
                $table->json('design_draft')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('flow_definitions', function (Blueprint $table) {
            $table->dropColumn('design_draft');
        });
    }
};

