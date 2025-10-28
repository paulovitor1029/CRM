<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('tenant_id', 64)->default('default');
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('ativo');
            $table->string('funnel_stage')->nullable();
            $table->json('meta')->nullable();
            $table->timestampsTz(0);

            $table->unique(['tenant_id', 'external_id']);
            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'funnel_stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

