<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_definitions', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('tenant_id', 64)->default('default');
            $table->string('key');
            $table->unsignedInteger('version');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->boolean('frozen')->default(false);
            $table->timestampsTz(0);
            $table->unique(['tenant_id', 'key', 'version']);
        });

        Schema::create('flow_states', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('flow_definition_id');
            $table->string('key');
            $table->string('name');
            $table->boolean('initial')->default(false);
            $table->boolean('terminal')->default(false);
            $table->timestampsTz(0);
            $table->foreign('flow_definition_id')->references('id')->on('flow_definitions')->cascadeOnDelete();
            $table->unique(['flow_definition_id', 'key']);
        });

        Schema::create('flow_transitions', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('flow_definition_id');
            $table->uuid('from_state_id');
            $table->uuid('to_state_id');
            $table->string('key');
            $table->timestampsTz(0);
            $table->foreign('flow_definition_id')->references('id')->on('flow_definitions')->cascadeOnDelete();
            $table->foreign('from_state_id')->references('id')->on('flow_states')->cascadeOnDelete();
            $table->foreign('to_state_id')->references('id')->on('flow_states')->cascadeOnDelete();
            $table->unique(['flow_definition_id', 'key']);
        });

        Schema::create('flow_logs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else {
                $table->uuid('id')->primary();
            }
            $table->uuid('flow_definition_id');
            $table->string('action');
            $table->json('details')->nullable();
            $table->uuid('user_id')->nullable();
            $table->timestampsTz(0);
            $table->foreign('flow_definition_id')->references('id')->on('flow_definitions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_logs');
        Schema::dropIfExists('flow_transitions');
        Schema::dropIfExists('flow_states');
        Schema::dropIfExists('flow_definitions');
    }
};

