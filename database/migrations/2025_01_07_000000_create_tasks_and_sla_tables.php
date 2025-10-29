<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) {
                $table->uuidPrimary('id');
            } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('key');
            $table->string('name');
            $table->unsignedInteger('target_response_minutes')->default(60);
            $table->unsignedInteger('target_resolution_minutes')->default(480);
            $table->json('working_hours')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampsTz(0);
            $table->unique(['tenant_id','key']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->uuid('sector_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open'); // open, in_progress, done, blocked, on_hold, canceled
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->timestampTz('due_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('assignee_id')->nullable();
            $table->uuid('sla_policy_id')->nullable();
            $table->timestampTz('response_due_at')->nullable();
            $table->timestampTz('resolution_due_at')->nullable();
            $table->timestampTz('first_response_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->json('recurrence')->nullable();
            $table->uuid('depends_on_task_id')->nullable();
            $table->timestampsTz(0);
            $table->foreign('sector_id')->references('id')->on('sectors')->nullOnDelete();
            $table->foreign('sla_policy_id')->references('id')->on('sla_policies')->nullOnDelete();
            $table->foreign('depends_on_task_id')->references('id')->on('tasks')->nullOnDelete();
            $table->index(['tenant_id','status','assignee_id']);
        });

        Schema::create('pendings', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('task_id');
            $table->uuid('assigned_to')->nullable();
            $table->timestampsTz(0);
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->unique('task_id');
        });

        Schema::create('task_comments', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('task_id');
            $table->uuid('user_id')->nullable();
            $table->text('content');
            $table->timestampsTz(0);
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
        });

        Schema::create('task_checklist_items', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('task_id');
            $table->string('description');
            $table->boolean('checked')->default(false);
            $table->timestampTz('checked_at')->nullable();
            $table->timestampsTz(0);
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
        });

        Schema::create('task_labels', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->string('tenant_id', 64)->default('default');
            $table->string('name');
            $table->string('color', 7)->nullable(); // #RRGGBB
            $table->timestampsTz(0);
            $table->unique(['tenant_id','name']);
        });

        Schema::create('task_task_label', function (Blueprint $table) {
            $table->uuid('task_id');
            $table->uuid('task_label_id');
            $table->primary(['task_id','task_label_id']);
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreign('task_label_id')->references('id')->on('task_labels')->cascadeOnDelete();
        });

        Schema::create('task_history', function (Blueprint $table) {
            if (method_exists($table, 'uuidPrimary')) { $table->uuidPrimary('id'); } else { $table->uuid('id')->primary(); }
            $table->uuid('task_id');
            $table->string('action');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('origin')->nullable();
            $table->timestampsTz(0);
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->index(['task_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_history');
        Schema::dropIfExists('task_task_label');
        Schema::dropIfExists('task_labels');
        Schema::dropIfExists('task_checklist_items');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('pendings');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('sla_policies');
    }
};

