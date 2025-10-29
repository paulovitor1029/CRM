<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rule_definitions', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('name');
            $table->string('event_key'); // e.g. task.assigned, customer.created, payment.approved
            $table->json('conditions')->nullable(); // simple condition tree
            $table->boolean('enabled')->default(true);
            $table->timestampsTz(0);
            $table->index(['tenant_id','event_key','enabled']);
        });

        Schema::create('rule_actions', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('rule_id');
            $table->string('type'); // change_stage | create_task | send_notification | webhook
            $table->unsignedSmallInteger('position')->default(0);
            $table->json('params')->nullable();
            $table->timestampsTz(0);
            $table->foreign('rule_id')->references('id')->on('rule_definitions')->cascadeOnDelete();
            $table->index(['rule_id','position']);
        });

        Schema::create('outbox', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('event_key');
            $table->json('payload');
            $table->timestampTz('occurred_at')->default(DB::raw('now()'));
            $table->string('status')->default('pending'); // pending|processing|processed|failed
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz(0);
            $table->index(['tenant_id','event_key','status']);
        });

        Schema::create('rule_runs', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('rule_id');
            $table->uuid('outbox_id');
            $table->string('status')->default('processing'); // processing|completed|failed
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->json('logs')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampsTz(0);
            $table->foreign('rule_id')->references('id')->on('rule_definitions')->cascadeOnDelete();
            $table->foreign('outbox_id')->references('id')->on('outbox')->cascadeOnDelete();
            $table->unique(['rule_id','outbox_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_runs');
        Schema::dropIfExists('outbox');
        Schema::dropIfExists('rule_actions');
        Schema::dropIfExists('rule_definitions');
    }
};

