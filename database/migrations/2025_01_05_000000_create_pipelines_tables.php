<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('tenant_id', 64)->default('default');
            $table->string('key'); // ex: vendas, implantacao, suporte
            $table->string('type')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampsTz(0);
            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('pipeline_stages', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('pipeline_id');
            $table->string('key');
            $table->string('name');
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('initial')->default(false);
            $table->boolean('terminal')->default(false);
            $table->timestampsTz(0);
            $table->foreign('pipeline_id')->references('id')->on('pipelines')->cascadeOnDelete();
            $table->unique(['pipeline_id', 'key']);
        });

        Schema::create('customer_pipeline_state', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('customer_id');
            $table->uuid('pipeline_id');
            $table->uuid('current_stage_id')->nullable();
            $table->json('meta')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestampsTz(0);
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('pipeline_id')->references('id')->on('pipelines')->cascadeOnDelete();
            $table->foreign('current_stage_id')->references('id')->on('pipeline_stages')->nullOnDelete();
            $table->unique(['customer_id', 'pipeline_id']);
        });

        Schema::create('pipeline_transition_logs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('customer_id');
            $table->uuid('pipeline_id');
            $table->uuid('from_stage_id')->nullable();
            $table->uuid('to_stage_id');
            $table->text('justification')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('origin')->nullable();
            $table->timestampsTz(0);
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('pipeline_id')->references('id')->on('pipelines')->cascadeOnDelete();
            $table->foreign('from_stage_id')->references('id')->on('pipeline_stages')->nullOnDelete();
            $table->foreign('to_stage_id')->references('id')->on('pipeline_stages')->cascadeOnDelete();
            $table->index(['customer_id', 'pipeline_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_transition_logs');
        Schema::dropIfExists('customer_pipeline_state');
        Schema::dropIfExists('pipeline_stages');
        Schema::dropIfExists('pipelines');
    }
};

